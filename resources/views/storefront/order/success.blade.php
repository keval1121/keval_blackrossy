@extends('layouts.storefront')
@php
    $seoTitle = 'Order Placed | '.store_name();
    $hideAds = true;
@endphp
@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush
@section('content')
<div class="container-store py-16 text-center">
    <p class="text-5xl">🎉</p>
    <h1 class="mt-4 font-serif text-4xl">Order placed successfully</h1>
    <p class="mt-3 text-muted">Thank you {{ $order->customer_name }}. Your Cash on Delivery order is confirmed.</p>
    <div class="mx-auto mt-8 max-w-md rounded-3xl bg-white p-6 text-left">
        <p>Order number: <strong>{{ $order->order_number }}</strong></p>
        <p class="mt-2">Payment: Cash on Delivery</p>
        <p class="mt-2">Total: <strong>{{ money($order->total) }}</strong></p>
    </div>
    <a href="{{ route('track') }}" class="btn btn-gold mt-8">Track order</a>
</div>
@endsection
