<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Single source of truth for money maths. Everything is integer paise.
 */
class PricingService
{
    public const METHODS = ['standard', 'express'];

    /**
     * @return array{subtotal_paise:int,shipping_paise:int,tax_paise:int,discount_paise:int,total_paise:int}
     */
    public function totals(int $subtotalPaise, string $shippingMethod = 'standard'): array
    {
        $subtotal = max(0, $subtotalPaise);
        $shipping = $subtotal > 0 ? $this->shippingRate($subtotal, $shippingMethod) : 0;
        $tax = (int) round($subtotal * $this->gstPercent() / 100);
        $discount = 0;

        return [
            'subtotal_paise' => $subtotal,
            'shipping_paise' => $shipping,
            'tax_paise' => $tax,
            'discount_paise' => $discount,
            'total_paise' => $subtotal + $shipping + $tax - $discount,
        ];
    }

    /**
     * Delivery options with their prices for the given subtotal.
     * A zero subtotal still returns the configured prices.
     *
     * @return array<int, array{code:string,label:string,price_paise:int,days:string}>
     */
    public function methods(int $subtotalPaise): array
    {
        $subtotal = max(0, $subtotalPaise);

        return [
            [
                'code' => 'standard',
                'label' => 'Standard delivery',
                'price_paise' => $this->shippingRate($subtotal, 'standard'),
                'days' => '3 to 5',
            ],
            [
                'code' => 'express',
                'label' => 'Express delivery',
                'price_paise' => $this->shippingRate($subtotal, 'express'),
                'days' => '1 to 2',
            ],
        ];
    }

    private function shippingRate(int $subtotal, string $method): int
    {
        if ($method === 'express') {
            return $this->intSetting('shipping_express_paise', 12000);
        }

        $freeAbove = $this->intSetting('free_shipping_above_paise', 0);
        if ($freeAbove > 0 && $subtotal >= $freeAbove) {
            return 0;
        }

        return $this->intSetting('shipping_standard_paise', 6000);
    }

    private function gstPercent(): float
    {
        $value = Setting::get('gst_percent', 5);

        return ($value === null || $value === '') ? 5.0 : max(0.0, (float) $value);
    }

    private function intSetting(string $key, int $default): int
    {
        $value = Setting::get($key, $default);

        return ($value === null || $value === '') ? $default : max(0, (int) $value);
    }
}
