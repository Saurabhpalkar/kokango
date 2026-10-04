<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Str;

/**
 * Local development / test gateway. Never use with real money.
 */
class FakeGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'fake';
    }

    public function createOrder(Order $order): array
    {
        return [
            'gateway_order_id' => 'order_fake_'.Str::random(14),
            'key_id' => 'rzp_test_fake',
            'amount_paise' => (int) $order->total_paise,
            'currency' => 'INR',
            'payload' => ['driver' => 'fake'],
        ];
    }

    public function verify(array $data): bool
    {
        $signature = (string) ($data['signature'] ?? '');
        if ($signature === '') {
            return false;
        }

        // The shortcut signature is for local/testing only; on a production server it would let anyone mark orders paid.
        if ($signature === 'fake') {
            return ! app()->isProduction();
        }

        $expected = hash_hmac('sha256', ($data['gateway_order_id'] ?? '').'|'.($data['gateway_payment_id'] ?? ''), $this->key());

        return hash_equals($expected, $signature);
    }

    public function verifyWebhook(string $payload, string $signature): bool
    {
        if ($signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $payload, $this->key()), $signature);
    }

    public function refund(Payment $payment): array
    {
        return [
            'success' => true,
            'refund_id' => 'rfnd_fake_'.Str::random(12),
            'status' => 'processed',
        ];
    }

    private function key(): string
    {
        return (string) config('app.key');
    }
}
