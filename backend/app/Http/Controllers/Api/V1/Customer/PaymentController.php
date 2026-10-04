<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Concerns\ResolvesViewer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\FailedPaymentRequest;
use App\Http\Requests\Payment\VerifyPaymentRequest;
use App\Http\Resources\OrderResource;
use App\Models\Payment;
use App\Services\OrderService;
use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    use ResolvesViewer;

    public function __construct(
        private OrderService $orders,
        private PaymentGatewayInterface $gateway,
    ) {
    }

    public function verify(VerifyPaymentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $order = $this->orders->findForViewer($data['order_no'], $this->viewer(), $data['token'] ?? null);

        $payment = Payment::where('order_id', $order->id)
            ->where('gateway_order_id', $data['gateway_order_id'])
            ->first();
        if (! $payment) {
            abort(404, 'Payment not found.');
        }

        // Idempotent: a second call (or the webhook arriving first) just returns the order.
        if ($payment->status !== 'paid') {
            $valid = $this->gateway->verify([
                'gateway_order_id' => $data['gateway_order_id'],
                'gateway_payment_id' => $data['gateway_payment_id'],
                'signature' => $data['signature'],
            ]);
            if (! $valid) {
                abort(422, 'Payment verification failed');
            }

            $order = $this->orders->markPaid($payment, [
                'gateway_payment_id' => $data['gateway_payment_id'],
                'signature' => $data['signature'],
            ]);
        }

        if (! $order->user_id) {
            $this->orders->clearCart($order, (string) $request->header('X-Cart-Token', ''));
        }

        return response()->json(['data' => (new OrderResource($order->fresh()))->resolve($request)]);
    }

    public function failed(FailedPaymentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $order = $this->orders->findForViewer($data['order_no'], $this->viewer(), $data['token'] ?? null);

        $this->orders->markPaymentFailed($order, $data['reason'] ?? null);

        return response()->json(['message' => 'Payment failure recorded.']);
    }

    public function razorpayWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('X-Razorpay-Signature', '');

        if (! $this->gateway->verifyWebhook($payload, $signature)) {
            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        try {
            $event = json_decode($payload, true) ?: [];
            $entity = (array) data_get($event, 'payload.payment.entity', []);
            $gatewayOrderId = $entity['order_id'] ?? null;

            $payment = $gatewayOrderId ? Payment::where('gateway_order_id', $gatewayOrderId)->first() : null;

            if ($payment) {
                switch ($event['event'] ?? '') {
                    case 'payment.captured':
                        // The amount Razorpay captured must be what we asked for (paise).
                        if (isset($entity['amount']) && (int) $entity['amount'] !== (int) $payment->amount_paise) {
                            Log::warning('Razorpay webhook amount mismatch', ['payment' => $payment->id, 'amount' => $entity['amount']]);
                            break;
                        }

                        $this->orders->markPaid($payment, [
                            'gateway_payment_id' => $entity['id'] ?? null,
                            'method' => $entity['method'] ?? null,
                        ]);
                        break;

                    case 'payment.failed':
                        if ($payment->status === 'pending') {
                            $this->orders->markPaymentFailed(
                                $payment->order,
                                $entity['error_description'] ?? 'Payment failed'
                            );
                        }
                        break;
                }
            }
        } catch (\Throwable $e) {
            Log::error('Razorpay webhook handling failed', ['error' => $e->getMessage()]);

            // Not 2xx: Razorpay retries the delivery later (handling is idempotent).
            return response()->json(['message' => 'Webhook handling failed.'], 500);
        }

        return response()->json(['message' => 'OK']);
    }
}
