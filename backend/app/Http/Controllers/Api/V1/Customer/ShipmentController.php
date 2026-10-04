<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Concerns\ResolvesViewer;
use App\Http\Controllers\Controller;
use App\Http\Resources\ShipmentResource;
use App\Services\OrderService;
use App\Services\Shipping\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    use ResolvesViewer;

    public function __construct(
        private OrderService $orders,
        private ShippingService $shipping,
    ) {
    }

    public function track(Request $request, string $orderNo): JsonResponse
    {
        $token = $request->query('token');
        $order = $this->orders->findForViewer($orderNo, $this->viewer(), is_string($token) ? $token : null);

        $shipment = $order->shipments()->latest('id')->first();
        if ($shipment && $shipment->status !== 'cancelled') {
            $this->shipping->syncIfStale($shipment);
            $this->orders->syncStatusFromShipment($shipment);
            $order->refresh();
        } else {
            $shipment = null;
        }

        $order->unsetRelation('shipment');

        if ($shipment) {
            $shipment->setRelation('order', $order);
        }

        return response()->json(['data' => [
            'shipment' => $shipment ? (new ShipmentResource($shipment))->resolve($request) : null,
            'timeline' => $this->orders->timeline($order),
        ]]);
    }
}
