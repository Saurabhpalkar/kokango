<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponsAndReviewsTest extends TestCase
{
    use RefreshDatabase;

    private const API = '/api/v1';

    private ProductVariant $variant;

    private Product $product;

    private string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();
        config(['kokango.payment.driver' => 'fake', 'kokango.shipping.driver' => 'fake', 'kokango.cod.enabled' => true, 'kokango.cod.max_paise' => 0]);
        $this->seed(RoleSeeder::class);

        $cat = Category::create(['name' => 'Powders', 'slug' => 'powders', 'is_active' => true]);
        $this->product = Product::create(['category_id' => $cat->id, 'name' => 'Moringa Powder', 'slug' => 'moringa-powder', 'is_active' => true]);
        $this->variant = ProductVariant::create(['product_id' => $this->product->id, 'sku' => 'MOR-100', 'size_label' => '100g', 'price_paise' => 50000, 'stock' => 50, 'is_active' => true]);

        $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'Admin@12345', 'is_active' => true]);
        $admin->assignRole('admin');
        $this->adminToken = $admin->createToken('t')->plainTextToken;
    }

    private function client(?string $token = null, ?string $cart = null): static
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        if ($token) {
            $this->withToken($token);
        }
        if ($cart) {
            $this->withHeader('X-Cart-Token', $cart);
        }

        return $this;
    }

    private function cartWith(int $qty, ?string $token = null): ?string
    {
        $cart = $token ? null : $this->client()->getJson(self::API.'/cart')->json('data.token');
        $this->client($token, $cart)->postJson(self::API.'/cart/items', ['variant_id' => $this->variant->id, 'qty' => $qty])->assertSuccessful();

        return $cart;
    }

    private function codCheckout(?string $cart, ?string $token, ?string $code, string $email = 'asha@example.com')
    {
        $body = ['shipping_method' => 'standard', 'payment_method' => 'cod', 'coupon_code' => $code,
            'address' => ['name' => 'Asha Rao', 'phone' => '9876543210', 'line1' => '12 MG Road', 'city' => 'Pune', 'state' => 'Maharashtra', 'pincode' => '411001']];
        if (! $token) {
            $body['customer'] = ['name' => 'Asha Rao', 'email' => $email, 'phone' => '9876543210'];
        }

        return $this->client($token, $cart)->postJson(self::API.'/checkout', $body);
    }

    public function test_admin_manages_coupons(): void
    {
        $this->client($this->adminToken)->postJson(self::API.'/admin/coupons', ['code' => 'bad code', 'type' => 'percent', 'value' => 10])
            ->assertStatus(422)->assertJsonValidationErrors('code');
        $this->client($this->adminToken)->postJson(self::API.'/admin/coupons', ['code' => 'MONSOON', 'type' => 'percent', 'value' => 95])
            ->assertStatus(422)->assertJsonValidationErrors('value');

        $id = $this->client($this->adminToken)->postJson(self::API.'/admin/coupons', ['code' => 'monsoon10', 'type' => 'percent', 'value' => 10, 'max_discount_paise' => 10000])
            ->assertCreated()->assertJsonPath('data.code', 'MONSOON10')->assertJsonPath('data.state', 'active')->json('data.id');
        $this->client($this->adminToken)->postJson(self::API.'/admin/coupons', ['code' => 'MONSOON10', 'type' => 'fixed', 'value' => 100])
            ->assertStatus(422)->assertJsonValidationErrors('code');
        $this->client($this->adminToken)->getJson(self::API.'/admin/coupons')->assertOk()->assertJsonCount(1, 'data');
        $this->client($this->adminToken)->putJson(self::API."/admin/coupons/$id", ['code' => 'MONSOON10', 'type' => 'percent', 'value' => 10, 'is_active' => false])
            ->assertOk()->assertJsonPath('data.state', 'off');
        $this->client($this->adminToken)->deleteJson(self::API."/admin/coupons/$id")->assertOk();
        $this->assertSame(0, Coupon::count());

        // customers cannot manage coupons
        $c = User::create(['name' => 'C', 'email' => 'c@example.com', 'password' => 'Secret@123', 'is_active' => true]);
        $c->assignRole('customer');
        $this->client($c->createToken('t')->plainTextToken)->getJson(self::API.'/admin/coupons')->assertForbidden();
    }

    public function test_coupon_discount_tax_and_rules(): void
    {
        Coupon::create(['code' => 'SAVE10', 'type' => 'percent', 'value' => 10, 'max_discount_paise' => 15000, 'min_subtotal_paise' => 60000, 'usage_limit' => 2]);
        Coupon::create(['code' => 'OLD', 'type' => 'fixed', 'value' => 5000, 'ends_at' => now()->subDay()]);

        $cart = $this->cartWith(1);   // subtotal 500.00
        $quote = fn ($code) => $this->client(null, $cart)->postJson(self::API.'/checkout/quote', ['shipping_method' => 'standard', 'coupon_code' => $code]);

        $quote('save10')->assertStatus(422)->assertJsonPath('errors.coupon_code.0', 'Add items worth Rs 600 or more to use this coupon.');
        $quote('OLD')->assertStatus(422)->assertJsonPath('errors.coupon_code.0', 'This coupon has expired.');
        $quote('NOPE')->assertStatus(422)->assertJsonPath('errors.coupon_code.0', 'This coupon code is not valid.');

        $this->client(null, $cart)->postJson(self::API.'/cart/items', ['variant_id' => $this->variant->id, 'qty' => 1])->assertSuccessful(); // 1000.00
        // 10% of 1000 = 100.00 -> GST 5% on 900.00 = 45.00; shipping 60.00; total 900 + 60 + 45 = 1005.00
        $quote('save10')->assertOk()
            ->assertJsonPath('data.discount_paise', 10000)
            ->assertJsonPath('data.tax_paise', 4500)
            ->assertJsonPath('data.total_paise', 100500)
            ->assertJsonPath('data.coupon.code', 'SAVE10');

        $order = $this->codCheckout($cart, null, 'SAVE10')->assertCreated()
            ->assertJsonPath('data.order.discount_paise', 10000)
            ->assertJsonPath('data.order.total_paise', 100500)
            ->assertJsonPath('data.order.coupon_code', 'SAVE10')->json('data.order.order_no');
        $this->assertSame(1, Coupon::where('code', 'SAVE10')->value('used_count'));

        // same customer email cannot use it twice
        $cart2 = $this->cartWith(2);
        $this->codCheckout($cart2, null, 'SAVE10')->assertStatus(422)->assertJsonPath('errors.coupon_code.0', 'You have already used this coupon.');

        // cancelling gives the use back
        $this->client($this->adminToken)->putJson(self::API."/admin/orders/$order/status", ['status' => 'cancelled'])->assertOk();
        $this->assertSame(0, Coupon::where('code', 'SAVE10')->value('used_count'));

        // usage limit: two other customers use it, the third is refused
        $this->codCheckout($this->cartWith(2), null, 'SAVE10', 'b@example.com')->assertCreated();
        $this->codCheckout($this->cartWith(2), null, 'SAVE10', 'c@example.com')->assertCreated();
        $this->codCheckout($this->cartWith(2), null, 'SAVE10', 'd@example.com')->assertStatus(422)->assertJsonPath('errors.coupon_code.0', 'This coupon has been fully used.');
        $this->assertSame(2, Coupon::where('code', 'SAVE10')->value('used_count'));

        // a refused coupon creates no order and keeps the stock
        $this->assertSame(3, Order::count());
    }

    public function test_reviews_only_from_buyers_and_moderation(): void
    {
        $buyer = User::create(['name' => 'Priya Sharma', 'email' => 'priya@example.com', 'password' => 'Secret@123', 'is_active' => true]);
        $buyer->assignRole('customer');
        $buyerToken = $buyer->createToken('t')->plainTextToken;
        $stranger = User::create(['name' => 'Ravi', 'email' => 'ravi@example.com', 'password' => 'Secret@123', 'is_active' => true]);
        $stranger->assignRole('customer');
        $strangerToken = $stranger->createToken('t')->plainTextToken;
        $url = self::API.'/products/moringa-powder/reviews';
        $review = ['rating' => 5, 'title' => 'Fresh!', 'body' => 'Fine powder and a fresh smell, I use it every day.'];

        $this->client()->postJson($url, $review)->assertUnauthorized();
        $this->client($strangerToken)->postJson($url, $review)->assertForbidden();
        $this->client($strangerToken)->getJson($url)->assertOk()->assertJsonPath('viewer.can_review', false);

        // the buyer orders (COD confirms at once) and can then review
        $this->cartWith(1, $buyerToken);
        $this->codCheckout(null, $buyerToken, null)->assertCreated();
        $this->client($buyerToken)->getJson($url)->assertOk()->assertJsonPath('viewer.can_review', true)->assertJsonPath('viewer.review', null);
        $this->client($buyerToken)->postJson($url, ['rating' => 6, 'body' => 'short'])->assertStatus(422)->assertJsonValidationErrors(['rating', 'body']);
        $this->client($buyerToken)->postJson($url, $review)->assertCreated()->assertJsonPath('data.name', 'Priya S.');
        $this->client($buyerToken)->postJson($url, array_merge($review, ['rating' => 4]))->assertOk();   // edit, not a second review

        $this->client()->getJson($url)->assertOk()
            ->assertJsonPath('summary.count', 1)->assertJsonPath('summary.average', 4)
            ->assertJsonPath('summary.stars.4', 1)->assertJsonPath('data.0.title', 'Fresh!');
        $this->client()->getJson(self::API.'/products/moringa-powder')->assertOk()
            ->assertJsonPath('data.rating_count', 1)->assertJsonPath('data.rating_avg', 4);

        // admin hides it: gone from the shop, still listed for the admin
        $id = $this->client($this->adminToken)->getJson(self::API.'/admin/reviews')->assertOk()->assertJsonPath('counts.published', 1)->json('data.0.id');
        $this->client($this->adminToken)->putJson(self::API."/admin/reviews/$id", ['status' => 'hidden'])->assertOk();
        $this->client()->getJson($url)->assertJsonPath('summary.count', 0);
        $this->client()->getJson(self::API.'/products')->assertJsonPath('data.0.rating_count', 0);
        // the customer editing a hidden review does not publish it again
        $this->client($buyerToken)->postJson($url, $review)->assertOk();
        $this->client()->getJson($url)->assertJsonPath('summary.count', 0);
        $this->client($this->adminToken)->deleteJson(self::API."/admin/reviews/$id")->assertOk();
        $this->client($this->adminToken)->getJson(self::API.'/admin/reviews')->assertJsonPath('counts.all', 0);
    }
}
