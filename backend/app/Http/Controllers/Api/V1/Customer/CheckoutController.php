<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Concerns\ResolvesViewer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\CheckoutRequest;
use App\Http\Requests\Checkout\QuoteRequest;
use App\Http\Resources\OrderResource;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\MarketplaceService;
use App\Services\OrderNotifier;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    use ResolvesViewer;

    public function __construct(
        private CartService $carts,
        private CheckoutService $checkout,
        private OrderService $orders,
        private OrderNotifier $notifier,
        private MarketplaceService $marketplace,
    ) {
    }

    public function quote(QuoteRequest $request): JsonResponse
    {
        $cart = $this->carts->resolve($request);

        return response()->json([
            'data' => $this->checkout->quote($cart, $request->validated('shipping_method'), $request->validated('coupon_code'), $this->viewer()?->email),
        ]);
    }

    public function store(CheckoutRequest $request): JsonResponse
    {
        $cart = $this->carts->resolve($request);

        $result = $this->checkout->placeOrder($request->validated(), $cart, $this->viewer());

        // Cash on Delivery is complete at once: empty the cart and tell the customer and the shop owner.
        if ($result['payment']['gateway'] === 'cod') {
            $this->orders->clearCart($result['order'], (string) $request->header('X-Cart-Token', ''));
            $this->marketplace->syncOrder($result['order']);
            $this->notifier->placed($result['order']);
        }

        return response()->json([
            'data' => [
                'order' => (new OrderResource($result['order']))->withToken()->resolve($request),
                'payment' => $result['payment'],
            ],
        ], 201);
    }
}
