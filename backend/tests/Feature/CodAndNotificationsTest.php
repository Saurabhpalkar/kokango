<?php

namespace Tests\Feature;

use App\Mail\OrderMail;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CodAndNotificationsTest extends TestCase
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
            'kokango.cod.enabled' => true,
            'kokango.cod.max_paise' => 0,
        ]);

        $this->seed(RoleSeeder::class);

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

        Setting::set('notification_email', 'owner@example.com');
    }

    private function cartToken(): string
    {
        return $this->getJson(self::API.'/cart')->json('data.token');
    }

    private function addToCart(string $token, int $qty = 2): void
    {
        $this->withHeader('X-Cart-Token', $token)
            ->postJson(self::API.'/cart/items', ['variant_id' => $this->variant->id, 'qty' => $qty])
            ->assertSuccessful();
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'shipping_method' => 'standard',
            'payment_method' => 'cod',
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

    private function placeGuestCod(int $qty = 2)
    {
        $token = $this->cartToken();
        $this->addToCart($token, $qty);

        return [$token, $this->withHeader('X-Cart-Token', $token)->postJson(self::API.'/checkout', $this->payload())];
    }

    public function test_cod_order_is_confirmed_at_once_and_emails_are_sent(): void
    {
        Mail::fake();

        [$token, $res] = $this->placeGuestCod(2);
        $res->assertCreated()
            ->assertJsonPath('data.order.status', 'confirmed')
            ->assertJsonPath('data.order.payment_status', 'pending')
            ->assertJsonPath('data.payment.gateway', 'cod')
            ->assertJsonPath('data.order.total_paise', 93780);

        $order = Order::firstOrFail();
        $this->assertTrue($order->isCod());
        $this->assertSame('pending', Payment::first()->status);
        $this->assertSame(1, Shipment::where('order_id', $order->id)->count());

        // Stock is taken, and the guest cart is emptied.
        $this->assertSame(8, $this->variant->fresh()->stock);
        $this->withHeader('X-Cart-Token', $token)->getJson(self::API.'/cart')->assertJsonPath('data.count', 0);

        // One email to the customer, one to the shop owner.
        Mail::assertSent(OrderMail::class, 2);
        Mail::assertSent(OrderMail::class, fn (OrderMail $m) => $m->kind === 'placed' && $m->hasTo('asha@example.com'));
        Mail::assertSent(OrderMail::class, fn (OrderMail $m) => $m->kind === 'admin' && $m->hasTo('owner@example.com'));
    }

    public function test_cod_email_renders(): void
    {
        [, $res] = $this->placeGuestCod(1);
        $res->assertCreated();

        $html = (new OrderMail(Order::firstOrFail(), 'placed'))->render();

        $this->assertStringContainsString(Order::first()->order_no, $html);
        $this->assertStringContainsString('Cash on Delivery', $html);
        $this->assertStringContainsString('Moringa Powder', $html);
    }

    public function test_cod_order_is_shipped_by_admin_and_paid_on_delivery(): void
    {
        Mail::fake();

        $this->placeGuestCod(1)[1]->assertCreated();
        $order = Order::firstOrFail();
        Mail::assertSent(OrderMail::class, 2);

        $admin = Model::unguarded(fn () => User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('Admin@12345'), 'is_active' => true]));
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        // An unpaid COD order may be shipped.
        $ship = $this->postJson(self::API.'/admin/orders/'.$order->order_no.'/shipment')->assertCreated();
        $shipmentId = $ship->json('data.id');
        $this->assertSame('processing', $order->fresh()->status);

        $this->putJson(self::API.'/admin/shipments/'.$shipmentId.'/status', ['status' => 'in_transit'])->assertOk();
        $this->assertSame('shipped', $order->fresh()->status);
        Mail::assertSent(OrderMail::class, fn (OrderMail $m) => $m->kind === 'shipped' && $m->hasTo('asha@example.com'));

        // Not paid until the parcel is delivered.
        $this->assertSame('pending', $order->fresh()->payment_status);

        $this->putJson(self::API.'/admin/shipments/'.$shipmentId.'/status', ['status' => 'delivered'])->assertOk();

        $order->refresh();
        $this->assertSame('delivered', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('paid', Payment::first()->status);
        Mail::assertSent(OrderMail::class, fn (OrderMail $m) => $m->kind === 'delivered' && $m->hasTo('asha@example.com'));

        // Setting the same status again does not send another email.
        $this->putJson(self::API.'/admin/shipments/'.$shipmentId.'/status', ['status' => 'delivered'])->assertOk();
        Mail::assertSent(OrderMail::class, fn (OrderMail $m) => $m->kind === 'delivered', 1);
    }

    public function test_admin_can_move_a_cod_order_with_the_status_control(): void
    {
        Mail::fake();

        $this->placeGuestCod(1)[1]->assertCreated();
        $order = Order::firstOrFail();

        $admin = Model::unguarded(fn () => User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('Admin@12345'), 'is_active' => true]));
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $this->putJson(self::API.'/admin/orders/'.$order->order_no.'/status', ['status' => 'delivered'])
            ->assertOk()
            ->assertJsonPath('data.status', 'delivered')
            ->assertJsonPath('data.payment_status', 'paid');
    }

    public function test_cod_can_be_switched_off(): void
    {
        config(['kokango.cod.enabled' => false]);

        [, $res] = $this->placeGuestCod(1);
        $res->assertStatus(409);

        $this->assertSame(0, Order::count());
        $this->assertSame(10, $this->variant->fresh()->stock);

        $this->getJson(self::API.'/settings/public')->assertJsonPath('data.cod_enabled', false);
    }

    public function test_cod_has_an_order_value_limit(): void
    {
        config(['kokango.cod.max_paise' => 50000]); // Rs 500

        [, $res] = $this->placeGuestCod(2); // total Rs 937.80
        $res->assertStatus(409);

        $this->assertSame(0, Order::count());
        $this->assertSame(10, $this->variant->fresh()->stock);
    }

    public function test_cancelling_an_unpaid_cod_order_restores_stock(): void
    {
        Mail::fake();

        $user = Model::unguarded(fn () => User::create(['name' => 'Rohan', 'email' => 'rohan@example.com', 'phone' => '9123456780', 'password' => bcrypt('Password@123'), 'is_active' => true]));
        $user->assignRole('customer');
        Sanctum::actingAs($user);

        $this->postJson(self::API.'/cart/items', ['variant_id' => $this->variant->id, 'qty' => 3])->assertSuccessful();
        $res = $this->postJson(self::API.'/checkout', $this->payload(['customer' => null]))->assertCreated();
        $orderNo = $res->json('data.order.order_no');
        $this->assertSame(7, $this->variant->fresh()->stock);

        $this->postJson(self::API.'/orders/'.$orderNo.'/cancel')
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertSame(10, $this->variant->fresh()->stock);
        $this->assertSame('failed', Payment::first()->status);
    }

    public function test_online_payment_sends_the_confirmation_email_once(): void
    {
        Mail::fake();

        $token = $this->cartToken();
        $this->addToCart($token, 1);
        $res = $this->withHeader('X-Cart-Token', $token)
            ->postJson(self::API.'/checkout', $this->payload(['payment_method' => 'online']))
            ->assertCreated()
            ->assertJsonPath('data.payment.gateway', 'fake');

        // Nothing is sent before the money arrives.
        Mail::assertNothingSent();

        $verify = [
            'order_no' => $res->json('data.order.order_no'),
            'token' => $res->json('data.order.token'),
            'gateway_order_id' => $res->json('data.payment.gateway_order_id'),
            'gateway_payment_id' => 'pay_fake_1',
            'signature' => 'fake',
        ];
        $this->postJson(self::API.'/payments/verify', $verify)->assertOk();
        $this->postJson(self::API.'/payments/verify', $verify)->assertOk(); // repeat call

        Mail::assertSent(OrderMail::class, 2); // customer + owner, only once
        Mail::assertSent(OrderMail::class, fn (OrderMail $m) => $m->kind === 'placed' && $m->hasTo('asha@example.com'));
    }

    public function test_a_broken_mail_server_never_breaks_checkout(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP is down'));

        [, $res] = $this->placeGuestCod(1);

        $res->assertCreated()->assertJsonPath('data.order.status', 'confirmed');
    }
}
