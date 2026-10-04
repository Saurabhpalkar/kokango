<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAndCatalogueTest extends TestCase
{
    use RefreshDatabase;

    private const API = '/api/v1';

    protected function setUp(): void
    {
        parent::setUp();

        // Roles must exist before any user gets one.
        $this->seed(RoleSeeder::class);
    }

    /**
     * Start a fresh "client": forget the cached guard user (the app instance is shared between
     * requests inside one test) and reset headers, then optionally attach a bearer token.
     */
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

    private function makeUser(string $role = 'customer', array $attrs = []): User
    {
        static $n = 0;
        $n++;

        $user = User::create(array_merge([
            'name' => 'User '.$n,
            'email' => "user{$n}@example.com",
            'phone' => sprintf('90000%05d', $n),
            'password' => 'Secret@123',
            'is_active' => true,
        ], $attrs));
        $user->assignRole($role);

        return $user;
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    /** @return array<string, ProductVariant> */
    private function seedCatalogue(): array
    {
        $powders = Category::create(['name' => 'Herbal Powders', 'slug' => 'powders', 'is_active' => true, 'sort_order' => 1]);
        $snacks = Category::create(['name' => 'Snacks', 'slug' => 'snacks', 'is_active' => true, 'sort_order' => 2]);

        $moringa = Product::create([
            'category_id' => $powders->id, 'name' => 'Moringa Powder', 'slug' => 'moringa-powder',
            'hindi_name' => 'मोरिंगा', 'image' => '/images/moringa-powder.png', 'badge' => 'TOP', 'is_active' => true,
        ]);
        $chips = Product::create([
            'category_id' => $snacks->id, 'name' => 'Jackfruit Chips', 'slug' => 'jackfruit-chips', 'is_active' => true,
        ]);
        $tulsi = Product::create([
            'category_id' => $powders->id, 'name' => 'Tulsi Powder', 'slug' => 'tulsi-powder', 'is_active' => true,
        ]);
        $hidden = Product::create([
            'category_id' => $powders->id, 'name' => 'Hidden Powder', 'slug' => 'hidden-powder', 'is_active' => false,
        ]);

        $v = [];
        $v['moringa50'] = $moringa->variants()->create(['sku' => 'MP-50', 'size_label' => '50g', 'price_paise' => 11900, 'stock' => 10, 'is_active' => true]);
        $v['moringa100'] = $moringa->variants()->create(['sku' => 'MP-100', 'size_label' => '100g', 'price_paise' => 19900, 'mrp_paise' => 24900, 'stock' => 100, 'is_active' => true]);
        $v['chips'] = $chips->variants()->create(['sku' => 'JC-100', 'size_label' => '100g', 'price_paise' => 13900, 'stock' => 40, 'is_active' => true]);
        $v['tulsi'] = $tulsi->variants()->create(['sku' => 'TP-100', 'size_label' => '100g', 'price_paise' => 18900, 'stock' => 0, 'is_active' => true]);
        $v['hidden'] = $hidden->variants()->create(['sku' => 'HP-100', 'size_label' => '100g', 'price_paise' => 10000, 'stock' => 5, 'is_active' => true]);

        return $v;
    }

    // ------------------------------------------------------------------ auth

    public function test_register_login_me_logout(): void
    {
        $payload = [
            'name' => 'Asha Rao',
            'email' => 'asha@example.com',
            'phone' => '9876500001',
            'password' => 'Secret@123',
            'password_confirmation' => 'Secret@123',
        ];

        $res = $this->client()->postJson(self::API.'/auth/register', $payload)
            ->assertCreated()
            ->assertJsonStructure(['data' => [
                'user' => ['id', 'name', 'email', 'phone', 'is_active', 'roles', 'is_admin'],
                'token',
            ]])
            ->assertJsonPath('data.user.email', 'asha@example.com')
            ->assertJsonPath('data.user.roles', ['customer'])
            ->assertJsonPath('data.user.is_admin', false);
        $this->assertNotEmpty($res->json('data.token'));
        $this->assertDatabaseHas('users', ['email' => 'asha@example.com', 'is_active' => 1]);

        // Duplicate email and bad confirmation give 422 with an errors object.
        $this->client()->postJson(self::API.'/auth/register', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
        $this->client()->postJson(self::API.'/auth/register', array_merge($payload, [
            'email' => 'other@example.com', 'phone' => '9876500002', 'password_confirmation' => 'nope',
        ]))->assertStatus(422)->assertJsonValidationErrors(['password']);

        // Wrong password.
        $this->client()->postJson(self::API.'/auth/login', ['login' => 'asha@example.com', 'password' => 'wrong-password'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['login']);

        // Login by email and by 10-digit mobile.
        $this->client()->postJson(self::API.'/auth/login', ['login' => 'asha@example.com', 'password' => 'Secret@123'])
            ->assertOk()
            ->assertJsonPath('data.user.email', 'asha@example.com');
        $login = $this->client()->postJson(self::API.'/auth/login', ['login' => '9876500001', 'password' => 'Secret@123'])
            ->assertOk();
        $token = $login->json('data.token');
        $this->assertNotEmpty($token);

        // Protected route: 401 JSON without a token, user with one.
        $this->client()->getJson(self::API.'/auth/me')
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);
        $this->client($token)->getJson(self::API.'/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'asha@example.com')
            ->assertJsonPath('data.roles.0', 'customer');

        // Logout revokes the token.
        $this->client($token)->postJson(self::API.'/auth/logout')
            ->assertOk()
            ->assertJsonStructure(['message']);
        $this->client($token)->getJson(self::API.'/auth/me')->assertUnauthorized();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->makeUser('customer', ['email' => 'off@example.com', 'is_active' => false]);

        $this->client()->postJson(self::API.'/auth/login', ['login' => 'off@example.com', 'password' => 'Secret@123'])
            ->assertForbidden()
            ->assertJsonStructure(['message']);
    }

    public function test_profile_update_and_password_change(): void
    {
        $user = $this->makeUser('customer', ['email' => 'p@example.com']);
        $token = $this->tokenFor($user);

        $this->client($token)->putJson(self::API.'/auth/profile', [
            'name' => 'Renamed', 'email' => 'p@example.com', 'phone' => '9111111111',
        ])->assertOk()->assertJsonPath('data.name', 'Renamed');

        $this->client($token)->putJson(self::API.'/auth/password', [
            'current_password' => 'bad', 'password' => 'NewSecret@1', 'password_confirmation' => 'NewSecret@1',
        ])->assertStatus(422)->assertJsonValidationErrors(['current_password']);

        $this->client($token)->putJson(self::API.'/auth/password', [
            'current_password' => 'Secret@123', 'password' => 'NewSecret@1', 'password_confirmation' => 'NewSecret@1',
        ])->assertOk();

        $this->client()->postJson(self::API.'/auth/login', ['login' => 'p@example.com', 'password' => 'NewSecret@1'])
            ->assertOk();
    }

    // ------------------------------------------------------------- catalogue

    public function test_product_list_filters_sort_and_detail(): void
    {
        $this->seedCatalogue();

        // Only active products, paginated with meta.
        $this->client()->getJson(self::API.'/products')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonStructure([
                'data' => [['id', 'slug', 'name', 'hindi_name', 'description', 'image_url', 'images', 'badge',
                    'is_active', 'category' => ['id', 'slug', 'name'], 'min_price_paise',
                    'variants' => [['id', 'product_id', 'sku', 'size_label', 'price_paise', 'mrp_paise', 'stock', 'in_stock', 'is_active']]]],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        // Category filter.
        $this->client()->getJson(self::API.'/products?category=snacks')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'jackfruit-chips');
        $this->client()->getJson(self::API.'/products?category=powders')
            ->assertOk()->assertJsonCount(2, 'data');

        // Search (name).
        $this->client()->getJson(self::API.'/products?search=moringa')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'moringa-powder');

        // Price filters use the lowest active variant price.
        $this->client()->getJson(self::API.'/products?min_price=13000')
            ->assertOk()->assertJsonCount(2, 'data');
        $this->client()->getJson(self::API.'/products?max_price=12000')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'moringa-powder')
            ->assertJsonPath('data.0.min_price_paise', 11900);

        // Sorting.
        $this->client()->getJson(self::API.'/products?sort=price_asc')
            ->assertOk()->assertJsonPath('data.0.slug', 'moringa-powder');
        $this->client()->getJson(self::API.'/products?sort=price_desc')
            ->assertOk()->assertJsonPath('data.0.slug', 'tulsi-powder');
        $this->client()->getJson(self::API.'/products?sort=newest')
            ->assertOk()->assertJsonPath('data.0.slug', 'tulsi-powder');

        // per_page is honoured.
        $this->client()->getJson(self::API.'/products?per_page=2')
            ->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('meta.last_page', 2);

        // Detail: ok, inactive and unknown are 404 JSON.
        $this->client()->getJson(self::API.'/products/moringa-powder')
            ->assertOk()
            ->assertJsonPath('data.name', 'Moringa Powder')
            ->assertJsonPath('data.category.slug', 'powders')
            ->assertJsonCount(2, 'data.variants');
        $this->client()->getJson(self::API.'/products/hidden-powder')
            ->assertNotFound()->assertJsonStructure(['message']);
        $this->client()->getJson(self::API.'/products/does-not-exist')->assertNotFound();

        // Categories with active product counts.
        $this->client()->getJson(self::API.'/categories')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.slug', 'powders')
            ->assertJsonPath('data.0.products_count', 2);
    }

    // ------------------------------------------------------------------ cart

    public function test_guest_cart_add_update_remove_and_stock_limits(): void
    {
        $v = $this->seedCatalogue();

        $cart = $this->client()->getJson(self::API.'/cart')
            ->assertOk()
            ->assertJsonPath('data.count', 0)
            ->assertJsonPath('data.shipping_paise', 0);
        $token = $cart->json('data.token');
        $this->assertNotEmpty($token);

        // Add twice: quantities add up. 2 x 11900 = 23800; shipping 6000; tax 1190.
        $this->client(null, $token)->postJson(self::API.'/cart/items', ['variant_id' => $v['moringa50']->id, 'qty' => 1])
            ->assertOk();
        $res = $this->client(null, $token)->postJson(self::API.'/cart/items', ['variant_id' => $v['moringa50']->id, 'qty' => 1])
            ->assertOk()
            ->assertJsonPath('data.token', $token)
            ->assertJsonPath('data.count', 2)
            ->assertJsonPath('data.items.0.qty', 2)
            ->assertJsonPath('data.items.0.line_total_paise', 23800)
            ->assertJsonPath('data.subtotal_paise', 23800)
            ->assertJsonPath('data.shipping_paise', 6000)
            ->assertJsonPath('data.tax_paise', 1190)
            ->assertJsonPath('data.total_paise', 30990);
        $itemId = $res->json('data.items.0.id');

        // More than stock (10) is a 422 on qty.
        $this->client(null, $token)->postJson(self::API.'/cart/items', ['variant_id' => $v['moringa50']->id, 'qty' => 9])
            ->assertStatus(422)->assertJsonValidationErrors(['qty']);
        // Out of stock variant.
        $this->client(null, $token)->postJson(self::API.'/cart/items', ['variant_id' => $v['tulsi']->id, 'qty' => 1])
            ->assertStatus(422);
        // Inactive product.
        $this->client(null, $token)->postJson(self::API.'/cart/items', ['variant_id' => $v['hidden']->id, 'qty' => 1])
            ->assertStatus(422);
        // Validation.
        $this->client(null, $token)->postJson(self::API.'/cart/items', ['variant_id' => $v['chips']->id, 'qty' => 0])
            ->assertStatus(422)->assertJsonValidationErrors(['qty']);

        // Max 20 per item even when stock is higher (stock 100).
        $this->client(null, $token)->postJson(self::API.'/cart/items', ['variant_id' => $v['moringa100']->id, 'qty' => 20])
            ->assertOk();
        $this->client(null, $token)->postJson(self::API.'/cart/items', ['variant_id' => $v['moringa100']->id, 'qty' => 1])
            ->assertStatus(422)->assertJsonValidationErrors(['qty']);

        // Update quantity, then qty 0 removes the line.
        $this->client(null, $token)->patchJson(self::API.'/cart/items/'.$itemId, ['qty' => 5])
            ->assertOk()->assertJsonPath('data.items.0.qty', 5);
        $this->client(null, $token)->patchJson(self::API.'/cart/items/'.$itemId, ['qty' => 0])
            ->assertOk()->assertJsonCount(1, 'data.items');
        $this->client(null, $token)->patchJson(self::API.'/cart/items/999999', ['qty' => 1])->assertNotFound();

        // Clear.
        $this->client(null, $token)->deleteJson(self::API.'/cart')
            ->assertOk()->assertJsonPath('data.count', 0)->assertJsonPath('data.total_paise', 0);
    }

    public function test_cart_merge_adds_quantities_capped_by_stock_and_discards_guest_cart(): void
    {
        $v = $this->seedCatalogue();
        $user = $this->makeUser();
        $userToken = $this->tokenFor($user);

        // Merge requires auth.
        $this->client()->postJson(self::API.'/cart/merge', ['guest_token' => 'x'])->assertUnauthorized();

        // The user already has 2 of the 50g pack.
        $this->client($userToken)->postJson(self::API.'/cart/items', ['variant_id' => $v['moringa50']->id, 'qty' => 2])
            ->assertOk();

        // Guest cart: 3 of the same pack + 1 jackfruit chips.
        $guest = $this->client()->getJson(self::API.'/cart')->json('data.token');
        $this->client(null, $guest)->postJson(self::API.'/cart/items', ['variant_id' => $v['moringa50']->id, 'qty' => 3])->assertOk();
        $this->client(null, $guest)->postJson(self::API.'/cart/items', ['variant_id' => $v['chips']->id, 'qty' => 1])->assertOk();

        $merged = $this->client($userToken)->postJson(self::API.'/cart/merge', ['guest_token' => $guest])
            ->assertOk()
            ->assertJsonPath('data.count', 6)
            ->assertJsonCount(2, 'data.items');
        $byVariant = collect($merged->json('data.items'))->keyBy('variant_id');
        $this->assertSame(5, $byVariant[$v['moringa50']->id]['qty']);
        $this->assertSame(1, $byVariant[$v['chips']->id]['qty']);
        $this->assertDatabaseMissing('carts', ['token' => $guest]);

        // Second guest cart pushes the 50g pack over its stock (10): capped at 10.
        $guest2 = $this->client()->getJson(self::API.'/cart')->json('data.token');
        $this->client(null, $guest2)->postJson(self::API.'/cart/items', ['variant_id' => $v['moringa50']->id, 'qty' => 9])->assertOk();
        $merged2 = $this->client($userToken)->postJson(self::API.'/cart/merge', ['guest_token' => $guest2])
            ->assertOk();
        $qty = collect($merged2->json('data.items'))->firstWhere('variant_id', $v['moringa50']->id)['qty'];
        $this->assertSame(10, $qty);

        // A logged-in user's cart ignores the guest header and is returned by GET /cart.
        $this->client($userToken, 'ignored-token')->getJson(self::API.'/cart')
            ->assertOk()->assertJsonPath('data.count', 11);
    }

    // ----------------------------------------------------------------- admin

    public function test_admin_routes_are_admin_only(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->makeUser('customer');

        // No token: 401 JSON (not a redirect to a login page).
        $this->client()->getJson(self::API.'/admin/dashboard')
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);

        // Customer token: 403 on every admin area.
        $customerToken = $this->tokenFor($customer);
        foreach (['/admin/dashboard', '/admin/products', '/admin/orders', '/admin/users', '/admin/settings'] as $path) {
            $this->client($customerToken)->getJson(self::API.$path)->assertForbidden();
        }
        $this->client($customerToken)->putJson(self::API.'/admin/users/'.$admin->id, ['role' => 'customer'])
            ->assertForbidden();

        // Admin token: allowed.
        $adminToken = $this->tokenFor($admin);
        $this->client($adminToken)->getJson(self::API.'/admin/dashboard')
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'stats' => ['total_orders', 'pending_orders', 'processing_orders', 'delivered_orders',
                    'total_sales_paise', 'low_stock_count', 'pending_shipments', 'customers'],
                'recent_orders',
                'low_stock',
            ]])
            ->assertJsonPath('data.stats.customers', 1);
        $this->client($adminToken)->getJson(self::API.'/admin/roles')
            ->assertOk()->assertExactJson(['data' => ['admin', 'customer']]);
        $this->client($adminToken)->getJson(self::API.'/auth/me')
            ->assertOk()->assertJsonPath('data.is_admin', true);
    }

    public function test_deactivated_user_token_stops_working(): void
    {
        $user = $this->makeUser();
        $token = $this->tokenFor($user);

        $this->client($token)->getJson(self::API.'/auth/me')->assertOk();

        $user->forceFill(['is_active' => false])->save();

        // 401 (not 403) so the SPA drops the token; the token row is revoked too.
        $this->client($token)->getJson(self::API.'/cart')->assertUnauthorized();
        $this->assertSame(0, $user->tokens()->count());
        $this->client($token)->getJson(self::API.'/auth/me')->assertUnauthorized();
    }

    public function test_hostile_query_params_do_not_cause_server_errors(): void
    {
        $this->seedCatalogue();
        $admin = $this->makeUser('admin');
        $adminToken = $this->tokenFor($admin);

        $this->client()->getJson(self::API.'/products?search[]=x&category[]=y&sort[]=z&per_page[]=1&min_price=abc')
            ->assertOk();
        $this->client()->getJson(self::API.'/products?search=%25_!')->assertOk()->assertJsonCount(0, 'data');
        foreach (['/admin/users?search[]=a&role[]=b', '/admin/orders?search[]=a&status[]=b', '/admin/customers?search[]=a',
            '/admin/products?search[]=a&category_id[]=1', '/admin/inventory?search[]=a'] as $path) {
            $this->client($adminToken)->getJson(self::API.$path)->assertOk();
        }
    }

    public function test_cart_quantity_is_capped_and_register_cannot_escalate(): void
    {
        $v = $this->seedCatalogue();
        $token = $this->client()->getJson(self::API.'/cart')->json('data.token');

        $this->client(null, $token)->postJson(self::API.'/cart/items', ['variant_id' => $v['chips']->id, 'qty' => PHP_INT_MAX])
            ->assertStatus(422)->assertJsonValidationErrors(['qty']);

        $res = $this->client()->postJson(self::API.'/auth/register', [
            'name' => 'Eve', 'email' => 'Eve@Example.com', 'password' => 'Secret@123', 'password_confirmation' => 'Secret@123',
            'role' => 'admin', 'is_active' => false, 'roles' => ['admin'],
        ])->assertCreated();
        $res->assertJsonPath('data.user.is_admin', false)->assertJsonPath('data.user.is_active', true)
            ->assertJsonPath('data.user.email', 'eve@example.com');

        // Mixed-case login works.
        $this->client()->postJson(self::API.'/auth/login', ['login' => 'EVE@example.com', 'password' => 'Secret@123'])->assertOk();
    }

    public function test_unknown_api_route_returns_json_404(): void
    {
        $this->client()->getJson(self::API.'/nope')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }
}
