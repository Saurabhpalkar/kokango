<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Concerns\ResolvesPerPage;
use App\Http\Controllers\Controller;
use App\Http\Resources\ShipmentResource;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\OrderService;
use App\Services\Shipping\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ShipmentController extends Controller
{
    use ResolvesPerPage;

    public function __construct(
        private ShippingService $shipping,
        private OrderService $orders,
    ) {
    }

    public function index(Request $request)
    {
        $query = Shipment::query()->with('order');

        $status = $request->query('status');
        if (is_string($status) && in_array($status, ShippingService::STATUSES, true)) {
            $query->where('status', $status);
        }

        return ShipmentResource::collection(
            $query->orderByDesc('id')->paginate($this->perPage($request))
        );
    }

    /** Create the shipment with the provider for a paid order. */
    public function store(Request $request, string $orderNo): JsonResponse
    {
        $order = Order::where('order_no', $orderNo)->firstOrFail();

        $shipment = DB::transaction(function () use ($order) {
            $o = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($o->payment_status !== 'paid') {
                abort(409, 'This order has not been paid yet.');
            }
            if ($o->status === 'cancelled') {
                abort(409, 'This order has been cancelled.');
            }

            $shipments = Shipment::where('order_id', $o->id)->lockForUpdate()->get();
            if ($shipments->contains(fn (Shipment $s) => ! in_array($s->status, ['pending', 'cancelled', 'returned'], true))) {
                abort(409, 'This order already has an active shipment.');
            }

            $existing = $shipments->firstWhere('status', 'pending');
            $shipment = $this->shipping->createForOrder($o, $existing);

            if (in_array($o->status, ['pending', 'confirmed'], true)) {
                $o->forceFill(['status' => 'processing'])->save();
            }

            return $shipment;
        });

        return response()->json(
            ['data' => (new ShipmentResource($shipment->load('order')))->resolve($request)],
            201
        );
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(ShippingService::STATUSES)],
        ]);

        $shipment = Shipment::findOrFail($id);

        DB::transaction(function () use ($shipment, $data) {
            $shipment->forceFill(['status' => $data['status']])->save();
            $this->orders->syncStatusFromShipment($shipment);
        });

        return response()->json(['data' => (new ShipmentResource($shipment->fresh('order')))->resolve($request)]);
    }

    public function sync(Request $request, int $id): JsonResponse
    {
        $shipment = Shipment::findOrFail($id);

        $this->shipping->sync($shipment);
        $this->orders->syncStatusFromShipment($shipment);

        return response()->json(['data' => (new ShipmentResource($shipment->fresh('order')))->resolve($request)]);
    }
}
