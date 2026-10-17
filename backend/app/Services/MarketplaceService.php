<?php

namespace App\Services;

use App\Helpers\ImageUrl;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\VendorPayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Marketplace rules shared by the vendor panel and the admin marketplace screens:
 * commission, seller figures, the JSON shapes the panels read, and splitting a customer order per seller.
 */
class MarketplaceService
{
    public const LOW_STOCK_BELOW = 10;

    /** Vendor order states that still count as sales (everything except cancelled). */
    private const LIVE = ['pending', 'confirmed', 'packed', 'shipped', 'delivered'];

    // ------------------------------------------------------------------ settings

    /**
     * @return array{default_commission: float|int, min_payout_paise: int, auto_approve_products: bool}
     */
    public function settings(): array
    {
        $commission = Setting::get('marketplace_default_commission', 10);
        $minPayout = Setting::get('marketplace_min_payout_paise', 50000);

        return [
            'default_commission' => is_numeric($commission) ? $commission + 0 : 10,
            'min_payout_paise' => is_numeric($minPayout) ? (int) $minPayout : 50000,
            'auto_approve_products' => (string) Setting::get('marketplace_auto_approve_products', '0') === '1',
        ];
    }

    public function saveSettings(array $data): array
    {
        Setting::set('marketplace_default_commission', $data['default_commission'] + 0);
        Setting::set('marketplace_min_payout_paise', (int) $data['min_payout_paise']);
        Setting::set('marketplace_auto_approve_products', (bool) ($data['auto_approve_products'] ?? false));

        return $this->settings();
    }

    /** Commission percent that applies to this vendor (own override, else the marketplace default). */
    public function rate(Vendor $vendor, ?array $settings = null): float|int
    {
        return $vendor->commission_percent !== null
            ? $vendor->commission_percent + 0
            : ($settings ?? $this->settings())['default_commission'];
    }

    // ------------------------------------------------------------------ figures

    /**
     * Sales figures for several vendors in two grouped queries.
     *
     * @param  Collection<int, Vendor>  $vendors
     * @return array<int, array<string, int|float>>  keyed by vendor id
     */
    public function statsFor(Collection $vendors): array
    {
        $ids = $vendors->pluck('id')->all();
        if (! $ids) {
            return [];
        }

        $live = "'".implode("','", self::LIVE)."'";
        $orders = VendorOrder::query()
            ->whereIn('vendor_id', $ids)
            ->groupBy('vendor_id')
            ->selectRaw('vendor_id, count(*) as orders')
            ->selectRaw("sum(case when status in ('pending','confirmed') then 1 else 0 end) as pending_orders")
            ->selectRaw("sum(case when status in ($live) then subtotal_paise else 0 end) as sales_paise")
            ->selectRaw("sum(case when status in ($live) then commission_paise else 0 end) as commission_paise")
            ->selectRaw("sum(case when status in ($live) then earning_paise else 0 end) as earnings_paise")
            ->selectRaw("sum(case when status = 'delivered' and vendor_payout_id is null then earning_paise else 0 end) as payable_paise")
            ->get()
            ->keyBy('vendor_id');

        $products = Product::query()
            ->whereIn('vendor_id', $ids)
            ->where(fn ($q) => $q->whereNull('status')->orWhere('status', '!=', 'archived'))
            ->groupBy('vendor_id')
            ->selectRaw('vendor_id, count(*) as products')
            ->selectRaw("sum(case when status = 'active' then 1 else 0 end) as live_products")
            ->selectRaw("sum(case when status = 'pending_review' then 1 else 0 end) as review_products")
            ->get()
            ->keyBy('vendor_id');

        $settings = $this->settings();
        $out = [];
        foreach ($vendors as $vendor) {
            $o = $orders->get($vendor->id);
            $p = $products->get($vendor->id);
            $out[$vendor->id] = [
                'commission_percent' => $this->rate($vendor, $settings),
                'orders' => (int) ($o->orders ?? 0),
                'pending_orders' => (int) ($o->pending_orders ?? 0),
                'sales_paise' => (int) ($o->sales_paise ?? 0),
                'commission_paise' => (int) ($o->commission_paise ?? 0),
                'earnings_paise' => (int) ($o->earnings_paise ?? 0),
                'payable_paise' => (int) ($o->payable_paise ?? 0),
                'products' => (int) ($p->products ?? 0),
                'live_products' => (int) ($p->live_products ?? 0),
                'review_products' => (int) ($p->review_products ?? 0),
            ];
        }

        return $out;
    }

    public function stats(Vendor $vendor): array
    {
        return $this->statsFor(collect([$vendor]))[$vendor->id];
    }

    /** What a vendor must fill in before the admin can approve the store. */
    public function checklist(Vendor $vendor): array
    {
        return [
            ['key' => 'profile', 'label' => 'Store profile & contact', 'ok' => (bool) ($vendor->store_name && $vendor->owner_name && $vendor->phone && $vendor->email)],
            ['key' => 'pickup', 'label' => 'Pickup address', 'ok' => (bool) ($vendor->pickup_address && $vendor->city && $vendor->pincode)],
            ['key' => 'fssai', 'label' => 'FSSAI licence number', 'ok' => (bool) $vendor->fssai_no],
            ['key' => 'bank', 'label' => 'Bank account for payouts', 'ok' => (bool) ($vendor->bank_account_no && $vendor->bank_ifsc)],
        ];
    }

    /**
     * Sales per day (non-cancelled vendor orders, by order date) for the last $days days.
     *
     * @param  Builder<VendorOrder>  $query
     */
    public function salesByDay(Builder $query, int $days, string $format): array
    {
        $from = now()->startOfDay()->subDays($days - 1);

        $rows = (clone $query)
            ->whereIn('vendor_orders.status', self::LIVE)
            ->join('orders', 'orders.id', '=', 'vendor_orders.order_id')
            ->where('orders.placed_at', '>=', $from)
            ->get(['vendor_orders.subtotal_paise', 'orders.placed_at']);

        $byDay = [];
        foreach ($rows as $row) {
            $key = Carbon::parse($row->placed_at)->toDateString();
            $byDay[$key] = ($byDay[$key] ?? 0) + (int) $row->subtotal_paise;
        }

        $out = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i);
            $out[] = ['label' => $day->format($format), 'paise' => $byDay[$day->toDateString()] ?? 0];
        }

        return $out;
    }

    // ------------------------------------------------------------------ JSON shapes

    /** Vendor profile. $effectiveRate: commission_percent is the rate in force (vendor panel) instead of the override (admin). */
    public function vendorPayload(Vendor $vendor, bool $effectiveRate = false): array
    {
        return [
            'id' => $vendor->id,
            'store_name' => $vendor->store_name,
            'owner_name' => $vendor->owner_name,
            'email' => $vendor->email,
            'phone' => (string) $vendor->phone,
            'city' => (string) $vendor->city,
            'pincode' => (string) $vendor->pincode,
            'pickup_address' => (string) $vendor->pickup_address,
            'gst_no' => (string) $vendor->gst_no,
            'pan_no' => (string) $vendor->pan_no,
            'fssai_no' => (string) $vendor->fssai_no,
            'description' => (string) $vendor->description,
            'status' => $vendor->status,
            'status_reason' => (string) $vendor->status_reason,
            'commission_percent' => $effectiveRate
                ? $this->rate($vendor)
                : ($vendor->commission_percent !== null ? $vendor->commission_percent + 0 : null),
            'bank' => [
                'account_name' => (string) $vendor->bank_account_name,
                'account_no' => (string) $vendor->bank_account_no,
                'ifsc' => (string) $vendor->bank_ifsc,
                'bank_name' => (string) $vendor->bank_name,
            ],
            'created_at' => $vendor->created_at?->toIso8601String(),
        ];
    }

    /** Vendor listing with its sizes and totals (min price, stock). */
    public function productPayload(Product $product): array
    {
        $variants = $product->relationLoaded('variants') ? $product->variants : $product->variants()->get();
        $active = $variants->where('is_active', true)->values();

        return [
            'id' => $product->id,
            'vendor_id' => $product->vendor_id,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => (string) $product->description,
            'category_id' => $product->category_id,
            'category' => $product->category ? ['id' => $product->category->id, 'name' => $product->category->name] : null,
            'image_url' => ImageUrl::resolve($product->image),
            'status' => $product->status ?? ($product->is_active ? 'active' : 'paused'),
            'review_note' => (string) $product->review_note,
            'submitted_at' => $product->submitted_at?->toIso8601String(),
            'variants' => $active->map(fn ($v) => [
                'id' => $v->id,
                'size_label' => $v->size_label,
                'sku' => $v->sku,
                'price_paise' => (int) $v->price_paise,
                'mrp_paise' => (int) ($v->mrp_paise ?: $v->price_paise),
                'stock' => (int) $v->stock,
            ])->all(),
            'min_price_paise' => $active->isNotEmpty() ? (int) $active->min('price_paise') : 0,
            'stock_total' => (int) $active->sum('stock'),
        ];
    }

    /** A seller's part of an order: only that seller's items and money. */
    public function orderPayload(VendorOrder $vo): array
    {
        $order = $vo->order;
        $items = $order->items->where('vendor_id', $vo->vendor_id)->values();

        return [
            'id' => $vo->id,
            'order_no' => $order->order_no,
            'vendor_id' => $vo->vendor_id,
            'customer_name' => $order->customer_name,
            'city' => $order->ship_city,
            'placed_at' => $order->placed_at?->toIso8601String(),
            'status' => $vo->status,
            'payment_status' => $order->payment_status,
            'settled' => $vo->vendor_payout_id !== null,
            'items' => $items->map(fn (OrderItem $i) => [
                'id' => $i->id,
                'name' => $i->name,
                'size_label' => $i->size_label,
                'qty' => (int) $i->qty,
                'total_paise' => (int) $i->total_paise,
                'image_url' => ImageUrl::resolve($i->image),
            ])->all(),
            'subtotal_paise' => (int) $vo->subtotal_paise,
            'commission_paise' => (int) $vo->commission_paise,
            'earning_paise' => (int) $vo->earning_paise,
            'address' => [
                'name' => $order->ship_name,
                'line1' => trim($order->ship_line1.($order->ship_line2 ? ', '.$order->ship_line2 : '')),
                'city' => $order->ship_city,
                'state' => $order->ship_state,
                'pincode' => $order->ship_pincode,
                'phone' => $order->ship_phone,
            ],
        ];
    }

    public function payoutPayload(VendorPayout $payout): array
    {
        return [
            'id' => $payout->id,
            'vendor_id' => $payout->vendor_id,
            'ref' => $payout->ref,
            'period' => $payout->period,
            'amount_paise' => (int) $payout->amount_paise,
            'status' => $payout->status,
            'paid_at' => $payout->paid_at?->toIso8601String(),
            'utr' => (string) $payout->utr,
        ];
    }

    /** Laravel paginator -> { data, meta } the panels' pager reads. */
    public function page(\Illuminate\Contracts\Pagination\LengthAwarePaginator $p, callable $map): array
    {
        return [
            'data' => collect($p->items())->map($map)->values()->all(),
            'meta' => [
                'current_page' => $p->currentPage(),
                'last_page' => $p->lastPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
            ],
        ];
    }

    // ------------------------------------------------------------------ visibility

    /** A vendor product is on the shop only when the listing is live AND the store is approved. */
    public function refreshVisibility(Product $product, ?Vendor $vendor = null): void
    {
        if ($product->vendor_id === null) {
            return;
        }

        $vendor ??= $product->vendor;
        $visible = $product->status === 'active' && $vendor && $vendor->isApproved();

        if ($product->is_active !== $visible) {
            $product->forceFill(['is_active' => $visible])->save();
        }
    }

    public function refreshVendorVisibility(Vendor $vendor): void
    {
        foreach ($vendor->products()->get() as $product) {
            $this->refreshVisibility($product, $vendor);
        }
    }

    // ------------------------------------------------------------------ orders

    /**
     * Keeps the per-seller parts of an order in step with the order. Safe to call any number of times.
     * - once the order is confirmed (paid, or Cash on Delivery) each seller gets a vendor order;
     * - cancelling / shipping / delivering the whole order is passed on to the sellers' parts.
     */
    public function syncOrder(Order $order): void
    {
        $order = $order->fresh();
        if (! $order) {
            return;
        }

        if ($order->status === 'cancelled') {
            VendorOrder::where('order_id', $order->id)->where('status', '!=', 'cancelled')->update(['status' => 'cancelled']);

            return;
        }

        if ($order->status === 'pending') {
            return;
        }

        $this->createVendorOrders($order);

        if ($order->status === 'delivered') {
            VendorOrder::where('order_id', $order->id)->where('status', '!=', 'cancelled')->update(['status' => 'delivered']);
        } elseif ($order->status === 'shipped') {
            VendorOrder::where('order_id', $order->id)->whereIn('status', ['pending', 'confirmed', 'packed'])->update(['status' => 'shipped']);
        }
    }

    private function createVendorOrders(Order $order): void
    {
        $groups = OrderItem::where('order_id', $order->id)
            ->whereNotNull('vendor_id')
            ->get()
            ->groupBy('vendor_id');

        if ($groups->isEmpty()) {
            return;
        }

        $existing = VendorOrder::where('order_id', $order->id)->pluck('vendor_id')->map(fn ($id) => (int) $id)->all();
        $settings = $this->settings();

        foreach ($groups as $vendorId => $items) {
            if (in_array((int) $vendorId, $existing, true)) {
                continue;
            }
            $vendor = Vendor::find($vendorId);
            if (! $vendor) {
                continue;
            }

            $subtotal = (int) $items->sum('total_paise');
            $rate = $this->rate($vendor, $settings);
            $commission = (int) round($subtotal * $rate / 100);

            try {
                VendorOrder::create([
                    'order_id' => $order->id,
                    'vendor_id' => $vendor->id,
                    'status' => 'pending',
                    'subtotal_paise' => $subtotal,
                    'commission_percent' => $rate,
                    'commission_paise' => $commission,
                    'earning_paise' => $subtotal - $commission,
                ]);
            } catch (QueryException $e) {
                // Another request created it at the same moment (unique order_id + vendor_id): nothing to do.
                if (! VendorOrder::where('order_id', $order->id)->where('vendor_id', $vendor->id)->exists()) {
                    throw $e;
                }
            }
        }
    }

    /** True when every item of the order was sold by this one vendor. */
    public function onlySeller(Order $order, int $vendorId): bool
    {
        return ! OrderItem::where('order_id', $order->id)
            ->where(fn ($q) => $q->whereNull('vendor_id')->orWhere('vendor_id', '!=', $vendorId))
            ->exists();
    }

    /** True when the order holds only marketplace items and every seller has shipped (or cancelled) its part. */
    public function allSellersShipped(Order $order): bool
    {
        if (OrderItem::where('order_id', $order->id)->whereNull('vendor_id')->exists()) {
            return false;
        }

        $parts = VendorOrder::where('order_id', $order->id)->pluck('status');

        return $parts->isNotEmpty()
            && $parts->contains(fn ($s) => $s === 'shipped' || $s === 'delivered')
            && $parts->every(fn ($s) => in_array($s, ['shipped', 'delivered', 'cancelled'], true));
    }

    /** "1 – 7 Oct 2026" style label for a payout. */
    public function periodLabel(Carbon $from, Carbon $to): string
    {
        if ($from->isSameDay($to)) {
            return $to->format('j M Y');
        }
        if ($from->isSameMonth($to)) {
            return $from->format('j').' – '.$to->format('j M Y');
        }
        if ($from->isSameYear($to)) {
            return $from->format('j M').' – '.$to->format('j M Y');
        }

        return $from->format('j M Y').' – '.$to->format('j M Y');
    }
}
