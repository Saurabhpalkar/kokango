<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Helpers\ImageUrl;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\VendorProfileRequest;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\VendorPayout;
use App\Services\MarketplaceService;
use App\Support\Search;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Seller dashboard, inventory, payouts and store settings. */
class VendorPanelController extends Controller
{
    public function __construct(private MarketplaceService $marketplace)
    {
    }

    public function dashboard(Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);
        $s = $this->marketplace->stats($vendor);
        $low = $this->variants($vendor)->where('product_variants.stock', '<', MarketplaceService::LOW_STOCK_BELOW)->get();

        $recent = VendorOrder::query()
            ->where('vendor_orders.vendor_id', $vendor->id)
            ->join('orders', 'orders.id', '=', 'vendor_orders.order_id')
            ->select('vendor_orders.*')
            ->with('order.items')
            ->orderByDesc('orders.placed_at')
            ->limit(5)
            ->get();

        return response()->json(['data' => [
            'stats' => [
                'total_sales_paise' => $s['sales_paise'],
                'earnings_paise' => $s['earnings_paise'],
                'orders' => $s['orders'],
                'pending_orders' => $s['pending_orders'],
                'products' => $s['products'],
                'live_products' => $s['live_products'],
                'low_stock_count' => $low->pluck('product_id')->unique()->count(),
            ],
            'sales_by_day' => $this->marketplace->salesByDay(VendorOrder::where('vendor_orders.vendor_id', $vendor->id), 7, 'D'),
            'recent_orders' => $recent->map(fn ($vo) => $this->marketplace->orderPayload($vo))->all(),
            'low_stock' => $low->take(5)->map(fn ($v) => $this->inventoryRow($v))->values()->all(),
        ]]);
    }

    public function inventory(Request $request): JsonResponse
    {
        $query = $this->variants($this->vendor($request));

        if (($search = Search::term($request)) !== '') {
            $query->where(function ($q) use ($search) {
                Search::where($q, ['product_variants.sku'], $search);
                $q->orWhereHas('product', fn ($p) => Search::where($p, ['products.name'], $search));
            });
        }

        $rows = $query->get()->map(fn ($v) => $this->inventoryRow($v))->values();

        return response()->json(['data' => $rows]);
    }

    public function adjust(Request $request, int $variantId): JsonResponse
    {
        $data = $request->validate(['stock' => ['required', 'integer', 'min:0', 'max:100000']]);
        $vendor = $this->vendor($request);

        $variant = DB::transaction(function () use ($vendor, $variantId, $data, $request) {
            $variant = $this->variants($vendor)->whereKey($variantId)->lockForUpdate()->first();
            if (! $variant) {
                abort(404, 'Product size not found.');
            }

            $change = (int) $data['stock'] - (int) $variant->stock;
            if ($change !== 0) {
                $variant->update(['stock' => (int) $data['stock']]);
                StockMovement::create([
                    'product_variant_id' => $variant->id,
                    'change' => $change,
                    'stock_after' => (int) $data['stock'],
                    'reason' => 'Vendor stock update',
                    'user_id' => $request->user()->id,
                ]);
            }

            return $variant;
        });

        return response()->json(['data' => $this->inventoryRow($variant->load('product'))]);
    }

    public function payouts(Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);
        $s = $this->marketplace->stats($vendor);
        $list = VendorPayout::where('vendor_id', $vendor->id)->orderByDesc('id')->get();

        return response()->json(['data' => [
            'summary' => [
                'available_paise' => $s['payable_paise'],
                'processing_paise' => (int) $list->whereIn('status', ['scheduled', 'processing'])->sum('amount_paise'),
                'paid_paise' => (int) $list->where('status', 'paid')->sum('amount_paise'),
                'commission_percent' => $s['commission_percent'],
            ],
            'payouts' => $list->map(fn ($p) => $this->marketplace->payoutPayload($p))->all(),
        ]]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->marketplace->vendorPayload($this->vendor($request), true)]);
    }

    public function updateStore(VendorProfileRequest $request): JsonResponse
    {
        $vendor = $this->vendor($request);
        $fields = $request->vendorFields();

        DB::transaction(function () use ($vendor, $fields) {
            $vendor->update($fields);
            $vendor->user()->update(['name' => $fields['owner_name'], 'email' => $fields['email']]);
        });

        return response()->json(['data' => $this->marketplace->vendorPayload($vendor->fresh(), true)]);
    }

    // ------------------------------------------------------------------ helpers

    private function vendor(Request $request): Vendor
    {
        return $request->attributes->get('vendor');
    }

    /** Active sizes of the seller's current listings. */
    private function variants(Vendor $vendor)
    {
        return ProductVariant::query()
            ->select('product_variants.*')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->where('products.vendor_id', $vendor->id)
            ->where('products.status', '!=', 'archived')
            ->where('product_variants.is_active', true)
            ->with('product')
            ->orderBy('products.name')
            ->orderBy('product_variants.price_paise');
    }

    private function inventoryRow(ProductVariant $v): array
    {
        return [
            'variant_id' => $v->id,
            'product_id' => $v->product_id,
            'product_name' => $v->product?->name,
            'size_label' => $v->size_label,
            'sku' => $v->sku,
            'stock' => (int) $v->stock,
            'low' => (int) $v->stock < MarketplaceService::LOW_STOCK_BELOW,
            'image_url' => ImageUrl::resolve($v->product?->image),
        ];
    }
}
