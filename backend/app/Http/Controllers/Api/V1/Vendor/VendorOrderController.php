<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Services\MarketplaceService;
use App\Services\OrderService;
use App\Support\Search;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** The seller's part of customer orders, and its fulfilment steps (confirm, pack, ship, or reject). */
class VendorOrderController extends Controller
{
    /** What the seller may move an order to, from each state. */
    private const NEXT = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['packed'],
        'packed' => ['shipped'],
    ];

    public function __construct(
        private MarketplaceService $marketplace,
        private OrderService $orders,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);
        $query = VendorOrder::query()
            ->where('vendor_orders.vendor_id', $vendor->id)
            ->join('orders', 'orders.id', '=', 'vendor_orders.order_id')
            ->select('vendor_orders.*')
            ->with('order.items');

        $status = $request->query('status');
        if (is_string($status) && in_array($status, VendorOrder::STATUSES, true)) {
            $query->where('vendor_orders.status', $status);
        }

        if (($search = Search::term($request)) !== '') {
            Search::where($query, ['orders.order_no', 'orders.customer_name'], $search);
        }

        $page = $query->orderByDesc('orders.placed_at')->orderByDesc('vendor_orders.id')->paginate(8)->withQueryString();

        return response()->json($this->marketplace->page($page, fn (VendorOrder $vo) => $this->marketplace->orderPayload($vo)));
    }

    public function show(Request $request, string $orderNo): JsonResponse
    {
        return response()->json(['data' => $this->marketplace->orderPayload($this->find($request, $orderNo))]);
    }

    public function updateStatus(Request $request, string $orderNo): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['confirmed', 'packed', 'shipped', 'cancelled'])],
        ]);
        $vendor = $this->vendor($request);
        $vo = $this->find($request, $orderNo);
        $to = $data['status'];

        if ($vo->order->status === 'cancelled' && $to !== 'cancelled') {
            abort(409, 'This order was cancelled.');
        }
        if (! in_array($to, self::NEXT[$vo->status] ?? [], true)) {
            abort(409, 'This order cannot be moved to "'.$to.'" now.');
        }

        if ($to === 'cancelled') {
            // Rejecting cancels the customer's order (refund + stock back), so only when it is all this seller's.
            if (! $this->marketplace->onlySeller($vo->order, $vendor->id)) {
                abort(409, 'This order also has items from other sellers. Please contact Kokango support to cancel it.');
            }
            $this->orders->cancel($vo->order);
        } else {
            DB::transaction(function () use ($vo, $to) {
                $locked = VendorOrder::whereKey($vo->id)->lockForUpdate()->firstOrFail();
                if (! in_array($to, self::NEXT[$locked->status] ?? [], true)) {
                    abort(409, 'This order was just updated. Please refresh.');
                }
                $locked->forceFill(['status' => $to])->save();
            });

            if ($to === 'shipped') {
                $this->markOrderShipped($vo->order);
            }
        }

        return response()->json(['data' => $this->marketplace->orderPayload($vo->fresh(['order.items']))]);
    }

    /** When every seller of a marketplace-only order has shipped, the customer's order is shipped too (and emailed). */
    private function markOrderShipped(Order $order): void
    {
        if (! $this->marketplace->allSellersShipped($order)) {
            return;
        }

        $before = null;
        $changed = DB::transaction(function () use ($order, &$before) {
            $o = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (! in_array($o->status, ['confirmed', 'processing'], true)) {
                return false;
            }
            $before = $o->status;
            $o->forceFill(['status' => 'shipped'])->save();

            return true;
        });

        if ($changed) {
            $this->orders->afterStatusChange($order->fresh(), $before);
        }
    }

    private function vendor(Request $request): Vendor
    {
        return $request->attributes->get('vendor');
    }

    private function find(Request $request, string $orderNo): VendorOrder
    {
        $vo = VendorOrder::query()
            ->where('vendor_id', $this->vendor($request)->id)
            ->whereHas('order', fn ($q) => $q->where('order_no', $orderNo))
            ->with('order.items')
            ->first();

        if (! $vo) {
            abort(404, 'Order not found.');
        }

        return $vo;
    }
}
