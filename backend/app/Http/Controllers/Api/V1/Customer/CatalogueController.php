<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\V1\Concerns\ResolvesPerPage;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Support\Search;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CatalogueController extends Controller
{
    use ResolvesPerPage;

    /** Lowest active variant price of a product (correlated subquery). */
    private const MIN_PRICE_SQL = '(select min(pv.price_paise) from product_variants pv where pv.product_id = products.id and pv.is_active = 1)';

    public function categories(): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function products(Request $request): AnonymousResourceCollection
    {
        $query = Product::query()
            ->where('is_active', true)
            ->whereHas('category', fn ($q) => $q->where('is_active', true))
            ->whereHas('variants', fn ($q) => $q->where('is_active', true))
            ->with([
                'category:id,slug,name',
                'variants' => fn ($q) => $q->where('is_active', true),
            ]);

        $slug = $request->query('category');
        if (is_string($slug) && $slug !== '') {
            $query->whereHas('category', fn ($q) => $q->where('slug', $slug));
        }

        if (($search = Search::term($request)) !== '') {
            Search::where($query, ['products.name', 'products.hindi_name', 'products.description'], $search);
        }

        if (is_numeric($request->query('min_price'))) {
            $query->whereRaw(self::MIN_PRICE_SQL.' >= ?', [(int) min((float) $request->query('min_price'), 9.0e15)]);
        }
        if (is_numeric($request->query('max_price'))) {
            $query->whereRaw(self::MIN_PRICE_SQL.' <= ?', [(int) min((float) $request->query('max_price'), 9.0e15)]);
        }

        match ($request->query('sort')) {
            'price_asc' => $query->orderByRaw(self::MIN_PRICE_SQL.' asc')->orderBy('id'),
            'price_desc' => $query->orderByRaw(self::MIN_PRICE_SQL.' desc')->orderBy('id'),
            default => $query->orderByDesc('id'),
        };

        return ProductResource::collection(
            $query->paginate($this->perPage($request))->withQueryString()
        );
    }

    public function show(string $slug): ProductResource
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with([
                'category:id,slug,name',
                'variants' => fn ($q) => $q->where('is_active', true),
            ])
            ->firstOrFail();

        return new ProductResource($product);
    }

    public function publicSettings(): JsonResponse
    {
        return response()->json([
            'data' => Setting::typed([
                'store_name',
                'gst_percent',
                'shipping_standard_paise',
                'shipping_express_paise',
                'free_shipping_above_paise',
                'free_shipping_note',
                'whatsapp_number',
            ]),
        ]);
    }
}
