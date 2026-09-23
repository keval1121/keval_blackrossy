<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

class CartService
{
    public function current(bool $create = true): ?Cart
    {
        $token = request()->cookie(config('shop.cart_cookie')) ?: session('cart_token');

        if ($token) {
            $cart = Cart::query()->where('token', $token)->first();
            if ($cart) {
                return $cart;
            }
        }

        if (! $create) {
            return null;
        }

        $cart = Cart::query()->create([
            'token' => (string) Str::uuid(),
            'ip_address' => request()->ip(),
        ]);

        session(['cart_token' => $cart->token]);
        Cookie::queue($this->cookie($cart->token));

        return $cart;
    }

    public function add(int $productId, int $quantity = 1, ?int $variantId = null): CartItem
    {
        $product = Product::query()->active()->findOrFail($productId);
        $variant = $this->resolveVariant($product, $variantId);
        $quantity = $this->clampQuantity($product, $quantity, $variant);
        $stock = $variant?->stock ?? $product->stock_quantity;

        if ($stock < 1) {
            throw new \RuntimeException('This product is currently out of stock.');
        }

        $cart = $this->current();
        $item = $cart->items()
            ->where('product_id', $product->id)
            ->where('product_variant_id', $variant?->id)
            ->first();

        $newQty = ($item?->quantity ?? 0) + $quantity;
        if ($newQty > $stock) {
            throw new \RuntimeException('Only '.$stock.' item(s) are available.');
        }

        if ($item) {
            $item->update(['quantity' => $newQty]);

            return $item->fresh(['product.primaryImage', 'variant.values.attributeValue']);
        }

        return $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'quantity' => $quantity,
        ])->load(['product.primaryImage', 'variant.values.attributeValue']);
    }

    public function updateQty(int $itemId, int $quantity): void
    {
        $item = $this->ownedItem($itemId);
        $product = $item->product;
        $stock = $item->availableStock();

        if ($quantity < 1) {
            $item->delete();

            return;
        }

        $quantity = $this->clampQuantity($product, $quantity, $item->variant);
        if ($quantity > $stock) {
            throw new \RuntimeException('Only '.$stock.' item(s) are available.');
        }

        $item->update(['quantity' => $quantity]);
    }

    public function remove(int $itemId): void
    {
        $this->ownedItem($itemId)->delete();
    }

    public function clear(?Cart $cart = null): void
    {
        $cart ??= $this->current(false);
        $cart?->items()->delete();
        $cart?->update(['coupon_id' => null]);
    }

    public function replaceWithBuyNow(int $productId, int $quantity, ?int $variantId): Cart
    {
        $cart = $this->current();
        $cart->items()->delete();
        $this->add($productId, $quantity, $variantId);

        return $cart->fresh(['items.product.primaryImage', 'items.variant.values.attributeValue', 'coupon']);
    }

    public function applyCoupon(string $code): Coupon
    {
        $cart = $this->current(false);
        if (! $cart || $cart->items()->doesntExist()) {
            throw new \RuntimeException('Your cart is empty.');
        }

        $coupon = Coupon::query()->where('code', strtoupper(trim($code)))->first();
        if (! $coupon) {
            throw new \RuntimeException('This coupon code is not valid.');
        }

        $summary = $this->summary($cart, false);
        if (! $coupon->isValidFor($summary['subtotal'])) {
            throw new \RuntimeException('This coupon cannot be applied to your cart.');
        }

        $cart->update(['coupon_id' => $coupon->id]);

        return $coupon;
    }

    public function removeCoupon(): void
    {
        $this->current(false)?->update(['coupon_id' => null]);
    }

    public function summary(?Cart $cart = null, bool $withCoupon = true): array
    {
        $cart ??= $this->current(false);
        $items = $cart
            ? $cart->items()->with(['product.primaryImage', 'variant.values.attributeValue'])->get()
            : collect();

        $lines = $items->map(function (CartItem $item) {
            return [
                'item' => $item,
                'name' => $item->product->name,
                'variant' => $item->variant?->label(),
                'qty' => $item->quantity,
                'price' => $item->unitPrice(),
                'total' => $item->lineTotal(),
                'image' => $item->product->thumbUrl(),
                'url' => $item->product->url(),
            ];
        });

        $subtotal = round($lines->sum('total'), 2);
        $discount = 0.0;
        $coupon = $withCoupon ? $cart?->coupon : null;
        if ($coupon) {
            $discount = $coupon->discountFor($subtotal);
            if ($discount <= 0) {
                $cart->update(['coupon_id' => null]);
                $coupon = null;
            }
        }

        $shipping = app(ShippingService::class)->charge($subtotal - $discount);

        return [
            'cart' => $cart,
            'items' => $lines,
            'count' => $items->sum('quantity'),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'delivery' => $shipping,
            'total' => max(0, round($subtotal - $discount + $shipping, 2)),
            'coupon' => $coupon,
            'free_shipping_from' => (float) setting('free_shipping_amount', 999),
        ];
    }

    public function count(): int
    {
        $cart = $this->current(false);

        return $cart ? (int) $cart->items()->sum('quantity') : 0;
    }

    public function validateStock(Cart $cart): Collection
    {
        $errors = collect();
        $cart->load(['items.product', 'items.variant']);

        foreach ($cart->items as $item) {
            if (! $item->product || $item->product->status->value !== 'active') {
                $errors->push('A product in your cart is no longer available.');
                continue;
            }
            $stock = $item->availableStock();
            if ($item->quantity > $stock) {
                $errors->push($item->product->name.' only has '.$stock.' left in stock.');
            }
        }

        return $errors;
    }

    private function ownedItem(int $itemId): CartItem
    {
        $cart = $this->current(false);
        abort_unless($cart, 404);

        return $cart->items()->where('id', $itemId)->firstOrFail();
    }

    private function resolveVariant(Product $product, ?int $variantId): ?ProductVariant
    {
        $hasVariants = $product->activeVariants()->exists();
        if ($hasVariants && ! $variantId) {
            throw new \RuntimeException('Please select a product option before adding to cart.');
        }

        if (! $variantId) {
            return null;
        }

        $variant = $product->activeVariants()->where('id', $variantId)->first();
        if (! $variant) {
            throw new \RuntimeException('The selected option is not available.');
        }

        return $variant;
    }

    private function clampQuantity(Product $product, int $quantity, ?ProductVariant $variant): int
    {
        $quantity = max($product->min_order_qty ?: 1, $quantity);
        $max = $product->max_order_qty ?: ($variant?->stock ?? $product->stock_quantity);

        return min($quantity, max(1, $max));
    }

    private function cookie(string $token): SymfonyCookie
    {
        return Cookie::make(
            config('shop.cart_cookie'),
            $token,
            config('shop.cart_cookie_days') * 24 * 60,
            '/',
            null,
            config('session.secure'),
            true,
            false,
            'lax'
        );
    }
}
