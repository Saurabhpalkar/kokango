<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\CreateVendorRequest;
use App\Http\Requests\Vendor\VendorProfileRequest;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\VendorPayout;
use App\Services\MarketplaceService;
use App\Support\Search;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/** Admin: seller accounts (list, detail, create, edit, approve / reject / suspend / reactivate). */
class VendorController extends Controller
{
    public function __construct(private MarketplaceService $marketplace)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Vendor::query();

        $status = $request->query('status');
        if (is_string($status) && in_array($status, Vendor::STATUSES, true)) {
            $query->where('status', $status);
        }
        if (($search = Search::term($request)) !== '') {
            Search::where($query, ['store_name', 'owner_name', 'email', 'city'], $search);
        }

        $page = $query->orderByDesc('created_at')->orderByDesc('id')->paginate(8)->withQueryString();
        $stats = $this->marketplace->statsFor(collect($page->items()));

        $counts = ['all' => Vendor::count()] + array_fill_keys(Vendor::STATUSES, 0);
        foreach (Vendor::query()->groupBy('status')->selectRaw('status, count(*) as n')->pluck('n', 'status') as $key => $n) {
            $counts[$key] = (int) $n;
        }

        return response()->json(
            $this->marketplace->page($page, fn (Vendor $v) => $this->row($v, $stats[$v->id])) + ['counts' => $counts]
        );
    }

    public function show(int $id): JsonResponse
    {
        $vendor = Vendor::findOrFail($id);
        $orders = VendorOrder::query()
            ->where('vendor_orders.vendor_id', $vendor->id)
            ->join('orders', 'orders.id', '=', 'vendor_orders.order_id')
            ->select('vendor_orders.*')
            ->with('order.items')
            ->orderByDesc('orders.placed_at')
            ->limit(6)
            ->get();

        return response()->json(['data' => [
            'vendor' => $this->marketplace->vendorPayload($vendor),
            'stats' => $this->marketplace->stats($vendor),
            'checklist' => $this->marketplace->checklist($vendor),
            'products' => Product::where('vendor_id', $vendor->id)->where('status', '!=', 'archived')
                ->with(['category:id,name', 'variants'])->orderByDesc('id')->get()
                ->map(fn ($p) => $this->marketplace->productPayload($p))->all(),
            'orders' => $orders->map(fn ($vo) => $this->marketplace->orderPayload($vo))->all(),
            'payouts' => VendorPayout::where('vendor_id', $vendor->id)->orderByDesc('id')->get()
                ->map(fn ($p) => $this->marketplace->payoutPayload($p))->all(),
            'sales_by_day' => $this->marketplace->salesByDay(VendorOrder::where('vendor_orders.vendor_id', $vendor->id), 14, 'j M'),
        ]]);
    }

    public function store(CreateVendorRequest $request): JsonResponse
    {
        $fields = $request->vendorFields();
        $approve = (bool) $request->validated('approve_now');

        $vendor = DB::transaction(function () use ($fields, $request, $approve) {
            $user = User::create([
                'name' => $fields['owner_name'],
                'email' => $fields['email'],
                'password' => $request->validated('password'),
                'is_active' => true,
            ]);
            Role::findOrCreate('vendor', 'web');
            $user->assignRole('vendor');

            return Vendor::create($fields + [
                'user_id' => $user->id,
                'status' => $approve ? 'approved' : 'pending',
            ]);
        });

        return response()->json(['data' => $this->marketplace->vendorPayload($vendor)], 201);
    }

    public function update(VendorProfileRequest $request, int $id): JsonResponse
    {
        $vendor = Vendor::findOrFail($id);
        $fields = $request->vendorFields();

        DB::transaction(function () use ($vendor, $fields) {
            $vendor->update($fields);
            $vendor->user()->update(['name' => $fields['owner_name'], 'email' => $fields['email']]);
        });

        return response()->json(['data' => $this->marketplace->vendorPayload($vendor->fresh())]);
    }

    public function act(Request $request, int $id, string $action): JsonResponse
    {
        $vendor = Vendor::findOrFail($id);
        $reason = trim((string) $request->input('reason', ''));
        if (mb_strlen($reason) > 255) {
            throw ValidationException::withMessages(['reason' => ['Keep the reason under 255 characters.']]);
        }

        DB::transaction(function () use ($vendor, $action, $reason) {
            switch ($action) {
                case 'approve':
                    if (collect($this->marketplace->checklist($vendor))->contains(fn ($c) => ! $c['ok'])) {
                        abort(409, 'Complete the approval checklist first.');
                    }
                    $vendor->update(['status' => 'approved', 'status_reason' => null]);
                    break;

                case 'reject':
                    if ($reason === '') {
                        throw ValidationException::withMessages(['reason' => ['Add a reason so the vendor knows what to fix.']]);
                    }
                    $vendor->update(['status' => 'rejected', 'status_reason' => $reason]);
                    break;

                case 'suspend':
                    if ($reason === '') {
                        throw ValidationException::withMessages(['reason' => ['Add a reason for the suspension.']]);
                    }
                    $vendor->update(['status' => 'suspended', 'status_reason' => $reason]);
                    // Hidden from the shop while suspended; reactivating puts them back.
                    Product::where('vendor_id', $vendor->id)->where('status', 'active')->update(['status' => 'paused']);
                    break;

                case 'reactivate':
                    $vendor->update(['status' => 'approved', 'status_reason' => null]);
                    Product::where('vendor_id', $vendor->id)->where('status', 'paused')->update(['status' => 'active']);
                    break;
            }
        });

        $this->marketplace->refreshVendorVisibility($vendor->fresh());

        return response()->json(['data' => $this->marketplace->vendorPayload($vendor->fresh())]);
    }

    /** List row: profile + figures; commission_percent is the rate in force, the override is separate. */
    private function row(Vendor $vendor, array $stats): array
    {
        $payload = $this->marketplace->vendorPayload($vendor);

        return array_merge($payload, ['commission_percent_override' => $payload['commission_percent']], $stats);
    }
}
