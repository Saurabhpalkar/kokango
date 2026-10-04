<?php

namespace App\Http\Resources;

use App\Support\Presenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Shipment */
class ShipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $order = $this->resource->order;

        return [
            'id' => $this->id,
            'order_no' => $order?->order_no,
            'customer_name' => $order?->customer_name,
            'provider' => $this->provider,
            'courier' => $this->courier,
            'awb' => $this->awb,
            'status' => $this->status,
            'tracking_url' => $this->tracking_url,
            'shipped_at' => Presenter::iso($this->shipped_at),
            'delivered_at' => Presenter::iso($this->delivered_at),
            'created_at' => Presenter::iso($this->created_at),
        ];
    }
}
