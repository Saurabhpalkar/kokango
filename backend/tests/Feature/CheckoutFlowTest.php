<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    private const API = '/api/v1';

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'kokango.payment.driver' => 'fake',
            'kokango.shipping.driver' => 'fake',
        ]);

        Model::unguarded(function () {
            $category = Category::create(['name' => 'Powders', 'slug' => 'powders', 'is_active' => true]);
            $product = Product::create([
                'category_id' => $category->id,
                'name' => 'Moringa Powder',
                'slug' => 'moringa-powder',
                'is_active' => true,
            ]);
            $this->variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => 'MOR-100',
                'size_label' => '100g',
                'price_paise' => 41800,
                'stock' => 10,
                'is_active' => true,
            ]);
        });
    }

    private function cartToken(): string
    {
        return $this->getJson(self::API.'/cart')->assertOk()->json('data.token');
    }

    private function addToCart(string $token, int $qty = 2): void
    {
        $this->withHeader('X-Cart-Token', $token)
            ->postJson(self::API.'/cart/items', ['variant_id' => $this->variant->id, 'qty' => $qty])
            ->assertSuccessful();
    }

    private function guestPayload(array $extra = []): array
    {
        return array_merge([
            'shipping_method' => 'standard',
            'customer' => ['name' => 'Asha Rao', 'email' => 'asha@example.com', 'phone' => '9876543210'],
            'address' => [
                'name' => 'Asha Rao',
                'phone' => '9876543210',
                'line1' => '12 MG Road',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560001',
            ],
        ], $extra);
    }

    private function checkoutAsGuest(string $token, array $extra = [])
    {
        return $this->withHeader('X-Cart-Token', $token)
            ->postJson(self::API.'/checkout', $this->guestPayload($extra));
    }

    public function test_quote_uses_database_prices(): void
    {
        $token = $this->cartToken();
        $this->addToCart($token, 2);

        $this->withHeader('X-Cart-Token', $token)
            ->postJson(self::API.'/checkout/quote', ['shipping_method' => 'standard'])
            ->assertOk()
            ->assertJsonPath('data.subtotal_paise', 83600)
            ->assertJsonPath('data.shipping_paise', 6000)
            ->assertJsonPath('data.tax_paise', 4180)
            ->assertJsonPath('data.total_paise', 93780);
    }

    public function test_guest_checkout_and_fake_payment(): void
    {
        $token = $this->cartToken();
        $this->addToCart($token, 2);

        $res = $this->checkoutAsGuest($token)->assertCreated();

        $res->assertJsonPath('data.order.status', 'pending')
            ->assertJsonPath('data.order.payment_status', 'pending')
            ->assertJsonPath('data.order.total_paise', 93780)
            ->assertJsonPath('data.payment.gateway', 'fake')
            ->assertJsonPath('data.payment.amount_paise', 93780)
            ->assertJsonPath('data.payment.currency', 'INR');

        $orderNo = $res->json('data.order.order_no');
        $orderToken = $res->json('data.order.token');
        $gatewayOrderId = $res->json('data.payment.gateway_order_id');

        $this->assertMatchesRegularExpression('/^KKG-\d{4}-\d{6}$/', $orderNo);
        $this->assertNotEmpty($orderToken);

        // Stock is reserved at checkout and the cart is kept.
        $this->assertSame(8, $this->variant->fresh()->stock);
        $this->assertTrue(StockMovement::where('product_variant_id', $this->variant->id)->where('change', -2)->exists());

        $verify = [
            'order_no' => $orderNo,
            'token' => $orderToken,
            'gateway_order_id' => $gatewayOrderId,
            'gateway_payment_id' => 'pay_fake_1',
            'signature' => 'fake',
        ];

        // Wrong signature is rejected.
        $this->postJson(self::API.'/payments/verify', array_merge($verify, ['signature' => 'nope']))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Payment verification failed');

        // Without the order token a guest cannot verify.
        $this->postJson(self::API.'/payments/verify', array_merge($verify, ['token' => 'wrong']))
            ->assertNotFound();

        $this->withHeader('X-Cart-Token', $token)
            ->postJson(self::API.'/payments/verify', $verify)
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.payment.status', 'paid');

        // Idempotent.
        $this->postJson(self::API.'/payments/verify', $verify)
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'paid');

        $order = Order::where('order_no', $orderNo)->firstOrFail();
        $this->assertSame('confirmed', $order->status);
        $this->assertSame(1, Shipment::where('order_id', $order->id)->count());
        $this->assertSame('pending', Shipment::where('order_id', $order->id)->first()->status);
        $this->assertSame(8, $this->variant->fresh()->stock);

        // The guest cart was cleared.
        $this->withHeader('X-Cart-Token', $token)->getJson(self::API.'/cart')->assertJsonPath('data.count', 0);

        // Guest access to the order needs the token.
        $this->getJson(self::API.'/orders/'.$orderNo)->assertNotFound();
        $this->getJson(self::API.'/orders/'.$orderNo.'?token='.$orderToken)
            ->assertOk()
            ->assertJsonPath('data.order_no', $orderNo)
            ->assertJsonMissingPath('data.token');

        // Tracking by order number + email (case-insensitive).
        $this->postJson(self::API.'/orders/track', ['order_no' => $orderNo, 'email' => 'ASHA@example.com'])
            ->assertOk()
            ->assertJsonPath('data.order_no', $orderNo);
    }

    public function test_logged_in_checkout_and_cancel_restores_stock(): void
    {
        $user = Model::unguarded(fn () => User::create([
            'name' => 'Rohan',
            'email' => 'rohan@example.com',
            'phone' => '9123456780',
            'password' => bcrypt('Password@123'),
            'is_active' => true,
        ]));
        Sanctum::actingAs($user);

        $this->postJson(self::API.'/cart/items', ['variant_id' => $this->variant->id, 'qty' => 3])->assertSuccessful();

        $res = $this->postJson(self::API.'/checkout', $this->guestPayload(['customer' => null]))->assertCreated();
        $orderNo = $res->json('data.order.order_no');
        $this->assertSame(7, $this->variant->fresh()->stock);

        $this->postJson(self::API.'/payments/verify', [
            'order_no' => $orderNo,
            'gateway_order_id' => $res->json('data.payment.gateway_order_id'),
            'gateway_payment_id' => 'pay_fake_2',
            'signature' => 'fake',
        ])->assertOk()->assertJsonPath('data.status', 'confirmed');

        $this->getJson(self::API.'/orders')->assertOk()->assertJsonPath('data.0.order_no', $orderNo);

        $this->postJson(self::API.'/orders/'.$orderNo.'/cancel')
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.payment_status', 'refunded');

        $this->assertSame(10, $this->variant->fresh()->stock);
        $this->assertSame('refunded', Payment::first()->status);
    }

    public function test_failed_payment_restores_stock(): void
    {
        $token = $this->cartToken();
        $this->addToCart($token, 4);
        $res = $this->checkoutAsGuest($token)->assertCreated();
        $this->assertSame(6, $this->variant->fresh()->stock);

        $this->postJson(self::API.'/payments/failed', [
            'order_no' => $res->json('data.order.order_no'),
            'token' => $res->json('data.order.token'),
            'reason' => 'Card declined',
        ])->assertOk();

        $this->assertSame(10, $this->variant->fresh()->stock);
        $order = Order::firstOrFail();
        $this->assertSame('failed', $order->payment_status);
        $this->assertSame('pending', $order->status);

        // A second failure call does not restore stock twice.
        $this->postJson(self::API.'/payments/failed', [
            'order_no' => $order->order_no,
            'token' => $order->public_token,
        ])->assertOk();
        $this->assertSame(10, $this->variant->fresh()->stock);
    }

    public function test_out_of_stock_returns_409_and_creates_nothing(): void
    {
        $token = $this->cartToken();
        $this->addToCart($token, 2);

        $this->variant->forceFill(['stock' => 1])->save();

        $this->checkoutAsGuest($token)->assertStatus(409);

        $this->assertSame(0, Order::count());
        $this->assertSame(1, $this->variant->fresh()->stock);
    }

    public function test_empty_cart_returns_409(): void
    {
        $token = $this->cartToken();

        $this->checkoutAsGuest($token)->assertStatus(409);
    }

    public function test_unserviceable_pincode_returns_409(): void
    {
        $token = $this->cartToken();
        $this->addToCart($token, 1);

        $payload = $this->guestPayload();
        $payload['address']['pincode'] = '012345';

        $this->withHeader('X-Cart-Token', $token)->postJson(self::API.'/checkout', $payload)->assertStatus(409);
        $this->assertSame(10, $this->variant->fresh()->stock);
    }

    public function test_client_supplied_prices_are_ignored(): void
    {
        $token = $this->cartToken();
        $this->addToCart($token, 2);

        $res = $this->checkoutAsGuest($token, [
            'total_paise' => 1,
            'subtotal_paise' => 1,
            'shipping_paise' => 0,
            'items' => [['variant_id' => $this->variant->id, 'qty' => 99, 'unit_price_paise' => 1]],
        ])->assertCreated();

        $res->assertJsonPath('data.order.total_paise', 93780)
            ->assertJsonPath('data.payment.amount_paise', 93780);
        $this->assertSame(93780, (int) Order::firstOrFail()->total_paise);
        $this->assertSame(8, $this->variant->fresh()->stock);
    }

    public function test_guest_requires_customer_and_validates_phone_and_pincode(): void
    {
        $token = $this->cartToken();
        $this->addToCart($token, 1);

        $payload = $this->guestPayload();
        unset($payload['customer']);
        $this->withHeader('X-Cart-Token', $token)->postJson(self::API.'/checkout', $payload)
            ->assertStatus(422)->assertJsonValidationErrors(['customer']);

        $payload = $this->guestPayload();
        $payload['address']['pincode'] = '56001';
        $payload['address']['phone'] = '12345';
        $this->withHeader('X-Cart-Token', $token)->postJson(self::API.'/checkout', $payload)
            ->assertStatus(422)->assertJsonValidationErrors(['address.pincode', 'address.phone']);
    }

    public function test_payment_cannot_be_verified_against_another_order(): void
    {
        $tokenA = $this->cartToken();
        $this->addToCart($tokenA, 1);
        $a = $this->checkoutAsGuest($tokenA)->assertCreated();

        $this->flushHeaders();
        $tokenB = $this->cartToken();
        $this->addToCart($tokenB, 1);
        $b = $this->checkoutAsGuest($tokenB)->assertCreated();

        // Order A's token with order B's gateway order id: no such payment on A.
        $this->postJson(self::API.'/payments/verify', [
            'order_no' => $a->json('data.order.order_no'),
            'token' => $a->json('data.order.token'),
            'gateway_order_id' => $b->json('data.payment.gateway_order_id'),
            'gateway_payment_id' => 'pay_x',
            'signature' => 'fake',
        ])->assertNotFound();

        $this->assertSame('pending', Order::where('order_no', $b->json('data.order.order_no'))->first()->payment_status);
    }

    public function test_cart_items_of_other_carts_cannot_be_touched(): void
    {
        $tokenA = $this->cartToken();
        $this->addToCart($tokenA, 1);
        $itemId = $this->withHeader('X-Cart-Token', $tokenA)->getJson(self::API.'/cart')->json('data.items.0.id');

        $this->flushHeaders();
        $tokenB = $this->cartToken();

        $this->withHeader('X-Cart-Token', $tokenB)->patchJson(self::API.'/cart/items/'.$itemId, ['qty' => 5])->assertNotFound();
        $this->withHeader('X-Cart-Token', $tokenB)->deleteJson(self::API.'/cart/items/'.$itemId)->assertNotFound();
        $this->withHeader('X-Cart-Token', $tokenA)->getJson(self::API.'/cart')->assertJsonPath('data.count', 1);
    }

    public function test_abandoned_unpaid_orders_release_their_stock(): void
    {
        $token = $this->cartToken();
        $this->addToCart($token, 4);
        $this->checkoutAsGuest($token)->assertCreated();
        $this->assertSame(6, $this->variant->fresh()->stock);

        // Fresh orders are left alone.
        $this->artisan('kokango:expire-pending-orders')->assertSuccessful();
        $this->assertSame(6, $this->variant->fresh()->stock);

        Order::query()->update(['placed_at' => now()->subHours(3)]);
        $this->artisan('kokango:expire-pending-orders')->assertSuccessful();

        $this->assertSame(10, $this->variant->fresh()->stock);
        $this->assertSame('failed', Order::firstOrFail()->payment_status);
    }

    public function test_shipping_serviceability(): void
    {
        $this->postJson(self::API.'/shipping/serviceability', ['pincode' => '560001'])
            ->assertOk()
            ->assertJsonPath('data.serviceable', true)
            ->assertJsonPath('data.city', 'Bengaluru')
            ->assertJsonPath('data.methods.0.code', 'standard')
            ->assertJsonPath('data.methods.0.price_paise', 6000)
            ->assertJsonPath('data.methods.1.price_paise', 12000);

        $this->postJson(self::API.'/shipping/rates', ['pincode' => '12'])->assertStatus(422);
    }
}
