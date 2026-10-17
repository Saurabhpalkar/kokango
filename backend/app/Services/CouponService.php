<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Validation\ValidationException;

/**
 * Coupon rules: which code is valid for which order, and how much it takes off (always computed here, never by the client).
 */
class CouponService
{
    /**
     * The coupon for $code if it can be used for this subtotal (and customer), else a 422 on "coupon_code".
     * Pass $lock = true inside the order transaction so two checkouts cannot both take the last use.
     */
    public function resolve(?string $code, int $subtotalPaise, ?string $customerEmail = null, bool $lock = false): ?Coupon
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return null;
        }

        $query = Coupon::where('code', $code);
        $coupon = $lock ? $query->lockForUpdate()->first() : $query->first();

        $fail = fn (string $message) => throw ValidationException::withMessages(['coupon_code' => [$message]]);

        if (! $coupon || ! $coupon->is_active) {
            $fail('This coupon code is not valid.');
        }
        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            $fail('This coupon is not active yet.');
        }
        if ($coupon->ends_at && $coupon->ends_at->isPast()) {
            $fail('This coupon has expired.');
        }
        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            $fail('This coupon has been fully used.');
        }
        if ($subtotalPaise < $coupon->min_subtotal_paise) {
            $fail('Add items worth Rs '.number_format($coupon->min_subtotal_paise / 100).' or more to use this coupon.');
        }
        if ($coupon->once_per_customer && $customerEmail) {
            $used = Order::where('coupon_code', $coupon->code)
                ->whereRaw('LOWER(customer_email) = ?', [mb_strtolower($customerEmail)])
                ->whereNotIn('status', ['cancelled'])
                ->where('payment_status', '!=', 'failed')
                ->exists();
            if ($used) {
                $fail('You have already used this coupon.');
            }
        }

        return $coupon;
    }

    /** Discount in paise for this subtotal (never more than the subtotal). */
    public function discount(Coupon $coupon, int $subtotalPaise): int
    {
        if ($coupon->type === 'fixed') {
            $amount = $coupon->value;
        } else {
            $amount = (int) round($subtotalPaise * min(90, $coupon->value) / 100);
            if ($coupon->max_discount_paise) {
                $amount = min($amount, $coupon->max_discount_paise);
            }
        }

        return max(0, min($amount, $subtotalPaise));
    }

    /** An order that will not go ahead (cancelled, payment failed) gives its coupon use back. */
    public function release(Order $order): void
    {
        if ($order->coupon_code) {
            Coupon::where('code', $order->coupon_code)->where('used_count', '>', 0)->decrement('used_count');
        }
    }
}
