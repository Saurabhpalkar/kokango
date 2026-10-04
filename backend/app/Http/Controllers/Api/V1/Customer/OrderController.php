<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\V1\Concerns\ResolvesPerPage;
use App\Http\Controllers\Concerns\ResolvesViewer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\TrackOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ResolvesPerPage;
    use ResolvesViewer;

    public function __construct(private OrderService $orders)
    {
    }

    public function index(Request $request)
    {
        $user = $this->viewer();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $paginator = Order::where('user_id', $user->id)
            ->with(['items', 'payment', 'shipment'])
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->paginate($this->perPage($request));

        return OrderResource::collection($paginator);
    }

    public function show(Request $request, string $orderNo): JsonResponse
    {
        $token = $request->query('token');
        $order = $this->orders->findForViewer($orderNo, $this->viewer(), is_string($token) ? $token : null);

        return response()->json(['data' => (new OrderResource($order))->resolve($request)]);
    }

    public function cancel(Request $request, string $orderNo): JsonResponse
    {
        $user = $this->viewer();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $order = $this->orders->findForViewer($orderNo, $user, null);
        $order = $this->orders->cancel($order);

        return response()->json(['data' => (new OrderResource($order))->resolve($request)]);
    }

    /** Guest lookup by order number + email. */
    public function track(TrackOrderRequest $request): JsonResponse
    {
        $data = $request->validated();

        $order = Order::where('order_no', strtoupper(trim($data['order_no'])))
            ->whereRaw('LOWER(customer_email) = ?', [strtolower(trim($data['email']))])
            ->first();

        if (! $order) {
            abort(404, 'We could not find an order with those details.');
        }

        return response()->json(['data' => (new OrderResource($order))->resolve($request)]);
    }
}
