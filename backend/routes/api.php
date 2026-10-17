<?php

use App\Http\Controllers\Api\V1\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Api\V1\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Api\V1\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\InventoryController as AdminInventoryController;
use App\Http\Controllers\Api\V1\Admin\MarketplaceController as AdminMarketplaceController;
use App\Http\Controllers\Api\V1\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Api\V1\Admin\ProductApprovalController as AdminProductApprovalController;
use App\Http\Controllers\Api\V1\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\V1\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Api\V1\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Api\V1\Admin\ShipmentController as AdminShipmentController;
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\Admin\VendorController as AdminVendorController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Customer\AddressController;
use App\Http\Controllers\Api\V1\Customer\CartController;
use App\Http\Controllers\Api\V1\Customer\CatalogueController;
use App\Http\Controllers\Api\V1\Customer\CheckoutController;
use App\Http\Controllers\Api\V1\Customer\OrderController;
use App\Http\Controllers\Api\V1\Customer\PaymentController;
use App\Http\Controllers\Api\V1\Customer\ReviewController;
use App\Http\Controllers\Api\V1\Customer\ShipmentController;
use App\Http\Controllers\Api\V1\Customer\ShippingController;
use App\Http\Controllers\Api\V1\Vendor\VendorAuthController;
use App\Http\Controllers\Api\V1\Vendor\VendorOrderController;
use App\Http\Controllers\Api\V1\Vendor\VendorPanelController;
use App\Http\Controllers\Api\V1\Vendor\VendorProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Kokango API v1  (the "/api/v1" prefix is added in bootstrap/app.php)
|--------------------------------------------------------------------------
| Routes marked "optional auth" work for guests and logged-in users: the
| controllers resolve the user with Auth::guard('sanctum')->user().
*/

// Gateway webhooks are server-to-server: no throttling, no auth (signature is verified in the controller).
Route::post('/payments/razorpay/webhook', [PaymentController::class, 'razorpayWebhook']);

Route::middleware(['throttle:api', 'active'])->group(function () {

    // ---- Auth ------------------------------------------------------------
    Route::prefix('auth')->group(function () {
        Route::middleware('throttle:auth')->group(function () {
            Route::post('/register', [AuthController::class, 'register']);
            Route::post('/login', [AuthController::class, 'login']);
            Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
            Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        });

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::put('/profile', [AuthController::class, 'updateProfile']);
            Route::put('/password', [AuthController::class, 'changePassword']);
        });
    });

    // ---- Public catalogue ------------------------------------------------
    Route::get('/categories', [CatalogueController::class, 'categories']);
    Route::get('/products', [CatalogueController::class, 'products']);
    Route::get('/products/{slug}', [CatalogueController::class, 'show']);
    Route::get('/products/{slug}/reviews', [ReviewController::class, 'index']);
    Route::post('/products/{slug}/reviews', [ReviewController::class, 'store'])->middleware(['auth:sanctum', 'throttle:auth']);
    Route::get('/settings/public', [CatalogueController::class, 'publicSettings']);

    // ---- Cart (optional auth) --------------------------------------------
    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/items', [CartController::class, 'addItem']);
    Route::patch('/cart/items/{id}', [CartController::class, 'updateItem'])->whereNumber('id');
    Route::delete('/cart/items/{id}', [CartController::class, 'destroyItem'])->whereNumber('id');
    Route::delete('/cart', [CartController::class, 'clear']);
    Route::post('/cart/merge', [CartController::class, 'merge'])->middleware('auth:sanctum');

    // ---- Shipping / checkout / payments (optional auth) -------------------
    Route::post('/shipping/serviceability', [ShippingController::class, 'serviceability']);
    Route::post('/shipping/rates', [ShippingController::class, 'rates']);

    Route::post('/checkout/quote', [CheckoutController::class, 'quote']);
    Route::post('/checkout', [CheckoutController::class, 'store']);

    Route::post('/payments/verify', [PaymentController::class, 'verify']);
    Route::post('/payments/failed', [PaymentController::class, 'failed']);

    // ---- Orders & tracking -----------------------------------------------
    Route::post('/orders/track', [OrderController::class, 'track'])->middleware('throttle:auth');
    Route::get('/shipments/{orderNo}/track', [ShipmentController::class, 'track']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/orders', [OrderController::class, 'index']);
        Route::post('/orders/{orderNo}/cancel', [OrderController::class, 'cancel']);

        Route::get('/addresses', [AddressController::class, 'index']);
        Route::post('/addresses', [AddressController::class, 'store']);
        Route::put('/addresses/{id}', [AddressController::class, 'update'])->whereNumber('id');
        Route::delete('/addresses/{id}', [AddressController::class, 'destroy'])->whereNumber('id');
        Route::post('/addresses/{id}/default', [AddressController::class, 'setDefault'])->whereNumber('id');
    });

    // Optional auth (owner, or guest with ?token=)
    Route::get('/orders/{orderNo}', [OrderController::class, 'show']);

    // ---- Admin -----------------------------------------------------------
    Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);

        Route::get('/products', [AdminProductController::class, 'index']);
        Route::post('/products', [AdminProductController::class, 'store']);
        Route::get('/products/{id}', [AdminProductController::class, 'show'])->whereNumber('id');
        Route::put('/products/{id}', [AdminProductController::class, 'update'])->whereNumber('id');
        Route::delete('/products/{id}', [AdminProductController::class, 'destroy'])->whereNumber('id');
        Route::post('/products/{id}/image', [AdminProductController::class, 'uploadImage'])->whereNumber('id');

        Route::get('/categories', [AdminCategoryController::class, 'index']);
        Route::post('/categories', [AdminCategoryController::class, 'store']);
        Route::put('/categories/{id}', [AdminCategoryController::class, 'update'])->whereNumber('id');
        Route::delete('/categories/{id}', [AdminCategoryController::class, 'destroy'])->whereNumber('id');

        Route::get('/inventory', [AdminInventoryController::class, 'index']);
        Route::post('/inventory/{variantId}/adjust', [AdminInventoryController::class, 'adjust'])->whereNumber('variantId');

        Route::get('/orders', [AdminOrderController::class, 'index']);
        Route::get('/orders/{orderNo}', [AdminOrderController::class, 'show']);
        Route::put('/orders/{orderNo}/status', [AdminOrderController::class, 'updateStatus']);
        Route::post('/orders/{orderNo}/shipment', [AdminShipmentController::class, 'store']);

        Route::get('/payments', [AdminPaymentController::class, 'index']);
        Route::post('/payments/{id}/refund', [AdminPaymentController::class, 'refund'])->whereNumber('id');

        Route::get('/shipments', [AdminShipmentController::class, 'index']);
        Route::put('/shipments/{id}/status', [AdminShipmentController::class, 'updateStatus'])->whereNumber('id');
        Route::post('/shipments/{id}/sync', [AdminShipmentController::class, 'sync'])->whereNumber('id');

        Route::get('/customers', [AdminCustomerController::class, 'index']);
        Route::put('/customers/{id}', [AdminCustomerController::class, 'update'])->whereNumber('id');

        Route::get('/settings', [AdminSettingController::class, 'show']);
        Route::put('/settings', [AdminSettingController::class, 'update']);

        Route::get('/users', [AdminUserController::class, 'index']);
        Route::post('/users', [AdminUserController::class, 'store']);
        Route::put('/users/{id}', [AdminUserController::class, 'update'])->whereNumber('id');
        Route::get('/roles', [AdminUserController::class, 'roles']);

        Route::get('/coupons', [AdminCouponController::class, 'index']);
        Route::post('/coupons', [AdminCouponController::class, 'store']);
        Route::put('/coupons/{id}', [AdminCouponController::class, 'update'])->whereNumber('id');
        Route::delete('/coupons/{id}', [AdminCouponController::class, 'destroy'])->whereNumber('id');

        Route::get('/reviews', [AdminReviewController::class, 'index']);
        Route::put('/reviews/{id}', [AdminReviewController::class, 'update'])->whereNumber('id');
        Route::delete('/reviews/{id}', [AdminReviewController::class, 'destroy'])->whereNumber('id');

        // Marketplace (sellers, listing reviews, commission and payouts)
        Route::get('/marketplace/overview', [AdminMarketplaceController::class, 'overview']);
        Route::get('/marketplace/settings', [AdminMarketplaceController::class, 'settings']);
        Route::put('/marketplace/settings', [AdminMarketplaceController::class, 'saveSettings']);

        Route::get('/vendors', [AdminVendorController::class, 'index']);
        Route::post('/vendors', [AdminVendorController::class, 'store']);
        Route::get('/vendors/{id}', [AdminVendorController::class, 'show'])->whereNumber('id');
        Route::put('/vendors/{id}', [AdminVendorController::class, 'update'])->whereNumber('id');
        Route::post('/vendors/{id}/{action}', [AdminVendorController::class, 'act'])
            ->whereNumber('id')->whereIn('action', ['approve', 'reject', 'suspend', 'reactivate']);

        Route::get('/product-approvals', [AdminProductApprovalController::class, 'index']);
        Route::post('/product-approvals/{id}/{action}', [AdminProductApprovalController::class, 'act'])
            ->whereNumber('id')->whereIn('action', ['approve', 'reject', 'unpublish']);

        Route::get('/vendor-payouts', [AdminMarketplaceController::class, 'payouts']);
        Route::post('/vendor-payouts', [AdminMarketplaceController::class, 'createPayout']);
        Route::post('/vendor-payouts/{id}/status', [AdminMarketplaceController::class, 'payoutStatus'])->whereNumber('id');
    });

    // ---- Vendor (seller) panel ---------------------------------------------
    Route::prefix('vendor')->group(function () {
        Route::middleware('throttle:auth')->group(function () {
            Route::post('/auth/register', [VendorAuthController::class, 'register']);
            Route::post('/auth/login', [VendorAuthController::class, 'login']);
        });

        Route::middleware(['auth:sanctum', 'vendor'])->group(function () {
            Route::get('/auth/me', [VendorAuthController::class, 'me']);
            Route::post('/auth/logout', [VendorAuthController::class, 'logout']);

            Route::get('/dashboard', [VendorPanelController::class, 'dashboard']);

            Route::get('/products', [VendorProductController::class, 'index']);
            Route::post('/products', [VendorProductController::class, 'store']);
            Route::get('/products/{id}', [VendorProductController::class, 'show'])->whereNumber('id');
            Route::put('/products/{id}', [VendorProductController::class, 'update'])->whereNumber('id');
            Route::delete('/products/{id}', [VendorProductController::class, 'destroy'])->whereNumber('id');

            Route::get('/orders', [VendorOrderController::class, 'index']);
            Route::get('/orders/{orderNo}', [VendorOrderController::class, 'show']);
            Route::put('/orders/{orderNo}/status', [VendorOrderController::class, 'updateStatus']);

            Route::get('/inventory', [VendorPanelController::class, 'inventory']);
            Route::post('/inventory/{variantId}/adjust', [VendorPanelController::class, 'adjust'])->whereNumber('variantId');

            Route::get('/payouts', [VendorPanelController::class, 'payouts']);

            Route::get('/store', [VendorPanelController::class, 'store']);
            Route::put('/store', [VendorPanelController::class, 'updateStore']);
        });
    });
});
