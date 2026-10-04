<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Concerns\ResolvesPerPage;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderSummaryResource;
use App\Models\Order;
use App\Support\Search;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    use ResolvesPerPage;

    public const STATUSES = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];

    public function __construct(private OrderService $orders)
    {
    }

    public function index(Request $request)
    {
        $query = Order::query()->with(['items', 'payment', 'shipment']);

        $status = $request->query('status');
        if (is_string($status) && in_array($status, self::STATUSES, true)) {
            $query->where('status', $status);
        }

        if (($search = Search::term($request)) !== '') {
            Search::where($query, ['orders.order_no', 'orders.customer_name', 'orders.customer_email'], $search);
        }

        $paginator = $query->orderByDesc('placed_at')->orderByDesc('id')->paginate($this->perPage($request));

        return OrderSummaryResource::collection($paginator);
    }

    public function show(Request $request, string $orderNo): JsonResponse
    {
        $order = Order::where('order_no', $orderNo)->firstOrFail();

        return response()->json(['data' => (new OrderResource($order))->resolve($request)]);
    }

    public function updateStatus(Request $request, string $orderNo): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
        ]);

        $order = Order::where('order_no', $orderNo)->firstOrFail();
        $status = $data['status'];

        if ($status === 'cancelled') {
            $order = $this->orders->cancel($order);
        } else {
            DB::transaction(function () use ($order, $status) {
                $o = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                if ($o->status === 'cancelled') {
                    abort(409, 'A cancelled order cannot be changed.');
                }
                // Fulfilment states only make sense for money received (a failed/refunded order can still go back to "pending").
                if ($status !== 'pending' && $o->payment_status !== 'paid') {
                    abort(409, 'This order has not been paid, so it cannot be moved to "'.$status.'".');
                }
                $o->forceFill(['status' => $status])->save();
            });
            $order = $order->fresh();
        }

        return response()->json(['data' => (new OrderResource($order))->resolve($request)]);
    }
}
