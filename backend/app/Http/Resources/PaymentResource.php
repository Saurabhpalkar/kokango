<?php

namespace App\Http\Resources;

use App\Support\Presenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Admin payment row. @mixin \App\Models\Payment */
class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $order = $this->resource->order;

        return [
            'id' => $this->id,
            'order_no' => $order?->order_no,
            'customer_name' => $order?->customer_name,
            'amount_paise' => (int) $this->amount_paise,
            'gateway' => $this->gateway,
            'gateway_payment_id' => $this->gateway_payment_id,
            'method' => $this->method,
            'status' => $this->status,
            'created_at' => Presenter::iso($this->created_at),
        ];
    }
}
