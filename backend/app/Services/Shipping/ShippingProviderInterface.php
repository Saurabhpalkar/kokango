<?php

namespace App\Services\Shipping;

use App\Models\Order;
use App\Models\Shipment;

interface ShippingProviderInterface
{
    /** Provider name stored on the shipment row: fake | shiprocket. */
    public function name(): string;

    /**
     * @return array{serviceable:bool,city:?string,state:?string,etd_days_min:?int,etd_days_max:?int}
     */
    public function serviceability(string $pincode, int $weightGrams): array;

    /**
     * @return array{provider_order_id:?string,courier:?string,awb:?string,tracking_url:?string,status:string,payload?:array}
     */
    public function createShipment(Order $order): array;

    /**
     * @return array{status:string,events:array<int,array{status:string,location:?string,at:?string}>}
     */
    public function track(Shipment $shipment): array;

    public function cancel(Shipment $shipment): void;
}
