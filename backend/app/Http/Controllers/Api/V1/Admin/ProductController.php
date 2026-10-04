<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\Slug;
use App\Http\Controllers\Api\V1\Concerns\ResolvesPerPage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Http\Resources\Admin\AdminProductResource;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Support\Search;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    use ResolvesPerPage;

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Product::query()->with(['category:id,slug,name', 'variants']);

        if (($search = Search::term($request)) !== '') {
            $query->where(function ($q) use ($search) {
                Search::where($q, ['products.name', 'products.hindi_name'], $search);
                $q->orWhereHas('variants', fn ($v) => Search::where($v, ['product_variants.sku'], $search));
            });
        }

        $categoryId = $request->query('category_id');
        if (is_numeric($categoryId) && (int) $categoryId > 0) {
            $query->where('category_id', (int) $categoryId);
        }

        return AdminProductResource::collection(
            $query->orderByDesc('id')->paginate($this->perPage($request))->withQueryString()
        );
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $data = $request->validated();

        $product = DB::transaction(function () use ($data, $request) {
            $product = Product::create([
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'slug' => Slug::unique($data['name'], Product::class),
                'hindi_name' => $data['hindi_name'] ?? null,
                'description' => $data['description'] ?? null,
                'image' => $this->normaliseImage($data['image_url'] ?? null),
                'badge' => $data['badge'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            foreach ($data['variants'] as $row) {
                $this->createVariant($product, $row, $request->user()?->id);
            }

            return $product;
        });

        return (new AdminProductResource($this->fresh($product)))->response()->setStatusCode(201);
    }

    public function show(int $id): AdminProductResource
    {
        return new AdminProductResource($this->fresh(Product::findOrFail($id)));
    }

    public function update(ProductRequest $request, int $id): AdminProductResource
    {
        $product = Product::findOrFail($id);
        $data = $request->validated();
        $userId = $request->user()?->id;

        DB::transaction(function () use ($product, $data, $userId) {
            $product->update([
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'hindi_name' => $data['hindi_name'] ?? null,
                'description' => $data['description'] ?? null,
                'badge' => $data['badge'] ?? null,
                'is_active' => $data['is_active'] ?? $product->is_active,
            ] + (array_key_exists('image_url', $data) ? ['image' => $this->normaliseImage($data['image_url'])] : []));

            // Locked so a checkout decrementing stock at the same moment is not overwritten half-way.
            $existing = $product->variants()->lockForUpdate()->get()->keyBy('id');
            $keep = [];

            foreach ($data['variants'] as $index => $row) {
                $variantId = $row['id'] ?? null;

                if ($variantId) {
                    $variant = $existing->get((int) $variantId);
                    if (! $variant) {
                        throw ValidationException::withMessages([
                            "variants.{$index}.id" => ['This variant does not belong to the product.'],
                        ]);
                    }

                    $oldStock = (int) $variant->stock;
                    $variant->update([
                        'sku' => $row['sku'],
                        'size_label' => $row['size_label'],
                        'price_paise' => $row['price_paise'],
                        'mrp_paise' => $row['mrp_paise'] ?? null,
                        'stock' => $row['stock'],
                        'is_active' => $row['is_active'] ?? true,
                    ]);

                    if ($oldStock !== (int) $row['stock']) {
                        StockMovement::create([
                            'product_variant_id' => $variant->id,
                            'change' => (int) $row['stock'] - $oldStock,
                            'stock_after' => (int) $row['stock'],
                            'reason' => 'Product edit',
                            'user_id' => $userId,
                        ]);
                    }
                    $keep[] = $variant->id;
                } else {
                    $keep[] = $this->createVariant($product, $row, $userId)->id;
                }
            }

            // Variants dropped from the payload: delete when never ordered, otherwise just deactivate.
            foreach ($existing as $variant) {
                if (in_array($variant->id, $keep, true)) {
                    continue;
                }

                if (OrderItem::where('product_variant_id', $variant->id)->exists()) {
                    $variant->update(['is_active' => false]);
                } else {
                    $variant->delete();
                }
            }
        });

        return new AdminProductResource($this->fresh($product));
    }

    public function destroy(int $id): JsonResponse|AdminProductResource
    {
        $product = Product::findOrFail($id);

        if (OrderItem::where('product_id', $product->id)->exists()) {
            $product->update(['is_active' => false]);

            return new AdminProductResource($this->fresh($product));
        }

        DB::transaction(fn () => $product->delete());
        $this->deleteStoredImage($product->image);

        return response()->json(['message' => 'Product deleted.']);
    }

    public function uploadImage(Request $request, int $id): AdminProductResource
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $path = $request->file('image')->store('products', 'public');

        $old = $product->image;
        $product->update(['image' => $path]);
        $this->deleteStoredImage($old);

        return new AdminProductResource($this->fresh($product));
    }

    private function createVariant(Product $product, array $row, ?int $userId): ProductVariant
    {
        $variant = $product->variants()->create([
            'sku' => $row['sku'],
            'size_label' => $row['size_label'],
            'price_paise' => $row['price_paise'],
            'mrp_paise' => $row['mrp_paise'] ?? null,
            'stock' => $row['stock'],
            'is_active' => $row['is_active'] ?? true,
        ]);

        if ((int) $row['stock'] > 0) {
            StockMovement::create([
                'product_variant_id' => $variant->id,
                'change' => (int) $row['stock'],
                'stock_after' => (int) $row['stock'],
                'reason' => 'Initial stock',
                'user_id' => $userId,
            ]);
        }

        return $variant;
    }

    private function fresh(Product $product): Product
    {
        return $product->load(['category:id,slug,name', 'variants']);
    }

    /**
     * The admin form sends back the image_url it received. For an uploaded file that is our own
     * absolute storage URL: keep only the relative path so APP_URL changes do not break it.
     */
    private function normaliseImage(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $prefix = Storage::disk('public')->url('');
        if ($prefix !== '' && str_starts_with($value, $prefix)) {
            return substr($value, strlen($prefix));
        }

        return $value;
    }

    /** Only files we stored ourselves (products/... on the public disk) are removed. */
    private function deleteStoredImage(?string $path): void
    {
        if ($path && str_starts_with($path, 'products/') && ! str_contains($path, '..')) {
            Storage::disk('public')->delete($path);
        }
    }
}
