<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OtpService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function success(Order $order)
    {
        return view('storefront.order.success', compact('order'));
    }

    public function trackForm()
    {
        return view('storefront.order.track');
    }

    public function track(Request $request, OtpService $otp)
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:30'],
            'mobile' => ['required', 'regex:/^[6-9]\d{9}$/'],
        ]);

        $order = Order::query()
            ->with(['statusLogs', 'items'])
            ->where('order_number', strtoupper(trim($data['order_number'])))
            ->where('mobile', $otp->normalize($data['mobile']))
            ->first();

        if (! $order) {
            return back()->withErrors(['order_number' => 'No order found for this Order ID and mobile number.'])->withInput();
        }

        return view('storefront.order.track-result', compact('order'));
    }
}
