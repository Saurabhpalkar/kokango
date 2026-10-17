<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Vendor panel + admin marketplace API (sellers, listing review, split orders, commission, payouts). */
class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    private const API = '/api/v1';

    private const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    private Category $category;

    private ProductVariant $kokangoVariant;

    private string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();

        config(['kokango.payment.driver' => 'fake', 'kokango.shipping.driver' => 'fake', 'kokango.cod.enabled' => true, 'kokango.cod.max_paise' => 0]);
        Storage::fake('public');
        $this->seed(RoleSeeder::class);

        $this->category = Category::create(['name' => 'Snacks', 'slug' => 'snacks', 'is_active' => true]);
        $own = Product::create(['category_id' => $this->category->id, 'name' => 'Jackfruit Chips', 'slug' => 'jackfruit-chips', 'is_active' => true]);
        $this->kokangoVariant = ProductVariant::create([
            'product_id' => $own->id, 'sku' => 'JC-100', 'size_label' => '100g', 'price_paise' => 10000, 'stock' => 50, 'is_active' => true,
        ]);

        $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'Admin@12345', 'is_active' => true]);
        $admin->assignRole('admin');
        $this->adminToken = $admin->createToken('test')->plainTextToken;
    }

    // ------------------------------------------------------------------ helpers

    private function client(?string $token = null, ?string $cartToken = null): static
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        if ($token) {
            $this->withToken($token);
        }
        if ($cartToken) {
            $this->withHeader('X-Cart-Token', $cartToken);
        }

        return $this;
    }

    private function admin(): static
    {
        return $this->client($this->adminToken);
    }

    /** Registers a seller and returns [token, vendor id]. */
    private function register(string $email = 'seller@example.com'): array
    {
        $res = $this->client()->postJson(self::API.'/vendor/auth/register', [
            'store_name' => 'Sahyadri Farms', 'owner_name' => 'Saurabh Patil', 'email' => $email,
            'phone' => '9876500000', 'city' => 'Nashik', 'password' => 'Vendor@12345',
        ])->assertCreated()
            ->assertJsonPath('data.vendor.status', 'pending')
            ->assertJsonPath('data.vendor.commission_percent', 10);

        return [$res->json('data.token'), $res->json('data.vendor.id')];
    }

    private function completeStore(string $token): void
    {
        $this->client($token)->putJson(self::API.'/vendor/store', [
            'store_name' => 'Sahyadri Farms', 'owner_name' => 'Saurabh Patil', 'email' => 'seller@example.com', 'phone' => '9876500000',
            'city' => 'Nashik', 'pincode' => '422001', 'pickup_address' => '11 Market Road', 'fssai_no' => '11522001001001',
            'bank' => ['account_name' => 'Sahyadri Farms', 'account_no' => '000123456789', 'ifsc' => 'hdfc0001234', 'bank_name' => 'HDFC Bank'],
        ])->assertOk()->assertJsonPath('data.bank.ifsc', 'HDFC0001234');
    }

    /** An approved seller with one live product: returns [token, vendor id, variant]. */
    private function approvedSellerWithProduct(): array
    {
        [$token, $vendorId] = $this->register();
        $this->completeStore($token);
        $this->admin()->postJson(self::API."/admin/vendors/$vendorId/approve")->assertOk()->assertJsonPath('data.status', 'approved');

        $productId = $this->client($token)->postJson(self::API.'/vendor/products', $this->productForm())
            ->assertCreated()->json('data.id');
        $this->admin()->postJson(self::API."/admin/product-approvals/$productId/approve")->assertOk();

        return [$token, $vendorId, ProductVariant::where('product_id', $productId)->firstOrFail()];
    }

    private function productForm(array $extra = []): array
    {
        return array_merge([
            'name' => 'Banana Chips', 'description' => 'Crisp.', 'category_id' => $this->category->id, 'image_url' => self::PNG,
            'status' => 'pending_review',
            'variants' => [['id' => null, 'size_label' => '200g', 'sku' => '', 'price_paise' => 20000, 'mrp_paise' => 25000, 'stock' => 10]],
        ], $extra);
    }

    /** Guest buys the given variants with Cash on Delivery; returns the order number. */
    private function codOrder(array $lines): string
    {
        $cart = $this->client()->getJson(self::API.'/cart')->json('data.token');
        foreach ($lines as [$variant, $qty]) {
            $this->client(null, $cart)->postJson(self::API.'/cart/items', ['variant_id' => $variant->id, 'qty' => $qty])->assertSuccessful();
        }

        return $this->client(null, $cart)->postJson(self::API.'/checkout', [
            'shipping_method' => 'standard', 'payment_method' => 'cod',
            'customer' => ['name' => 'Asha Rao', 'email' => 'asha@example.com', 'phone' => '9876543210'],
            'address' => ['name' => 'Asha Rao', 'phone' => '9876543210', 'line1' => '12 MG Road', 'city' => 'Pune', 'state' => 'Maharashtra', 'pincode' => '411001'],
        ])->assertCreated()->json('data.order.order_no');
    }

    // ------------------------------------------------------------------ accounts

    public function test_register_login_and_approval_rules(): void
    {
        [$token, $vendorId] = $this->register();

        $this->client($token)->getJson(self::API.'/vendor/auth/me')->assertOk()->assertJsonPath('data.store_name', 'Sahyadri Farms');
        $this->client()->postJson(self::API.'/vendor/auth/register', [
            'store_name' => 'X', 'owner_name' => 'Y', 'email' => 'seller@example.com', 'phone' => '9876500001', 'city' => 'Pune', 'password' => 'Vendor@12345',
        ])->assertStatus(422)->assertJsonValidationErrors('email');

        $this->client()->postJson(self::API.'/vendor/auth/login', ['login' => 'seller@example.com', 'password' => 'wrong'])->assertStatus(422);
        $this->client()->postJson(self::API.'/vendor/auth/login', ['login' => 'SELLER@example.com', 'password' => 'Vendor@12345'])
            ->assertOk()->assertJsonStructure(['data' => ['token', 'vendor' => ['id', 'status', 'bank']]]);

        // Approval needs the checklist (pickup address, FSSAI, bank).
        $this->admin()->postJson(self::API."/admin/vendors/$vendorId/approve")->assertStatus(409);
        $this->completeStore($token);
        $this->admin()->getJson(self::API."/admin/vendors/$vendorId")->assertOk()
            ->assertJsonPath('data.checklist.3.ok', true);
        $this->admin()->postJson(self::API."/admin/vendors/$vendorId/approve")->assertOk();

        // Suspend needs a reason; a suspended seller cannot sign in.
        $this->admin()->postJson(self::API."/admin/vendors/$vendorId/suspend")->assertStatus(422)->assertJsonValidationErrors('reason');
        $this->admin()->postJson(self::API."/admin/vendors/$vendorId/suspend", ['reason' => 'Late dispatch'])->assertOk()
            ->assertJsonPath('data.status', 'suspended');
        $this->client()->postJson(self::API.'/vendor/auth/login', ['login' => 'seller@example.com', 'password' => 'Vendor@12345'])
            ->assertForbidden()->assertJsonPath('message', 'Your store is suspended: Late dispatch Please contact Kokango support.');

        // Customers and sellers are kept apart.
        $customer = User::create(['name' => 'C', 'email' => 'c@example.com', 'password' => 'Secret@123', 'is_active' => true]);
        $customer->assignRole('customer');
        $this->client($customer->createToken('t')->plainTextToken)->getJson(self::API.'/vendor/dashboard')->assertForbidden();
        $this->client($token)->getJson(self::API.'/admin/vendors')->assertForbidden();
        $this->client()->getJson(self::API.'/vendor/dashboard')->assertUnauthorized();
        $this->admin()->getJson(self::API.'/admin/roles')->assertOk()->assertExactJson(['data' => ['admin', 'customer']]);
    }

    public function test_admin_creates_lists_and_edits_vendors(): void
    {
        $res = $this->admin()->postJson(self::API.'/admin/vendors', [
            'store_name' => 'Konkan Kitchen', 'owner_name' => 'Meena', 'phone' => '98702 22222', 'email' => 'konkan@example.com',
            'password' => 'Vendor@12345', 'city' => 'Ratnagiri', 'commission_percent' => '8', 'approve_now' => true,
            'bank' => ['account_name' => '', 'account_no' => '', 'ifsc' => '', 'bank_name' => ''],
        ])->assertCreated()->assertJsonPath('data.status', 'approved')->assertJsonPath('data.commission_percent', 8);
        $id = $res->json('data.id');

        $this->admin()->getJson(self::API.'/admin/vendors?status=approved')->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('counts.approved', 1)
            ->assertJsonPath('data.0.commission_percent', 8)
            ->assertJsonPath('data.0.commission_percent_override', 8);

        // Clearing the override falls back to the default (10%).
        $vendor = $this->admin()->getJson(self::API."/admin/vendors/$id")->json('data.vendor');
        $this->admin()->putJson(self::API."/admin/vendors/$id", array_merge($vendor, ['commission_percent' => '']))->assertOk()
            ->assertJsonPath('data.commission_percent', null);
        $this->admin()->getJson(self::API."/admin/vendors/$id")->assertJsonPath('data.stats.commission_percent', 10);

        $this->client()->postJson(self::API.'/vendor/auth/login', ['login' => 'konkan@example.com', 'password' => 'Vendor@12345'])->assertOk();
    }

    // ------------------------------------------------------------------ listings

    public function test_listing_review_controls_what_the_shop_shows(): void
    {
        [$token, $vendorId] = $this->register();
        $this->completeStore($token);

        $res = $this->client($token)->postJson(self::API.'/vendor/products', $this->productForm())->assertCreated()
            ->assertJsonPath('data.status', 'pending_review')
            ->assertJsonPath('data.min_price_paise', 20000)
            ->assertJsonPath('data.stock_total', 10);
        $id = $res->json('data.id');
        $image = Product::find($id)->image;
        $this->assertStringStartsWith('products/vendor-', $image);
        Storage::disk('public')->assertExists($image);

        $shop = fn () => collect($this->client()->getJson(self::API.'/products')->json('data'))->pluck('name')->all();
        $this->assertNotContains('Banana Chips', $shop());

        // Approved listing of a store that is not approved yet stays hidden.
        $this->admin()->getJson(self::API.'/admin/product-approvals')->assertOk()
            ->assertJsonPath('counts.pending_review', 1)->assertJsonPath('data.0.vendor_name', 'Sahyadri Farms');
        $this->admin()->postJson(self::API."/admin/product-approvals/$id/approve")->assertOk()->assertJsonPath('data.status', 'active');
        $this->assertNotContains('Banana Chips', $shop());

        $this->admin()->postJson(self::API."/admin/vendors/$vendorId/approve")->assertOk();
        $this->assertContains('Banana Chips', $shop());

        // Editing a live listing sends it back to review (and off the shop).
        $form = $this->client($token)->getJson(self::API."/vendor/products/$id")->assertOk()->json('data');
        $this->client($token)->putJson(self::API."/vendor/products/$id", $this->productForm([
            'image_url' => $form['image_url'],
            'variants' => [array_merge($form['variants'][0], ['stock' => 4]), ['size_label' => '500g', 'price_paise' => 45000, 'stock' => 2]],
        ]))->assertOk()->assertJsonPath('data.status', 'pending_review')->assertJsonCount(2, 'data.variants');
        $this->assertSame($image, Product::find($id)->image);
        $this->assertNotContains('Banana Chips', $shop());

        // Reject needs a reason, which the vendor sees.
        $this->admin()->postJson(self::API."/admin/product-approvals/$id/reject")->assertStatus(422)->assertJsonValidationErrors('reason');
        $this->admin()->postJson(self::API."/admin/product-approvals/$id/reject", ['reason' => 'Add ingredients'])->assertOk();
        $this->client($token)->getJson(self::API.'/vendor/products?status=rejected')->assertOk()
            ->assertJsonPath('data.0.review_note', 'Add ingredients');

        // Auto-approve: submissions of an approved store go live at once.
        $this->admin()->putJson(self::API.'/admin/marketplace/settings', ['default_commission' => 12, 'min_payout_paise' => 0, 'auto_approve_products' => true])
            ->assertOk()->assertJsonPath('data.auto_approve_products', true);
        $this->client($token)->postJson(self::API.'/vendor/products', $this->productForm(['name' => 'Kokum Sarbat']))->assertCreated()
            ->assertJsonPath('data.status', 'active');
        $this->assertContains('Kokum Sarbat', $shop());
        $this->client($token)->getJson(self::API.'/vendor/auth/me')->assertJsonPath('data.commission_percent', 12);

        // Suspending hides everything; reactivating restores it.
        $this->admin()->postJson(self::API."/admin/vendors/$vendorId/suspend", ['reason' => 'Audit'])->assertOk();
        $this->assertNotContains('Kokum Sarbat', $shop());
        $this->admin()->postJson(self::API."/admin/vendors/$vendorId/reactivate")->assertOk();
        $this->assertContains('Kokum Sarbat', $shop());
    }

    public function test_listing_validation_and_ownership(): void
    {
        [$tokenA] = $this->register('a@example.com');
        [$tokenB] = $this->register('b@example.com');

        $id = $this->client($tokenA)->postJson(self::API.'/vendor/products', $this->productForm(['status' => 'draft']))->assertCreated()
            ->assertJsonPath('data.status', 'draft')->json('data.id');

        // Another seller cannot see, edit or delete it.
        $this->client($tokenB)->getJson(self::API."/vendor/products/$id")->assertNotFound();
        $this->client($tokenB)->putJson(self::API."/vendor/products/$id", $this->productForm())->assertNotFound();
        $this->client($tokenB)->deleteJson(self::API."/vendor/products/$id")->assertNotFound();
        $this->client($tokenB)->getJson(self::API.'/vendor/products')->assertOk()->assertJsonPath('meta.total', 0);
        $variantId = ProductVariant::where('product_id', $id)->value('id');
        $this->client($tokenB)->postJson(self::API."/vendor/inventory/$variantId/adjust", ['stock' => 99])->assertNotFound();

        // Validation: SKU taken, no sizes, bad image.
        $this->client($tokenB)->postJson(self::API.'/vendor/products', $this->productForm([
            'variants' => [['size_label' => '1kg', 'sku' => 'JC-100', 'price_paise' => 100, 'stock' => 1]],
        ]))->assertStatus(422)->assertJsonValidationErrors('variants.0.sku');
        $this->client($tokenB)->postJson(self::API.'/vendor/products', $this->productForm(['variants' => []]))
            ->assertStatus(422)->assertJsonValidationErrors('variants');
        $this->client($tokenB)->postJson(self::API.'/vendor/products', $this->productForm(['image_url' => 'data:image/png;base64,bm90IGFuIGltYWdl']))
            ->assertStatus(422)->assertJsonValidationErrors('image_url');
        $this->client($tokenB)->postJson(self::API.'/vendor/products', $this->productForm(['image_url' => 'https://evil.example/x.png']))
            ->assertStatus(422)->assertJsonValidationErrors('image_url');

        // Inventory: own sizes only, absolute stock.
        $this->client($tokenA)->getJson(self::API.'/vendor/inventory')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.stock', 10);
        $this->client($tokenA)->postJson(self::API."/vendor/inventory/$variantId/adjust", ['stock' => 3])->assertOk()
            ->assertJsonPath('data.stock', 3)->assertJsonPath('data.low', true);
        $this->client($tokenA)->postJson(self::API."/vendor/inventory/$variantId/adjust", ['stock' => -1])->assertStatus(422);

        // Never sold: deleting really deletes.
        $this->client($tokenA)->deleteJson(self::API."/vendor/products/$id")->assertOk();
        $this->assertNull(Product::find($id));
    }

    // ------------------------------------------------------------------ orders & money

    public function test_split_order_commission_fulfilment_and_payout(): void
    {
        [$token, $vendorId, $variant] = $this->approvedSellerWithProduct();

        // Mixed basket: 2 x seller's chips (2 x 200.00) + 1 x Kokango chips.
        $orderNo = $this->codOrder([[$variant, 2], [$this->kokangoVariant, 1]]);

        $vo = VendorOrder::where('vendor_id', $vendorId)->firstOrFail();
        $this->assertSame(40000, $vo->subtotal_paise);
        $this->assertSame(4000, $vo->commission_paise);   // 10% default
        $this->assertSame(36000, $vo->earning_paise);

        // The seller sees only its own items.
        $this->client($token)->getJson(self::API.'/vendor/orders')->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.order_no', $orderNo)
            ->assertJsonPath('data.0.status', 'pending')
            ->assertJsonCount(1, 'data.0.items')
            ->assertJsonPath('data.0.items.0.qty', 2)
            ->assertJsonPath('data.0.address.pincode', '411001');

        // Steps in order only; a mixed order cannot be rejected by one seller.
        $this->client($token)->putJson(self::API."/vendor/orders/$orderNo/status", ['status' => 'shipped'])->assertStatus(409);
        $this->client($token)->putJson(self::API."/vendor/orders/$orderNo/status", ['status' => 'cancelled'])->assertStatus(409);
        foreach (['confirmed', 'packed', 'shipped'] as $step) {
            $this->client($token)->putJson(self::API."/vendor/orders/$orderNo/status", ['status' => $step])->assertOk()
                ->assertJsonPath('data.status', $step);
        }
        // Kokango still has to send its own item, so the customer's order is not "shipped" yet.
        $this->assertSame('confirmed', Order::where('order_no', $orderNo)->value('status'));

        $this->client($token)->getJson(self::API.'/vendor/dashboard')->assertOk()
            ->assertJsonPath('data.stats.total_sales_paise', 40000)
            ->assertJsonPath('data.stats.earnings_paise', 36000)
            ->assertJsonCount(7, 'data.sales_by_day')
            ->assertJsonPath('data.recent_orders.0.order_no', $orderNo);

        // Nothing payable before delivery.
        $this->admin()->postJson(self::API.'/admin/vendor-payouts', ['vendor_id' => $vendorId])->assertStatus(409);

        $this->admin()->putJson(self::API."/admin/orders/$orderNo/status", ['status' => 'delivered'])->assertOk();
        $this->assertSame('delivered', $vo->fresh()->status);
        $this->client($token)->getJson(self::API.'/vendor/payouts')->assertOk()->assertJsonPath('data.summary.available_paise', 36000);

        // Minimum payout (default Rs 500 = 50000 paise) blocks it; lower it and pay.
        $this->admin()->postJson(self::API.'/admin/vendor-payouts', ['vendor_id' => $vendorId])->assertStatus(409)
            ->assertJsonPath('message', 'Balance is below the minimum payout amount.');
        $this->admin()->putJson(self::API.'/admin/marketplace/settings', ['default_commission' => 10, 'min_payout_paise' => 10000, 'auto_approve_products' => false])->assertOk();
        $this->admin()->getJson(self::API.'/admin/vendor-payouts')->assertOk()
            ->assertJsonPath('data.summary.payable_paise', 36000)
            ->assertJsonPath('data.vendors.0.min_ok', true);

        $payoutId = $this->admin()->postJson(self::API.'/admin/vendor-payouts', ['vendor_id' => $vendorId])->assertCreated()
            ->assertJsonPath('data.amount_paise', 36000)
            ->assertJsonPath('data.status', 'scheduled')
            ->json('data.id');
        $this->admin()->postJson(self::API.'/admin/vendor-payouts', ['vendor_id' => $vendorId])->assertStatus(409); // already settled

        $this->admin()->postJson(self::API."/admin/vendor-payouts/$payoutId/status", ['status' => 'paid'])->assertStatus(422)->assertJsonValidationErrors('utr');
        $this->admin()->postJson(self::API."/admin/vendor-payouts/$payoutId/status", ['status' => 'processing'])->assertOk();
        $this->admin()->postJson(self::API."/admin/vendor-payouts/$payoutId/status", ['status' => 'paid', 'utr' => 'UTR123'])->assertOk()
            ->assertJsonPath('data.utr', 'UTR123');

        $this->client($token)->getJson(self::API.'/vendor/payouts')->assertOk()
            ->assertJsonPath('data.summary.available_paise', 0)
            ->assertJsonPath('data.summary.paid_paise', 36000)
            ->assertJsonPath('data.payouts.0.status', 'paid');

        $this->admin()->getJson(self::API.'/admin/marketplace/overview')->assertOk()
            ->assertJsonPath('data.stats.gmv_paise', 40000)
            ->assertJsonPath('data.stats.commission_paise', 4000)
            ->assertJsonPath('data.stats.orders', 1)
            ->assertJsonPath('data.top_vendors.0.store_name', 'Sahyadri Farms')
            ->assertJsonCount(14, 'data.sales_by_day');
    }

    public function test_seller_only_order_ships_with_seller_and_reject_cancels_it(): void
    {
        [$token, , $variant] = $this->approvedSellerWithProduct();

        // Seller-only order: when the seller ships, the customer's order is shipped too.
        $first = $this->codOrder([[$variant, 1]]);
        foreach (['confirmed', 'packed', 'shipped'] as $step) {
            $this->client($token)->putJson(self::API."/vendor/orders/$first/status", ['status' => $step])->assertOk();
        }
        $this->assertSame('shipped', Order::where('order_no', $first)->value('status'));

        // Rejecting a new seller-only order cancels it and puts the stock back.
        $before = $variant->fresh()->stock;
        $second = $this->codOrder([[$variant, 2]]);
        $this->assertSame($before - 2, $variant->fresh()->stock);
        $this->client($token)->putJson(self::API."/vendor/orders/$second/status", ['status' => 'cancelled'])->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
        $this->assertSame('cancelled', Order::where('order_no', $second)->value('status'));
        $this->assertSame($before, $variant->fresh()->stock);
        $this->client($token)->getJson(self::API.'/vendor/orders?status=cancelled')->assertJsonPath('meta.total', 1);
    }

    public function test_unpaid_cod_order_is_never_expired(): void
    {
        $orderNo = $this->codOrder([[$this->kokangoVariant, 2]]);
        // Staff moved it back to "pending" and it is old: still not an abandoned online payment.
        Order::where('order_no', $orderNo)->update(['status' => 'pending', 'placed_at' => now()->subDay()]);

        $this->artisan('kokango:expire-pending-orders')->assertSuccessful();

        $this->assertSame('pending', Order::where('order_no', $orderNo)->value('payment_status'));
        $this->assertSame(48, $this->kokangoVariant->fresh()->stock);

        // A "payment failed" call (e.g. a stale checkout tab) must not release the stock of a COD order.
        $token = Order::where('order_no', $orderNo)->value('public_token');
        $this->client()->postJson(self::API.'/payments/failed', ['order_no' => $orderNo, 'token' => $token, 'reason' => 'x'])->assertOk();
        $this->assertSame('pending', Order::where('order_no', $orderNo)->value('payment_status'));
        $this->assertSame(48, $this->kokangoVariant->fresh()->stock);
    }

    public function test_online_order_reaches_seller_only_after_payment_and_uses_override_rate(): void
    {
        [$token, $vendorId, $variant] = $this->approvedSellerWithProduct();
        Vendor::whereKey($vendorId)->update(['commission_percent' => 8]);

        $cart = $this->client()->getJson(self::API.'/cart')->json('data.token');
        $this->client(null, $cart)->postJson(self::API.'/cart/items', ['variant_id' => $variant->id, 'qty' => 1])->assertSuccessful();
        $res = $this->client(null, $cart)->postJson(self::API.'/checkout', [
            'shipping_method' => 'standard',
            'customer' => ['name' => 'Asha Rao', 'email' => 'asha@example.com', 'phone' => '9876543210'],
            'address' => ['name' => 'Asha Rao', 'phone' => '9876543210', 'line1' => '12 MG Road', 'city' => 'Pune', 'state' => 'Maharashtra', 'pincode' => '411001'],
        ])->assertCreated();

        // Unpaid: the seller sees nothing yet.
        $this->client($token)->getJson(self::API.'/vendor/orders')->assertJsonPath('meta.total', 0);

        $this->client(null, $cart)->postJson(self::API.'/payments/verify', [
            'order_no' => $res->json('data.order.order_no'),
            'token' => $res->json('data.order.token'),
            'gateway_order_id' => $res->json('data.payment.gateway_order_id'),
            'gateway_payment_id' => 'pay_fake_1',
            'signature' => 'fake',
        ])->assertOk()->assertJsonPath('data.status', 'confirmed');

        $this->client($token)->getJson(self::API.'/vendor/orders')->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.commission_paise', 1600)   // 8% of 200.00
            ->assertJsonPath('data.0.earning_paise', 18400);

        // Admin cancelling the order cancels the seller's part.
        $this->admin()->putJson(self::API.'/admin/orders/'.$res->json('data.order.order_no').'/status', ['status' => 'cancelled'])->assertOk();
        $this->client($token)->getJson(self::API.'/vendor/orders')->assertJsonPath('data.0.status', 'cancelled');
    }
}
