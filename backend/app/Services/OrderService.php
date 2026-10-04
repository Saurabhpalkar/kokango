<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Shipping\ShippingService;
use App\Support\Presenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * State transitions for orders, payments and stock.
 *
 * Stock is "held" for an order from checkout until the payment fails or the order is cancelled.
 */
class OrderService
{
    private const SHIPPED_STATES = ['picked_up', 'in_transit', 'out_for_delivery'];
    private const PAST_READY_STATES = ['picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'returned'];

    public function __construct(
        private PaymentGatewayInterface $gateway,
        private ShippingService $shipping,
    ) {
    }

    // ------------------------------------------------------------------ access

    public function canAccess(Order $order, ?User $user, ?string $token): bool
    {
        if ($user && $order->user_id && (int) $order->user_id === (int) $user->id) {
            return true;
        }

        if ($token !== null && $token !== '' && $order->public_token && hash_equals((string) $order->public_token, $token)) {
            return true;
        }

        return false;
    }

    /** Find an order the viewer may see; 404 for both "missing" and "not yours". */
    public function findForViewer(string $orderNo, ?User $user, ?string $token): Order
    {
        $order = Order::where('order_no', $orderNo)->first();

        if (! $order || ! $this->canAccess($order, $user, $token)) {
            abort(404, 'Order not found.');
        }

        return $order;
    }

    // ------------------------------------------------------------------ payment

    /**
     * Mark a payment as paid. Idempotent: safe to call from verify and webhook concurrently.
     */
    public function markPaid(Payment $payment, array $gatewayData = []): Order
    {
        $order = DB::transaction(function () use ($payment, $gatewayData) {
            $p = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $order = Order::whereKey($p->order_id)->lockForUpdate()->firstOrFail();

            if (in_array($p->status, ['paid', 'refunded'], true)) {
                return $order;
            }

            $note = null;
            $newStatus = $order->status;

            if ($order->status === 'cancelled') {
                $note = 'Payment received after the order was cancelled; refund required.';
                Log::warning('Late payment on cancelled order', ['order' => $order->order_no]);
            } else {
                $stockHeld = $order->payment_status !== 'failed';
                $stockOk = $stockHeld || $this->reserveStock($order, 'Order '.$order->order_no.' (late payment)');

                if ($stockOk) {
                    if ($order->status === 'pending') {
                        $newStatus = 'confirmed';
                    }
                } else {
                    $note = 'Payment received but stock is no longer available; refund or restock required.';
                    Log::warning('Late payment without stock', ['order' => $order->order_no]);
                }
            }

            $payload = (array) $p->payload;
            $payload['verified_at'] = now()->toIso8601String();

            $p->forceFill([
                'status' => 'paid',
                'gateway_payment_id' => $gatewayData['gateway_payment_id'] ?? $p->gateway_payment_id,
                'signature' => $gatewayData['signature'] ?? $p->signature,
                'method' => $gatewayData['method'] ?? $p->method,
                'paid_at' => now(),
                'payload' => $payload,
            ])->save();

            $orderUpdates = ['payment_status' => 'paid', 'status' => $newStatus];
            if ($note) {
                $orderUpdates['notes'] = trim(((string) $order->notes)."\n[system] ".$note);
            }
            $order->forceFill($orderUpdates)->save();

            if ($newStatus === 'confirmed' && ! $order->shipments()->exists()) {
                (new Shipment())->forceFill([
                    'order_id' => $order->id,
                    'provider' => $this->shipping->providerName(),
                    'status' => 'pending',
                ])->save();
            }

            if ($newStatus === 'confirmed' && $order->user_id) {
                $this->clearCart($order);
            }

            return $order;
        });

        Log::info('Order paid', ['order' => $order->order_no]);

        return $order->fresh();
    }

    public function markPaymentFailed(Order $order, ?string $reason = null): void
    {
        DB::transaction(function () use ($order, $reason) {
            $o = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            // Never downgrade a paid / refunded order, and do nothing twice.
            if (in_array($o->payment_status, ['paid', 'refunded', 'failed'], true)) {
                return;
            }

            $payment = Payment::where('order_id', $o->id)->where('status', 'pending')->latest('id')->lockForUpdate()->first();
            if ($payment) {
                $payload = (array) $payment->payload;
                $payload['failure_reason'] = $reason;
                $payment->forceFill(['status' => 'failed', 'payload' => $payload])->save();
            }

            $o->forceFill(['payment_status' => 'failed'])->save();

            if ($o->status !== 'cancelled') {
                $this->restoreStock($o, 'Payment failed '.$o->order_no);
            }
        });
    }

    /** Refund a paid payment through the gateway. */
    public function refundPayment(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            $p = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($p->status !== 'paid') {
                abort(409, 'Only paid payments can be refunded.');
            }

            $result = $this->gateway->refund($p);

            $payload = (array) $p->payload;
            $payload['refund'] = [
                'refund_id' => $result['refund_id'] ?? null,
                'status' => $result['status'] ?? null,
                'at' => now()->toIso8601String(),
            ];
            $p->forceFill(['status' => 'refunded', 'payload' => $payload])->save();

            Order::whereKey($p->order_id)->lockForUpdate()->first()?->forceFill(['payment_status' => 'refunded'])->save();

            return $p;
        });
    }

    // ------------------------------------------------------------------ cancel / stock

    public function cancel(Order $order): Order
    {
        DB::transaction(function () use ($order) {
            $o = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($o->status === 'cancelled') {
                return;
            }

            if (in_array($o->status, ['shipped', 'delivered'], true)) {
                abort(409, 'This order has already been shipped and cannot be cancelled.');
            }

            $shipments = Shipment::where('order_id', $o->id)->lockForUpdate()->get();
            foreach ($shipments as $shipment) {
                if (in_array($shipment->status, self::PAST_READY_STATES, true)) {
                    abort(409, 'This order has already been shipped and cannot be cancelled.');
                }
            }

            $stockHeld = $o->payment_status !== 'failed';

            // Money first: if the refund fails nothing else changes.
            $payment = Payment::where('order_id', $o->id)->latest('id')->first();
            if ($o->payment_status === 'paid' && $payment && $payment->status === 'paid') {
                $this->refundPayment($payment);
            } elseif ($payment && $payment->status === 'pending') {
                $payload = (array) $payment->payload;
                $payload['failure_reason'] = 'Order cancelled';
                $payment->forceFill(['status' => 'failed', 'payload' => $payload])->save();
            }

            foreach ($shipments as $shipment) {
                if ($shipment->status !== 'cancelled') {
                    if ($shipment->status !== 'pending' && $shipment->provider_order_id) {
                        $this->shipping->cancel($shipment);
                    }
                    $shipment->forceFill(['status' => 'cancelled'])->save();
                }
            }

            if ($stockHeld) {
                $this->restoreStock($o, 'Order '.$o->order_no.' cancelled');
            }

            $o->refresh();
            $o->forceFill(['status' => 'cancelled'])->save();
        });

        return $order->fresh();
    }

    /** Put the order's quantities back on the shelf and write stock movements. */
    public function restoreStock(Order $order, ?string $reason = null): void
    {
        DB::transaction(function () use ($order, $reason) {
            $items = $order->items()->get();
            $variants = ProductVariant::whereIn('id', $items->pluck('product_variant_id')->filter()->all())
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($items as $item) {
                $variant = $variants->get($item->product_variant_id);
                if (! $variant) {
                    continue;
                }
                $variant->forceFill(['stock' => $variant->stock + $item->qty])->save();
                $this->movement($variant, (int) $item->qty, $reason ?? 'Order '.$order->order_no.' stock restored');
            }
        });
    }

    /** Take stock again (late payment after a failure). Returns false if not enough stock. */
    private function reserveStock(Order $order, string $reason): bool
    {
        $items = $order->items()->get();
        $variants = ProductVariant::whereIn('id', $items->pluck('product_variant_id')->filter()->all())
            ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

        foreach ($items as $item) {
            $variant = $variants->get($item->product_variant_id);
            if (! $variant || $variant->stock < $item->qty) {
                return false;
            }
        }

        foreach ($items as $item) {
            $variant = $variants->get($item->product_variant_id);
            $variant->forceFill(['stock' => $variant->stock - $item->qty])->save();
            $this->movement($variant, -(int) $item->qty, $reason);
        }

        return true;
    }

    public function movement(ProductVariant $variant, int $change, string $reason, ?int $userId = null): void
    {
        (new StockMovement())->forceFill([
            'product_variant_id' => $variant->id,
            'change' => $change,
            'stock_after' => (int) $variant->stock,
            'reason' => $reason,
            'user_id' => $userId,
        ])->save();
    }

    /** Clear the signed-in customer's cart, or the guest cart that matches the token. */
    public function clearCart(Order $order, ?string $guestToken = null): void
    {
        $cart = null;

        if ($order->user_id) {
            $cart = Cart::where('user_id', $order->user_id)->first();
        } elseif ($guestToken) {
            $cart = Cart::where('token', $guestToken)->whereNull('user_id')->first();
        }

        if ($cart) {
            CartItem::where('cart_id', $cart->id)->delete();
        }
    }

    // ------------------------------------------------------------------ shipment sync

    public function syncStatusFromShipment(Shipment $shipment): ?Order
    {
        return DB::transaction(function () use ($shipment) {
            $order = Order::whereKey($shipment->order_id)->lockForUpdate()->first();
            if (! $order) {
                return null;
            }

            $status = $shipment->status;
            $stamps = [];

            if (in_array($status, self::SHIPPED_STATES, true) || $status === 'delivered') {
                if (! $shipment->shipped_at) {
                    $stamps['shipped_at'] = now();
                }
            }
            if ($status === 'delivered' && ! $shipment->delivered_at) {
                $stamps['delivered_at'] = now();
            }
            if ($stamps) {
                $shipment->forceFill($stamps)->save();
            }

            if ($order->status === 'cancelled') {
                return $order;
            }

            $new = null;
            if (in_array($status, self::SHIPPED_STATES, true)) {
                $new = $order->status === 'delivered' ? null : 'shipped';
            } elseif ($status === 'delivered') {
                $new = 'delivered';
            } elseif (in_array($status, ['ready_to_ship', 'pickup_scheduled'], true) && $order->status === 'confirmed') {
                $new = 'processing';
            }

            if ($new && $new !== $order->status) {
                $order->forceFill(['status' => $new])->save();
            }

            return $order;
        });
    }

    // ------------------------------------------------------------------ timeline

    /**
     * @return array<int, array{key:string,label:string,done:bool,at:?string}>
     */
    public function timeline(Order $order): array
    {
        $order->loadMissing(['payment', 'shipment']);

        $shipment = $order->shipment;
        if ($shipment && $shipment->status === 'cancelled') {
            $shipment = null;
        }
        $payment = $order->payment;

        $steps = [
            'confirmed' => ['Order Confirmed', 1],
            'processing' => ['Processing', 2],
            'shipped' => ['Shipped', 3],
            'in_transit' => ['In Transit', 4],
            'out_for_delivery' => ['Out for Delivery', 5],
            'delivered' => ['Delivered', 6],
        ];

        if ($order->status === 'cancelled') {
            $level = ($payment && $payment->paid_at) ? 1 : 0;
        } else {
            $orderLevel = match ($order->status) {
                'confirmed' => 1,
                'processing' => 2,
                'shipped' => 3,
                'delivered' => 6,
                default => $order->payment_status === 'paid' ? 1 : 0,
            };
            $shipLevel = $shipment ? match ($shipment->status) {
                'ready_to_ship', 'pickup_scheduled' => 2,
                'picked_up', 'returned' => 3,
                'in_transit' => 4,
                'out_for_delivery' => 5,
                'delivered' => 6,
                default => 0,
            } : 0;
            $level = max($orderLevel, $shipLevel);
        }

        $out = [];
        foreach ($steps as $key => [$label, $rank]) {
            $done = $level >= $rank;
            $at = null;

            if ($done) {
                $at = match ($key) {
                    'confirmed' => $payment?->paid_at ?? $order->placed_at,
                    'processing' => $shipment && $shipment->status !== 'pending'
                        ? $shipment->created_at
                        : ($order->status === 'processing' ? $order->updated_at : null),
                    'shipped' => $shipment?->shipped_at ?? ($order->status === 'shipped' ? $order->updated_at : null),
                    'in_transit', 'out_for_delivery' => $shipment && $shipment->status === $key ? $shipment->updated_at : null,
                    'delivered' => $shipment?->delivered_at ?? ($order->status === 'delivered' ? $order->updated_at : null),
                };
            }

            $out[] = [
                'key' => $key,
                'label' => $label,
                'done' => $done,
                'at' => Presenter::iso($at),
            ];
        }

        return $out;
    }
}
