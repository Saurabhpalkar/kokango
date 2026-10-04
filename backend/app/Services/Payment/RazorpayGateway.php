<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

class RazorpayGateway implements PaymentGatewayInterface
{
    private const BASE_URL = 'https://api.razorpay.com/v1';

    public function name(): string
    {
        return 'razorpay';
    }

    public function createOrder(Order $order): array
    {
        $response = $this->client()->post(self::BASE_URL.'/orders', [
            'amount' => (int) $order->total_paise,
            'currency' => 'INR',
            'receipt' => (string) $order->order_no,
            'notes' => [
                'order_no' => (string) $order->order_no,
            ],
        ]);

        $json = $this->ensureOk($response, 'create order');

        return [
            'gateway_order_id' => (string) $json['id'],
            'key_id' => (string) config('kokango.payment.razorpay.key_id'),
            'amount_paise' => (int) ($json['amount'] ?? $order->total_paise),
            'currency' => (string) ($json['currency'] ?? 'INR'),
            'payload' => [
                'razorpay_order' => [
                    'id' => $json['id'] ?? null,
                    'status' => $json['status'] ?? null,
                    'receipt' => $json['receipt'] ?? null,
                ],
            ],
        ];
    }

    public function verify(array $data): bool
    {
        $secret = (string) config('kokango.payment.razorpay.key_secret');
        $signature = (string) ($data['signature'] ?? '');
        if ($secret === '' || $signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', ($data['gateway_order_id'] ?? '').'|'.($data['gateway_payment_id'] ?? ''), $secret);

        return hash_equals($expected, $signature);
    }

    public function verifyWebhook(string $payload, string $signature): bool
    {
        $secret = (string) config('kokango.payment.razorpay.webhook_secret');
        if ($secret === '' || $signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $payload, $secret), $signature);
    }

    public function refund(Payment $payment): array
    {
        if (! $payment->gateway_payment_id) {
            abort(409, 'This payment has no gateway payment id and cannot be refunded automatically.');
        }

        $response = $this->client()->post(
            self::BASE_URL.'/payments/'.$payment->gateway_payment_id.'/refund',
            ['amount' => (int) $payment->amount_paise]
        );

        $json = $this->ensureOk($response, 'refund');

        return [
            'success' => true,
            'refund_id' => $json['id'] ?? null,
            'status' => $json['status'] ?? null,
        ];
    }

    private function client(): PendingRequest
    {
        return Http::withBasicAuth(
            (string) config('kokango.payment.razorpay.key_id'),
            (string) config('kokango.payment.razorpay.key_secret')
        )->acceptJson()->asJson()->timeout(20);
    }

    private function ensureOk(Response $response, string $action): array
    {
        if ($response->failed()) {
            Log::error('Razorpay '.$action.' failed', [
                'status' => $response->status(),
                'body' => $response->json('error') ?? substr($response->body(), 0, 500),
            ]);
            abort(502, 'The payment gateway is unavailable. Please try again.');
        }

        return (array) $response->json();
    }
}
