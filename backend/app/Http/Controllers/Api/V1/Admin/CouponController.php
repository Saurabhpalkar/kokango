<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Admin: discount codes. */
class CouponController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Coupon::orderByDesc('id')->get()->map(fn ($c) => $this->row($c))->all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $coupon = Coupon::create($this->validated($request));

        return response()->json(['data' => $this->row($coupon)], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->update($this->validated($request, $coupon));

        return response()->json(['data' => $this->row($coupon->fresh())]);
    }

    public function destroy(int $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);

        // A coupon that was used stays for the order history; it is switched off instead.
        if (Order::where('coupon_code', $coupon->code)->exists()) {
            $coupon->update(['is_active' => false]);

            return response()->json(['message' => 'This coupon was used, so it was switched off instead of deleted.', 'data' => $this->row($coupon->fresh())]);
        }

        $coupon->delete();

        return response()->json(['message' => 'Coupon deleted.']);
    }

    private function validated(Request $request, ?Coupon $coupon = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('coupons', 'code')->ignore($coupon?->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => ['required', 'integer', 'min:1', $request->input('type') === 'percent' ? 'max:90' : 'max:100000000'],
            'min_subtotal_paise' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'max_discount_paise' => ['nullable', 'integer', 'min:1', 'max:100000000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'once_per_customer' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'code.regex' => 'Use letters, numbers, - or _ only (no spaces).',
            'code.unique' => 'This code already exists.',
            'value.max' => 'A percentage discount can be at most 90%.',
        ]);

        return $data + ['min_subtotal_paise' => $data['min_subtotal_paise'] ?? 0, 'once_per_customer' => $data['once_per_customer'] ?? true, 'is_active' => $data['is_active'] ?? true];
    }

    private function row(Coupon $c): array
    {
        $now = now();
        $state = ! $c->is_active ? 'off'
            : ($c->starts_at && $c->starts_at->gt($now) ? 'scheduled'
            : ($c->ends_at && $c->ends_at->lt($now) ? 'expired'
            : ($c->usage_limit !== null && $c->used_count >= $c->usage_limit ? 'used_up' : 'active')));

        return [
            'id' => $c->id,
            'code' => $c->code,
            'description' => (string) $c->description,
            'type' => $c->type,
            'value' => (int) $c->value,
            'min_subtotal_paise' => (int) $c->min_subtotal_paise,
            'max_discount_paise' => $c->max_discount_paise,
            'starts_at' => $c->starts_at?->toIso8601String(),
            'ends_at' => $c->ends_at?->toIso8601String(),
            'usage_limit' => $c->usage_limit,
            'used_count' => (int) $c->used_count,
            'once_per_customer' => (bool) $c->once_per_customer,
            'is_active' => (bool) $c->is_active,
            'state' => $state,
        ];
    }
}
