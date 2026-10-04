<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CartItemRequest;
use App\Http\Requests\Customer\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartService $carts)
    {
    }

    public function show(Request $request): CartResource
    {
        return new CartResource($this->carts->load($this->carts->resolve($request)));
    }

    public function addItem(CartItemRequest $request): CartResource
    {
        $cart = $this->carts->resolve($request);

        return new CartResource(
            $this->carts->add($cart, (int) $request->validated('variant_id'), (int) $request->validated('qty'))
        );
    }

    public function updateItem(UpdateCartItemRequest $request, int $id): CartResource
    {
        $cart = $this->carts->resolve($request);

        return new CartResource($this->carts->update($cart, $id, (int) $request->validated('qty')));
    }

    public function destroyItem(Request $request, int $id): CartResource
    {
        $cart = $this->carts->resolve($request);

        return new CartResource($this->carts->remove($cart, $id));
    }

    public function clear(Request $request): CartResource
    {
        $cart = $this->carts->resolve($request);

        return new CartResource($this->carts->clear($cart));
    }

    public function merge(Request $request): CartResource
    {
        $data = $request->validate([
            'guest_token' => ['required', 'string', 'max:64'],
        ]);

        // Route is behind auth:sanctum.
        return new CartResource($this->carts->merge($request->user(), $data['guest_token']));
    }
}
