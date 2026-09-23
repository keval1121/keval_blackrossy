<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::query()
            ->with('address')
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->q, function ($q, $term) {
                $q->where('order_number', 'like', '%'.$term.'%')
                    ->orWhere('mobile', 'like', '%'.$term.'%')
                    ->orWhere('customer_name', 'like', '%'.$term.'%');
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['items', 'address', 'statusLogs.admin']);

        return view('admin.orders.show', compact('order'));
    }

    public function status(Request $request, Order $order, OrderService $orders)
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $orders->changeStatus($order, OrderStatus::from($data['status']), $data['note'] ?? null, $request->user('admin')?->id);

        return back()->with('status', 'Order status updated.');
    }

    public function print(Order $order)
    {
        $order->load(['items', 'address']);

        return view('admin.orders.print', compact('order'));
    }

    public function export(Request $request)
    {
        $orders = Order::query()->with('address')->when($request->status, fn ($q, $s) => $q->where('status', $s))->latest()->get();

        return response()->streamDownload(function () use ($orders) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['order_number', 'date', 'customer', 'mobile', 'city', 'status', 'payment', 'total']);
            foreach ($orders as $order) {
                fputcsv($out, [
                    $order->order_number,
                    $order->created_at->toDateTimeString(),
                    $order->customer_name,
                    $order->mobile,
                    $order->address?->city,
                    $order->status->value,
                    $order->payment_method->value,
                    $order->total,
                ]);
            }
            fclose($out);
        }, 'orders.csv', ['Content-Type' => 'text/csv']);
    }
}
