@extends('layouts.admin')
@section('title', 'Dashboard')
@section('content')
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach(['today_orders'=>'Today’s orders','today_revenue'=>'Today’s revenue','pending'=>'Pending','confirmed'=>'Confirmed','delivered'=>'Delivered','cancelled'=>'Cancelled','products'=>'Products','low_stock'=>'Low stock'] as $key => $label)
        <article class="rounded-2xl bg-white p-5">
            <p class="text-xs uppercase tracking-widest text-stone-400">{{ $label }}</p>
            <p class="mt-2 text-2xl font-semibold">{{ $key === 'today_revenue' ? money($cards[$key]) : $cards[$key] }}</p>
        </article>
    @endforeach
</div>
<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <article class="rounded-2xl bg-white p-5">
        <h2 class="font-semibold">Orders by day</h2>
        <div class="mt-4 flex h-40 items-end gap-2">
            @php($max = max(1, $orderSeries->max('orders')))
            @foreach($orderSeries as $point)
                <div class="flex-1 text-center">
                    <div class="mx-auto w-full rounded-t bg-amber-500" style="height: {{ ($point['orders'] / $max) * 120 }}px"></div>
                    <p class="mt-2 text-[10px] text-stone-400">{{ $point['label'] }}</p>
                </div>
            @endforeach
        </div>
    </article>
    <article class="rounded-2xl bg-white p-5">
        <h2 class="font-semibold">Top products</h2>
        <ul class="mt-3 space-y-2 text-sm">
            @foreach($topProducts as $row)
                <li class="flex justify-between"><span>{{ $row->product_name_snapshot }}</span><span>{{ $row->qty }} sold</span></li>
            @endforeach
        </ul>
        <h2 class="mt-6 font-semibold">Top categories</h2>
        <ul class="mt-3 space-y-2 text-sm">
            @foreach($topCategories as $row)
                <li class="flex justify-between"><span>{{ $row->name }}</span><span>{{ $row->qty }}</span></li>
            @endforeach
        </ul>
    </article>
</div>
<div class="mt-6 rounded-2xl bg-white p-5">
    <h2 class="font-semibold">Recent orders</h2>
    <table class="mt-3 w-full text-left text-sm">
        <thead><tr class="text-stone-400"><th class="py-2">Order</th><th>Customer</th><th>Total</th><th>Status</th></tr></thead>
        <tbody>
        @foreach($recent as $order)
            <tr class="border-t border-stone-100">
                <td class="py-2"><a class="text-amber-700" href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a></td>
                <td>{{ $order->customer_name }}</td>
                <td>{{ money($order->total) }}</td>
                <td>{{ $order->status->label() }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
