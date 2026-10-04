<?php

namespace App\Http\Resources;

use App\Helpers\ImageUrl;
use App\Models\Cart;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Cart */
class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = self::items($this->resource);
        $subtotal = array_sum(array_column($items, 'line_total_paise'));
        $totals = app(PricingService::class)->totals((int) $subtotal, 'standard');

        return [
            'token' => $this->token,
            'count' => (int) array_sum(array_column($items, 'qty')),
            'items' => $items,
            'subtotal_paise' => (int) $totals['subtotal_paise'],
            'shipping_paise' => (int) $totals['shipping_paise'],
            'tax_paise' => (int) $totals['tax_paise'],
            'total_paise' => (int) $totals['total_paise'],
        ];
    }

    /**
     * Cart line items in API shape (also usable by the checkout quote).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function items(Cart $cart): array
    {
        $cart->loadMissing('items.variant.product');

        return $cart->items
            ->filter(fn ($item) => $item->variant && $item->variant->product)
            ->map(function ($item) {
                $variant = $item->variant;
                $product = $variant->product;

                return [
                    'id' => $item->id,
                    'variant_id' => $variant->id,
                    'product_id' => $product->id,
                    'slug' => $product->slug,
                    'name' => $product->name,
                    'size_label' => $variant->size_label,
                    'image_url' => ImageUrl::resolve($product->image),
                    'unit_price_paise' => (int) $variant->price_paise,
                    'qty' => (int) $item->qty,
                    'line_total_paise' => (int) $variant->price_paise * (int) $item->qty,
                    'stock' => (int) $variant->stock,
                ];
            })
            ->values()
            ->all();
    }
}
