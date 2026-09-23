<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(CartService $cart)
    {
        return view('storefront.cart.index', $cart->summary());
    }

    public function add(Request $request, CartService $cart)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        try {
            if ($request->boolean('buy_now')) {
                $cart->replaceWithBuyNow($data['product_id'], $data['quantity'] ?? 1, $data['variant_id'] ?? null);

                return $request->expectsJson()
                    ? response()->json(['redirect' => route('checkout.index')])
                    : redirect()->route('checkout.index');
            }

            $item = $cart->add($data['product_id'], $data['quantity'] ?? 1, $data['variant_id'] ?? null);
        } catch (\RuntimeException $e) {
            return $this->fail($request, $e->getMessage());
        }

        $summary = $cart->summary();

        return $request->expectsJson()
            ? response()->json([
                'message' => $item->product->name.' added to cart',
                'count' => $summary['count'],
                'total' => money($summary['total']),
            ])
            : back()->with('status', $item->product->name.' added to cart');
    }

    public function update(Request $request, CartService $cart)
    {
        $data = $request->validate([
            'item_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:0', 'max:50'],
        ]);

        try {
            $cart->updateQty($data['item_id'], $data['quantity']);
        } catch (\RuntimeException $e) {
            return $this->fail($request, $e->getMessage());
        }

        return $this->ok($request, $cart);
    }

    public function remove(Request $request, CartService $cart)
    {
        $cart->remove($request->integer('item_id'));

        return $this->ok($request, $cart, 'Item removed');
    }

    public function coupon(Request $request, CartService $cart)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:40']]);

        try {
            $coupon = $cart->applyCoupon($data['code']);
        } catch (\RuntimeException $e) {
            return $this->fail($request, $e->getMessage());
        }

        return $this->ok($request, $cart, 'Coupon '.$coupon->code.' applied');
    }

    public function removeCoupon(Request $request, CartService $cart)
    {
        $cart->removeCoupon();

        return $this->ok($request, $cart, 'Coupon removed');
    }

    public function count(CartService $cart)
    {
        return response()->json(['count' => $cart->count()]);
    }

    private function ok(Request $request, CartService $cart, string $message = 'Cart updated')
    {
        $summary = $cart->summary();
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'count' => $summary['count'],
                'html' => view('storefront.cart.partials.contents', $summary)->render(),
            ]);
        }

        return back()->with('status', $message);
    }

    private function fail(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->withErrors(['cart' => $message]);
    }
}
