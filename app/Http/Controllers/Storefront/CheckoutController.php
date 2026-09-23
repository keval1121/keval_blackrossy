<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\CheckoutRequest;
use App\Services\CartService;
use App\Services\NotificationService;
use App\Services\OrderService;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function index(CartService $cart)
    {
        if (! setting('cod_enabled', true)) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Checkout is temporarily unavailable.']);
        }

        $summary = $cart->summary();
        if ($summary['count'] < 1) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Your cart is empty.']);
        }

        return view('storefront.checkout.index', $summary);
    }

    public function otp(Request $request, OtpService $otp)
    {
        $data = $request->validate(['mobile' => ['required', 'regex:/^[6-9]\d{9}$/']]);

        try {
            $otp->assertNotBlocked($data['mobile']);
            $code = $otp->send($data['mobile']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $payload = ['message' => 'OTP sent to your mobile number.'];
        if (app()->environment('local')) {
            $payload['debug_otp'] = $code;
        }

        return response()->json($payload);
    }

    public function place(CheckoutRequest $request, CartService $cart, OrderService $orders, OtpService $otp, NotificationService $notifications)
    {
        $current = $cart->current(false);
        if (! $current || $current->items()->doesntExist()) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Your cart is empty.']);
        }

        $data = $request->validated();
        if (setting('otp_enabled') && ! $otp->verify($data['mobile'], $data['otp'] ?? '')) {
            return back()->withErrors(['otp' => 'The OTP is invalid or expired.'])->withInput();
        }

        $data['_cart_total'] = $cart->summary($current)['total'];

        try {
            $order = $orders->place($data, $current);
            $notifications->orderPlaced($order);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['checkout' => $e->getMessage()])->withInput();
        } catch (\Throwable $e) {
            Log::error('Order creation failed', ['error' => $e->getMessage()]);

            return back()->withErrors(['checkout' => 'We could not place your order. Please try again.'])->withInput();
        }

        return redirect()->route('order.success', $order);
    }
}
