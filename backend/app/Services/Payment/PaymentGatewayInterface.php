<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;

interface PaymentGatewayInterface
{
    /** Gateway name stored on the payment row: razorpay | fake. */
    public function name(): string;

    /**
     * Create the gateway-side order.
     *
     * @return array{gateway_order_id:string,key_id:string,amount_paise:int,currency:string,payload:array}
     */
    public function createOrder(Order $order): array;

    /**
     * Verify a checkout signature.
     *
     * @param  array{gateway_order_id:string,gateway_payment_id:string,signature:string}  $data
     */
    public function verify(array $data): bool;

    public function verifyWebhook(string $payload, string $signature): bool;

    /**
     * @return array{success:bool,refund_id:?string,status:?string}
     */
    public function refund(Payment $payment): array;
}
