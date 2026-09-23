@extends('layouts.admin')
@section('title', 'Reports')
@section('content')
<form class="mb-4 flex gap-2" method="get">
    <input class="admin-input" type="date" name="from" value="{{ $from }}">
    <input class="admin-input" type="date" name="to" value="{{ $to }}">
    <button class="rounded-xl bg-stone-900 px-4 text-white">Apply</button>
    <a class="rounded-xl border px-4 py-2 text-sm" href="{{ route('admin.orders.export') }}">Export orders</a>
</form>
<div class="grid gap-4 md:grid-cols-4">
    <article class="rounded-2xl bg-white p-5"><p class="text-xs text-stone-400">Orders</p><p class="text-2xl font-semibold">{{ $orders->count() }}</p></article>
    <article class="rounded-2xl bg-white p-5"><p class="text-xs text-stone-400">Revenue</p><p class="text-2xl font-semibold">{{ money($orders->reject(fn ($order) => $order->status === \App\Enums\OrderStatus::Cancelled)->sum('total')) }}</p></article>
    <article class="rounded-2xl bg-white p-5"><p class="text-xs text-stone-400">Delivered</p><p class="text-2xl font-semibold">{{ $orders->where('status', \App\Enums\OrderStatus::Delivered)->count() }}</p></article>
    <article class="rounded-2xl bg-white p-5"><p class="text-xs text-stone-400">Cancelled</p><p class="text-2xl font-semibold">{{ $orders->where('status', \App\Enums\OrderStatus::Cancelled)->count() }}</p></article>
</div>
@endsection
