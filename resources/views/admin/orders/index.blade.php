@extends('layouts.admin')
@section('title', 'Orders')
@section('content')
<form class="mb-4 flex gap-2" method="get">
    <input name="q" value="{{ request('q') }}" class="admin-input w-56" placeholder="Order, mobile, name">
    <select name="status" class="admin-input w-44">
        <option value="">All statuses</option>
        @foreach(\App\Enums\OrderStatus::cases() as $status)
            <option value="{{ $status->value }}" @selected(request('status')===$status->value)>{{ $status->label() }}</option>
        @endforeach
    </select>
    <button class="rounded-xl bg-stone-900 px-4 text-white">Filter</button>
    <a href="{{ route('admin.orders.export', request()->query()) }}" class="rounded-xl border px-4 py-2 text-sm">Export</a>
</form>
<table class="w-full rounded-2xl bg-white text-left text-sm">
    <thead><tr class="text-stone-400"><th class="p-3">Order</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
    <tbody>
    @foreach($orders as $order)
        <tr class="border-t border-stone-100">
            <td class="p-3"><a class="text-amber-700" href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a></td>
            <td>{{ $order->customer_name }}<div class="text-xs text-stone-400">{{ $order->mobile }}</div></td>
            <td>{{ money($order->total) }}</td>
            <td>{{ $order->status->label() }}</td>
            <td>{{ $order->created_at->format('d M Y H:i') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
