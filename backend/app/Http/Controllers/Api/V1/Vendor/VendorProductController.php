<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Helpers\ImageUrl;
use App\Helpers\Slug;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\VendorProductRequest;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Vendor;
use App\Services\MarketplaceService;
use App\Support\Search;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** A seller's own listings. New and edited listings go to Kokango for review before they are live. */
class VendorProductController extends Controller
{
    private const MAX_IMAGE_BYTES = 2 * 1024 * 1024;

    public function __construct(private MarketplaceService $marketplace)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);
        $query = $this->own($vendor)->with(['category:id,name', 'variants']);

        if (($search = Search::term($request)) !== '') {
            Search::where($query, ['products.name'], $search);
        }

        $status = $request->query('status');
        if (is_string($status) && in_array($status, Product::VENDOR_STATUSES, true)) {
            $query->where('status', $status);
        }

        $page = $query->orderByDesc('id')->paginate(6)->withQueryString();

        return response()->json($this->marketplace->page($page, fn (Product $p) => $this->marketplace->productPayload($p)));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json(['data' => $this->marketplace->productPayload($this->find($request, $id))]);
    }

    public function store(VendorProductRequest $request): JsonResponse
    {
        $vendor = $this->vendor($request);
        $data = $request->validated();
        $this->checkSkus($data['variants']);

        $product = DB::transaction(function () use ($vendor, $data, $request) {
            $product = Product::create([
                'vendor_id' => $vendor->id,
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'slug' => Slug::unique($data['name'], Product::class),
                'description' => $data['description'] ?? null,
                'image' => $this->storeImage($data['image_url'] ?? null, null),
                'is_active' => false,
            ] + $this->listingState($vendor, $data['status']));

            foreach ($data['variants'] as $row) {
                $this->createVariant($product, $vendor, $row, $request->user()->id);
            }

            return $product;
        });

        $this->marketplace->refreshVisibility($product, $vendor);

        return response()->json(['data' => $this->marketplace->productPayload($product->fresh(['category', 'variants']))], 201);
    }

    public function update(VendorProductRequest $request, int $id): JsonResponse
    {
        $vendor = $this->vendor($request);
        $product = $this->find($request, $id);
        $data = $request->validated();
        $userId = $request->user()->id;

        $variantIds = $product->variants()->pluck('id')->all();
        foreach ($data['variants'] as $i => $row) {
            if (! empty($row['id']) && ! in_array((int) $row['id'], $variantIds, true)) {
                throw ValidationException::withMessages(["variants.$i.id" => ['This size does not belong to the product.']]);
            }
        }
        $this->checkSkus($data['variants']);

        $oldImage = $product->image;
        $newImage = array_key_exists('image_url', $data) ? $this->storeImage($data['image_url'], $product) : $oldImage;

        DB::transaction(function () use ($product, $vendor, $data, $userId, $newImage) {
            $product->update([
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'image' => $newImage,
            ] + $this->listingState($vendor, $data['status']));

            // Locked so a checkout taking stock at the same moment is not overwritten.
            $existing = $product->variants()->lockForUpdate()->get()->keyBy('id');
            $keep = [];

            foreach ($data['variants'] as $row) {
                $variant = ! empty($row['id']) ? $existing->get((int) $row['id']) : null;
                if (! $variant) {
                    $keep[] = $this->createVariant($product, $vendor, $row, $userId)->id;

                    continue;
                }

                $old = (int) $variant->stock;
                $new = (int) ($row['stock'] ?? 0);
                $variant->update([
                    'sku' => $this->sku($row, $vendor, $variant->sku),
                    'size_label' => $row['size_label'],
                    'price_paise' => $row['price_paise'],
                    'mrp_paise' => max((int) ($row['mrp_paise'] ?? 0), (int) $row['price_paise']),
                    'stock' => $new,
                    'is_active' => true,
                ]);
                if ($old !== $new) {
                    StockMovement::create([
                        'product_variant_id' => $variant->id,
                        'change' => $new - $old,
                        'stock_after' => $new,
                        'reason' => 'Vendor product edit',
                        'user_id' => $userId,
                    ]);
                }
                $keep[] = $variant->id;
            }

            // Sizes removed from the form: delete when never ordered, otherwise just hide.
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

        if ($newImage !== $oldImage) {
            $this->deleteStoredImage($oldImage);
        }

        $product->refresh();
        $this->marketplace->refreshVisibility($product, $vendor);

        return response()->json(['data' => $this->marketplace->productPayload($product->load(['category', 'variants']))]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $product = $this->find($request, $id);

        // Already sold: keep the row for order history, but take it off the shop and out of the panel.
        if (OrderItem::where('product_id', $product->id)->exists()) {
            $product->forceFill(['status' => 'archived', 'is_active' => false])->save();
        } else {
            DB::transaction(fn () => $product->delete());
            $this->deleteStoredImage($product->image);
        }

        return response()->json(['message' => 'Product deleted.']);
    }

    // ------------------------------------------------------------------ helpers

    private function vendor(Request $request): Vendor
    {
        return $request->attributes->get('vendor');
    }

    private function own(Vendor $vendor)
    {
        return Product::query()->where('vendor_id', $vendor->id)->where('status', '!=', 'archived');
    }

    private function find(Request $request, int $id): Product
    {
        $product = $this->own($this->vendor($request))->with(['category:id,name', 'variants'])->find($id);
        if (! $product) {
            abort(404, 'Product not found.');
        }

        return $product;
    }

    /** Status + review fields for a save. Submitting goes to review, unless auto-approve is on for an approved store. */
    private function listingState(Vendor $vendor, string $requested): array
    {
        if ($requested === 'draft') {
            return ['status' => 'draft'];
        }

        if ($vendor->isApproved() && $this->marketplace->settings()['auto_approve_products']) {
            return ['status' => 'active', 'review_note' => null, 'submitted_at' => now()];
        }

        return ['status' => 'pending_review', 'review_note' => null, 'submitted_at' => now()];
    }

    private function createVariant(Product $product, Vendor $vendor, array $row, int $userId): ProductVariant
    {
        $stock = (int) ($row['stock'] ?? 0);
        $variant = $product->variants()->create([
            'sku' => $this->sku($row, $vendor),
            'size_label' => $row['size_label'],
            'price_paise' => $row['price_paise'],
            'mrp_paise' => max((int) ($row['mrp_paise'] ?? 0), (int) $row['price_paise']),
            'stock' => $stock,
            'is_active' => true,
        ]);

        if ($stock > 0) {
            StockMovement::create([
                'product_variant_id' => $variant->id,
                'change' => $stock,
                'stock_after' => $stock,
                'reason' => 'Initial stock',
                'user_id' => $userId,
            ]);
        }

        return $variant;
    }

    /** The SKU the vendor typed, or a generated one (SKUs are unique across the whole shop). */
    private function sku(array $row, Vendor $vendor, ?string $current = null): string
    {
        $sku = trim((string) ($row['sku'] ?? ''));
        if ($sku !== '') {
            return $sku;
        }
        if ($current) {
            return $current;
        }
        do {
            $sku = 'V'.$vendor->id.'-'.strtoupper(Str::random(8));
        } while (ProductVariant::where('sku', $sku)->exists());

        return $sku;
    }

    private function checkSkus(array $variants): void
    {
        $seen = [];
        foreach ($variants as $i => $row) {
            $sku = trim((string) ($row['sku'] ?? ''));
            if ($sku === '') {
                continue;
            }
            $taken = in_array(mb_strtolower($sku), $seen, true) || ProductVariant::where('sku', $sku)
                ->when(! empty($row['id']), fn ($q) => $q->where('id', '!=', (int) $row['id']))
                ->exists();
            if ($taken) {
                throw ValidationException::withMessages(["variants.$i.sku" => ['This SKU is already used. Leave it empty to get one automatically.']]);
            }
            $seen[] = mb_strtolower($sku);
        }
    }

    /**
     * The form sends a "data:image/...;base64," string for a new photo, or back the URL it received.
     * Returns the value to store in products.image.
     */
    private function storeImage(?string $value, ?Product $product): ?string
    {
        if ($value === null || $value === '') {
            return $product?->image;
        }

        if (preg_match('#^data:image/(png|jpe?g|webp);base64,(.+)$#s', $value, $m)) {
            $bytes = base64_decode($m[2], true);
            if ($bytes === false || strlen($bytes) > self::MAX_IMAGE_BYTES || @getimagesizefromstring($bytes) === false) {
                throw ValidationException::withMessages(['image_url' => ['Please choose a JPG, PNG or WebP image under 2 MB.']]);
            }
            $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
            $path = 'products/vendor-'.Str::random(24).'.'.$ext;
            Storage::disk('public')->put($path, $bytes);

            return $path;
        }

        if (str_starts_with($value, 'data:')) {
            throw ValidationException::withMessages(['image_url' => ['Please choose a JPG, PNG or WebP image under 2 MB.']]);
        }

        // Unchanged image: the form echoes back the URL we sent.
        if ($product && $value === ImageUrl::resolve($product->image)) {
            return $product->image;
        }

        // Images bundled with the website.
        if (preg_match('#^/images/[A-Za-z0-9._-]+$#', $value)) {
            return $value;
        }

        throw ValidationException::withMessages(['image_url' => ['Please upload the product photo from your computer.']]);
    }

    /** Only files we stored ourselves (products/... on the public disk) are removed. */
    private function deleteStoredImage(?string $path): void
    {
        if ($path && str_starts_with($path, 'products/') && ! str_contains($path, '..')) {
            Storage::disk('public')->delete($path);
        }
    }
}
