<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Shipping\ShippingService;
use App\Support\Presenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Quotes and places orders. All amounts come from the database, never from the request.
 */
class CheckoutService
{
    /** Parcel weight estimate used for serviceability checks. */
    private const GRAMS_PER_UNIT = 250;

    public function __construct(
        private PricingService $pricing,
        private ShippingService $shipping,
        private PaymentGatewayInterface $gateway,
        private OrderService $orders,
    ) {
    }

    /** Cart subtotal in paise from current DB prices. */
    public function subtotal(Cart $cart): int
    {
        $cart->loadMissing('items.variant');

        $sum = 0;
        foreach ($cart->items as $item) {
            if ($item->variant) {
                $sum += (int) $item->variant->price_paise * (int) $item->qty;
            }
        }

        return $sum;
    }

    public function weightGrams(Cart $cart): int
    {
        $cart->loadMissing('items');

        return max(500, (int) $cart->items->sum('qty') * self::GRAMS_PER_UNIT);
    }

    /**
     * @return array<string, mixed>
     */
    public function quote(Cart $cart, string $method = 'standard'): array
    {
        $cart->loadMissing('items.variant.product');

        $items = [];
        foreach ($cart->items as $item) {
            $variant = $item->variant;
            $product = $variant?->product;
            if (! $variant || ! $product) {
                continue;
            }
            $items[] = [
                'id' => $item->id,
                'variant_id' => $variant->id,
                'product_id' => $product->id,
                'slug' => $product->slug,
                'name' => $product->name,
                'size_label' => $variant->size_label,
                'image_url' => Presenter::imageUrl($product->image),
                'unit_price_paise' => (int) $variant->price_paise,
                'qty' => (int) $item->qty,
                'line_total_paise' => (int) $variant->price_paise * (int) $item->qty,
                'stock' => (int) $variant->stock,
            ];
        }

        $subtotal = array_sum(array_column($items, 'line_total_paise'));

        return ['items' => $items] + $this->pricing->totals($subtotal, $method);
    }

    /**
     * Place an order for the cart. Does NOT clear the cart (that happens when payment succeeds).
     *
     * @param  array<string, mixed>  $data  validated checkout input
     * @return array{order: Order, payment: array<string, mixed>}
     */
    public function placeOrder(array $data, Cart $cart, ?User $user = null): array
    {
        $method = $data['shipping_method'] ?? 'standard';

        $cart->load('items');
        if ($cart->items->isEmpty()) {
            abort(409, 'Your cart is empty.');
        }

        $address = $this->resolveAddress($data, $user);
        $customer = $this->resolveCustomer($data, $user, $address);

        // External call kept outside the row locks; re-checked cheaply below only via stock.
        $serviceability = $this->shipping->serviceability($address['pincode'], $this->weightGrams($cart));
        if (! $serviceability['serviceable']) {
            abort(409, 'Sorry, we do not deliver to this pincode yet.');
        }

        return DB::transaction(function () use ($data, $cart, $user, $method, $address, $customer) {
            $cartItems = CartItem::where('cart_id', $cart->id)->get();
            if ($cartItems->isEmpty()) {
                abort(409, 'Your cart is empty.');
            }

            // Lock variant rows (ordered by id to avoid deadlocks).
            $variants = ProductVariant::whereIn('id', $cartItems->pluck('product_variant_id')->all())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $products = Product::whereIn('id', $variants->pluck('product_id')->all())->get()->keyBy('id');

            $lines = [];
            $subtotal = 0;
            foreach ($cartItems as $cartItem) {
                $variant = $variants->get($cartItem->product_variant_id);
                $product = $variant ? $products->get($variant->product_id) : null;

                if (! $variant || ! $product || ! $variant->is_active || ! $product->is_active) {
                    $name = $product?->name ?? 'An item in your cart';
                    abort(409, $name.' is no longer available. Please update your cart.');
                }

                $qty = (int) $cartItem->qty;
                if ($qty < 1 || $variant->stock < $qty) {
                    abort(409, $variant->stock > 0
                        ? 'Only '.$variant->stock.' of '.$product->name.' ('.$variant->size_label.') left in stock.'
                        : $product->name.' ('.$variant->size_label.') is out of stock.');
                }

                $lines[] = compact('variant', 'product', 'qty');
                $subtotal += (int) $variant->price_paise * $qty;
            }

            $totals = $this->pricing->totals($subtotal, $method);

            $order = new Order();
            $order->forceFill([
                'order_no' => 'TMP-'.Str::random(24),
                'public_token' => Str::random(40),
                'user_id' => $user?->id,
                'customer_name' => $customer['name'],
                'customer_email' => $customer['email'],
                'customer_phone' => $customer['phone'],
                'ship_name' => $address['name'],
                'ship_phone' => $address['phone'],
                'ship_line1' => $address['line1'],
                'ship_line2' => $address['line2'],
                'ship_city' => $address['city'],
                'ship_state' => $address['state'],
                'ship_pincode' => $address['pincode'],
                'shipping_method' => $method,
                'subtotal_paise' => $totals['subtotal_paise'],
                'shipping_paise' => $totals['shipping_paise'],
                'tax_paise' => $totals['tax_paise'],
                'discount_paise' => $totals['discount_paise'],
                'total_paise' => $totals['total_paise'],
                'status' => 'pending',
                'payment_status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'placed_at' => now(),
            ])->save();

            $order->forceFill([
                'order_no' => sprintf('KKG-%s-%06d', now()->format('Y'), $order->id),
            ])->save();

            foreach ($lines as $line) {
                /** @var ProductVariant $variant */
                $variant = $line['variant'];
                $product = $line['product'];
                $qty = $line['qty'];

                (new OrderItem())->forceFill([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'product_slug' => $product->slug,
                    'name' => $product->name,
                    'size_label' => $variant->size_label,
                    'image' => $product->image,
                    'unit_price_paise' => (int) $variant->price_paise,
                    'qty' => $qty,
                    'total_paise' => (int) $variant->price_paise * $qty,
                ])->save();

                $variant->forceFill(['stock' => $variant->stock - $qty])->save();
                $this->orders->movement($variant, -$qty, 'Order '.$order->order_no, $user?->id);
            }

            $gatewayOrder = $this->gateway->createOrder($order);

            $payment = new Payment();
            $payment->forceFill([
                'order_id' => $order->id,
                'gateway' => $this->gateway->name(),
                'gateway_order_id' => $gatewayOrder['gateway_order_id'],
                'amount_paise' => $order->total_paise,
                'currency' => 'INR',
                'status' => 'pending',
                'payload' => $gatewayOrder['payload'] ?? null,
            ])->save();

            $order->load(['items', 'payment', 'shipment']);

            return [
                'order' => $order,
                'payment' => [
                    'gateway' => $payment->gateway,
                    'gateway_order_id' => $payment->gateway_order_id,
                    'key_id' => $gatewayOrder['key_id'],
                    'amount_paise' => (int) $order->total_paise,
                    'currency' => 'INR',
                    'prefill' => [
                        'name' => $customer['name'],
                        'email' => $customer['email'],
                        'phone' => $customer['phone'],
                    ],
                ],
            ];
        });
    }

    /**
     * @return array{name:string,phone:string,line1:string,line2:?string,city:string,state:string,pincode:string}
     */
    private function resolveAddress(array $data, ?User $user): array
    {
        if ($user && ! empty($data['address_id'])) {
            $saved = Address::where('user_id', $user->id)->find($data['address_id']);
            if (! $saved) {
                abort(422, 'The selected address is invalid.');
            }
            $src = [
                'name' => $saved->name,
                'phone' => $saved->phone,
                'line1' => $saved->line1,
                'line2' => $saved->line2,
                'city' => $saved->city,
                'state' => $saved->state,
                'pincode' => $saved->pincode,
            ];
        } else {
            $src = (array) ($data['address'] ?? []);
        }

        if (empty($src['pincode']) || empty($src['line1'])) {
            abort(422, 'A delivery address is required.');
        }

        return [
            'name' => (string) $src['name'],
            'phone' => (string) $src['phone'],
            'line1' => (string) $src['line1'],
            'line2' => isset($src['line2']) && $src['line2'] !== '' ? (string) $src['line2'] : null,
            'city' => (string) $src['city'],
            'state' => (string) $src['state'],
            'pincode' => (string) $src['pincode'],
        ];
    }

    /**
     * @return array{name:string,email:string,phone:string}
     */
    private function resolveCustomer(array $data, ?User $user, array $address): array
    {
        $c = (array) ($data['customer'] ?? []);

        $name = $c['name'] ?? $user?->name ?? $address['name'];
        $email = $c['email'] ?? $user?->email;
        $phone = $c['phone'] ?? $user?->phone ?? $address['phone'];

        if (! $email) {
            abort(422, 'A customer email is required.');
        }

        return ['name' => (string) $name, 'email' => (string) $email, 'phone' => (string) $phone];
    }
}
