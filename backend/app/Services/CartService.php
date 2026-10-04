<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartService
{
    public const MAX_QTY = 20;

    /**
     * Current user (works with or without auth middleware on the route).
     */
    public function user(Request $request): ?User
    {
        return $request->user() ?? Auth::guard('sanctum')->user();
    }

    /**
     * Finds the cart of the logged-in user, or the guest cart named by the X-Cart-Token header.
     * With $create = false a missing guest cart returns null instead of creating one.
     */
    public function resolve(Request $request, bool $create = true): ?Cart
    {
        $user = $this->user($request);

        if ($user) {
            $cart = Cart::where('user_id', $user->id)->first();
            if (! $cart && $create) {
                $cart = $this->createUserCart($user);
            }

            return $cart;
        }

        $token = (string) $request->header('X-Cart-Token', '');
        $cart = $token !== ''
            ? Cart::where('token', $token)->whereNull('user_id')->first()
            : null;

        if (! $cart && $create) {
            $cart = Cart::create(['token' => Str::random(40)]);
        }

        return $cart;
    }

    /** Two parallel first requests (e.g. page load) may both create the cart: the loser reads the winner's. */
    private function createUserCart(User $user): Cart
    {
        try {
            return Cart::create(['user_id' => $user->id, 'token' => Str::random(40)]);
        } catch (UniqueConstraintViolationException) {
            return Cart::where('user_id', $user->id)->firstOrFail();
        }
    }

    /** Loads everything needed to render a cart. */
    public function load(Cart $cart): Cart
    {
        return $cart->load(['items' => fn ($q) => $q->orderBy('id'), 'items.variant.product']);
    }

    public function subtotal(Cart $cart): int
    {
        $cart->loadMissing('items.variant');

        return (int) $cart->items->sum(fn (CartItem $i) => ($i->variant?->price_paise ?? 0) * $i->qty);
    }

    public function add(Cart $cart, int $variantId, int $qty): Cart
    {
        $variant = ProductVariant::with('product')->find($variantId);

        if (! $variant || ! $variant->is_active || ! $variant->product || ! $variant->product->is_active) {
            throw ValidationException::withMessages(['variant_id' => ['This product is no longer available.']]);
        }

        // Two simultaneous "add" clicks can both try to create the row: the loser retries and increments.
        try {
            $this->addLocked($cart, $variant, $qty);
        } catch (UniqueConstraintViolationException) {
            $this->addLocked($cart, $variant, $qty);
        }

        $cart->touch();

        return $this->load($cart);
    }

    private function addLocked(Cart $cart, ProductVariant $variant, int $qty): void
    {
        DB::transaction(function () use ($cart, $variant, $qty) {
            $item = CartItem::where('cart_id', $cart->id)
                ->where('product_variant_id', $variant->id)
                ->lockForUpdate()
                ->first();

            $total = ($item?->qty ?? 0) + $qty;
            $this->assertQuantity($variant, $total);

            if ($item) {
                $item->update(['qty' => $total]);
            } else {
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_variant_id' => $variant->id,
                    'qty' => $total,
                ]);
            }
        });
    }

    public function update(Cart $cart, int $itemId, int $qty): Cart
    {
        $item = CartItem::where('cart_id', $cart->id)->with('variant')->findOrFail($itemId);

        if ($qty <= 0) {
            $item->delete();
        } else {
            $this->assertQuantity($item->variant, $qty);
            $item->update(['qty' => $qty]);
        }

        $cart->touch();

        return $this->load($cart);
    }

    public function remove(Cart $cart, int $itemId): Cart
    {
        CartItem::where('cart_id', $cart->id)->findOrFail($itemId)->delete();
        $cart->touch();

        return $this->load($cart);
    }

    public function clear(Cart $cart): Cart
    {
        CartItem::where('cart_id', $cart->id)->delete();
        $cart->touch();

        return $this->load($cart);
    }

    /**
     * Moves the items of a guest cart into the user's cart (quantities add up, capped by stock and
     * the per-item maximum) and deletes the guest cart.
     */
    public function merge(User $user, string $guestToken): Cart
    {
        $userCart = Cart::where('user_id', $user->id)->first() ?? $this->createUserCart($user);

        $guest = Cart::where('token', $guestToken)->whereNull('user_id')->first();
        if (! $guest) {
            return $this->load($userCart);
        }

        DB::transaction(function () use ($guest, $userCart) {
            foreach ($guest->items()->with('variant.product')->get() as $gItem) {
                $variant = $gItem->variant;
                if (! $variant || ! $variant->is_active || ! $variant->product?->is_active) {
                    continue;
                }

                $existing = CartItem::where('cart_id', $userCart->id)
                    ->where('product_variant_id', $variant->id)
                    ->first();

                $qty = min(($existing?->qty ?? 0) + $gItem->qty, (int) $variant->stock, self::MAX_QTY);
                if ($qty <= 0) {
                    continue;
                }

                if ($existing) {
                    $existing->update(['qty' => $qty]);
                } else {
                    CartItem::create([
                        'cart_id' => $userCart->id,
                        'product_variant_id' => $variant->id,
                        'qty' => $qty,
                    ]);
                }
            }

            $guest->delete();
        });

        $userCart->touch();

        return $this->load($userCart);
    }

    private function assertQuantity(ProductVariant $variant, int $qty): void
    {
        $stock = (int) $variant->stock;

        if ($stock <= 0) {
            throw ValidationException::withMessages(['qty' => ['Only 0 left in stock']]);
        }
        if ($qty > $stock) {
            throw ValidationException::withMessages(['qty' => ["Only {$stock} left in stock"]]);
        }
        if ($qty > self::MAX_QTY) {
            throw ValidationException::withMessages(['qty' => ['You can order at most '.self::MAX_QTY.' of one item']]);
        }
    }
}
