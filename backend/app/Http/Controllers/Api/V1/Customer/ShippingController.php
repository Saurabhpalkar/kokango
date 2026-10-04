<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shipping\PincodeRequest;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\PricingService;
use App\Services\Shipping\ShippingService;
use Illuminate\Http\JsonResponse;

class ShippingController extends Controller
{
    public function __construct(
        private CartService $carts,
        private CheckoutService $checkout,
        private PricingService $pricing,
        private ShippingService $shipping,
    ) {
    }

    public function serviceability(PincodeRequest $request): JsonResponse
    {
        return $this->respond($request);
    }

    /** Alias of serviceability. */
    public function rates(PincodeRequest $request): JsonResponse
    {
        return $this->respond($request);
    }

    private function respond(PincodeRequest $request): JsonResponse
    {
        $pincode = (string) $request->validated('pincode');

        // Do not create a cart just to look up a pincode.
        $cart = $this->carts->resolve($request, false);
        $subtotal = $cart ? $this->checkout->subtotal($cart) : 0;
        $weight = $cart ? $this->checkout->weightGrams($cart) : 500;

        $result = $this->shipping->serviceability($pincode, $weight);

        return response()->json(['data' => [
            'pincode' => $pincode,
            'serviceable' => $result['serviceable'],
            'city' => $result['city'],
            'state' => $result['state'],
            'methods' => $result['serviceable'] ? $this->pricing->methods($subtotal) : [],
        ]]);
    }
}
