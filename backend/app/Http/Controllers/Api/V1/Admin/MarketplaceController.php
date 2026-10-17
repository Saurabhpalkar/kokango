<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ImageUrl;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\VendorPayout;
use App\Services\MarketplaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Admin: marketplace overview, commission settings and seller payouts. */
class MarketplaceController extends Controller
{
    public function __construct(private MarketplaceService $marketplace)
    {
    }

    public function overview(): JsonResponse
    {
        $vendors = Vendor::all();
        $stats = $this->marketplace->statsFor($vendors);
        $rows = $vendors->map(fn (Vendor $v) => ['id' => $v->id, 'store_name' => $v->store_name, 'city' => (string) $v->city, 'status' => $v->status] + $stats[$v->id]);
        $pending = Product::whereNotNull('vendor_id')->where('status', 'pending_review')->with('vendor:id,store_name')
            ->orderByDesc('submitted_at')->get();

        return response()->json(['data' => [
            'stats' => [
                'vendors' => $vendors->count(),
                'approved_vendors' => $vendors->where('status', 'approved')->count(),
                'pending_vendors' => $vendors->where('status', 'pending')->count(),
                'pending_products' => $pending->count(),
                'gmv_paise' => (int) $rows->sum('sales_paise'),
                'commission_paise' => (int) $rows->sum('commission_paise'),
                'payable_paise' => (int) $rows->sum('payable_paise'),
                'orders' => VendorOrder::count(),
            ],
            'top_vendors' => $rows->where('sales_paise', '>', 0)->sortByDesc('sales_paise')->take(5)->values()->all(),
            'pending_vendors' => $rows->where('status', 'pending')->values()->all(),
            'pending_products' => $pending->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'image_url' => ImageUrl::resolve($p->image),
                'vendor_name' => $p->vendor?->store_name,
                'submitted_at' => $p->submitted_at?->toIso8601String(),
            ])->all(),
            'sales_by_day' => $this->marketplace->salesByDay(VendorOrder::query(), 14, 'j M'),
        ]]);
    }

    public function settings(): JsonResponse
    {
        return response()->json(['data' => $this->marketplace->settings()]);
    }

    public function saveSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'default_commission' => ['required', 'numeric', 'min:0', 'max:50'],
            'min_payout_paise' => ['required', 'integer', 'min:0', 'max:100000000'],
            'auto_approve_products' => ['nullable', 'boolean'],
        ], [
            'default_commission.*' => 'Enter 0–50',
        ]);

        return response()->json(['data' => $this->marketplace->saveSettings($data)]);
    }

    public function payouts(): JsonResponse
    {
        $settings = $this->marketplace->settings();
        $vendors = Vendor::whereIn('status', ['approved', 'suspended'])->orderBy('store_name')->get();
        $stats = $this->marketplace->statsFor($vendors);
        $names = Vendor::pluck('store_name', 'id');
        $payouts = VendorPayout::orderByDesc('id')->get();
        $commission = (int) VendorOrder::where('status', '!=', 'cancelled')->sum('commission_paise');

        return response()->json(['data' => [
            'summary' => [
                'payable_paise' => (int) collect($stats)->sum('payable_paise'),
                'in_progress_paise' => (int) $payouts->whereIn('status', ['scheduled', 'processing'])->sum('amount_paise'),
                'paid_paise' => (int) $payouts->where('status', 'paid')->sum('amount_paise'),
                'commission_paise' => $commission,
            ],
            'vendors' => $vendors->map(fn (Vendor $v) => [
                'id' => $v->id,
                'store_name' => $v->store_name,
                'city' => (string) $v->city,
                'status' => $v->status,
                'min_ok' => $stats[$v->id]['payable_paise'] > 0 && $stats[$v->id]['payable_paise'] >= $settings['min_payout_paise'],
            ] + $stats[$v->id])->values()->all(),
            'payouts' => $payouts->map(fn ($p) => $this->marketplace->payoutPayload($p) + ['vendor_name' => $names[$p->vendor_id] ?? '—'])->all(),
            'settings' => $settings,
        ]]);
    }

    /** Pays out a seller's delivered orders that are not in a payout yet. */
    public function createPayout(Request $request): JsonResponse
    {
        $data = $request->validate(['vendor_id' => ['required', 'integer', Rule::exists('vendors', 'id')]]);
        $vendor = Vendor::findOrFail($data['vendor_id']);
        $settings = $this->marketplace->settings();

        $payout = DB::transaction(function () use ($vendor, $settings) {
            $orders = VendorOrder::where('vendor_id', $vendor->id)
                ->where('status', 'delivered')
                ->whereNull('vendor_payout_id')
                ->with('order:id,placed_at')
                ->lockForUpdate()
                ->get();

            $amount = (int) $orders->sum('earning_paise');
            if ($amount <= 0) {
                abort(409, 'Nothing to pay out for this vendor yet.');
            }
            if ($amount < $settings['min_payout_paise']) {
                abort(409, 'Balance is below the minimum payout amount.');
            }
            if (! $vendor->bank_account_no || ! $vendor->bank_ifsc) {
                abort(409, 'This vendor has no bank account on file.');
            }

            $dates = $orders->map(fn ($vo) => Carbon::parse($vo->order->placed_at ?? $vo->created_at));
            $payout = VendorPayout::create([
                'vendor_id' => $vendor->id,
                'period' => $this->marketplace->periodLabel($dates->min(), $dates->max()),
                'amount_paise' => $amount,
                'status' => 'scheduled',
            ]);
            $payout->forceFill(['ref' => sprintf('PO-%s-%03d', now()->format('Y'), $payout->id)])->save();

            VendorOrder::whereIn('id', $orders->pluck('id'))->update(['vendor_payout_id' => $payout->id]);

            return $payout;
        });

        return response()->json(['data' => $this->marketplace->payoutPayload($payout)], 201);
    }

    public function payoutStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['processing', 'paid'])],
            'utr' => ['nullable', 'string', 'max:50'],
        ]);
        $utr = trim((string) ($data['utr'] ?? ''));
        if ($data['status'] === 'paid' && $utr === '') {
            throw ValidationException::withMessages(['utr' => ['Enter the bank reference (UTR) for this payment.']]);
        }

        $payout = DB::transaction(function () use ($id, $data, $utr) {
            $payout = VendorPayout::lockForUpdate()->findOrFail($id);
            if ($payout->status === 'paid') {
                abort(409, 'This payout is already paid.');
            }
            if ($data['status'] === 'processing' && $payout->status !== 'scheduled') {
                abort(409, 'This payout is already processing.');
            }

            $payout->forceFill($data['status'] === 'paid'
                ? ['status' => 'paid', 'utr' => $utr, 'paid_at' => now()]
                : ['status' => 'processing'])->save();

            return $payout;
        });

        return response()->json(['data' => $this->marketplace->payoutPayload($payout)]);
    }
}
