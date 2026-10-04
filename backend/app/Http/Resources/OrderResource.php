<?php

namespace App\Http\Resources;

use App\Services\OrderService;
use App\Support\Presenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full Order shape from docs/API.md.
 * Call ->withToken() to include the public token (only done for POST /checkout).
 *
 * @mixin \App\Models\Order
 */
class OrderResource extends JsonResource
{
    protected bool $includeToken = false;

    public function withToken(bool $include = true): static
    {
        $this->includeToken = $include;

        return $this;
    }

    public function toArray(Request $request): array
    {
        $order = $this->resource;
        $order->loadMissing(['items', 'payment', 'shipment']);

        $shipment = $order->shipment;
        if ($shipment) {
            $shipment->setRelation('order', $order);
        }

        $data = [
            'order_no' => $order->order_no,
        ];

        if ($this->includeToken) {
            $data['token'] = $order->public_token;
        }

        return $data + [
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'shipping_method' => $order->shipping_method,
            'placed_at' => Presenter::iso($order->placed_at),
            'customer' => [
                'name' => $order->customer_name,
                'email' => $order->customer_email,
                'phone' => $order->customer_phone,
            ],
            'address' => [
                'name' => $order->ship_name,
                'phone' => $order->ship_phone,
                'line1' => $order->ship_line1,
                'line2' => $order->ship_line2,
                'city' => $order->ship_city,
                'state' => $order->ship_state,
                'pincode' => $order->ship_pincode,
            ],
            'items' => $order->items->map(fn ($i) => [
                'id' => $i->id,
                'product_id' => $i->product_id,
                'variant_id' => $i->product_variant_id,
                'product_slug' => $i->product_slug,
                'name' => $i->name,
                'size_label' => $i->size_label,
                'image_url' => Presenter::imageUrl($i->image),
                'unit_price_paise' => (int) $i->unit_price_paise,
                'qty' => (int) $i->qty,
                'total_paise' => (int) $i->total_paise,
            ])->values()->all(),
            'subtotal_paise' => (int) $order->subtotal_paise,
            'shipping_paise' => (int) $order->shipping_paise,
            'tax_paise' => (int) $order->tax_paise,
            'discount_paise' => (int) $order->discount_paise,
            'total_paise' => (int) $order->total_paise,
            'payment' => $order->payment ? [
                'gateway' => $order->payment->gateway,
                'status' => $order->payment->status,
                'gateway_payment_id' => $order->payment->gateway_payment_id,
                'method' => $order->payment->method,
            ] : null,
            'shipment' => $shipment ? (new ShipmentResource($shipment))->resolve($request) : null,
            'timeline' => app(OrderService::class)->timeline($order),
        ];
    }
}
