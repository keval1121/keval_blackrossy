@extends('layouts.storefront')
@section('content')
<div class="container-store max-w-2xl py-12">
    <h1 class="font-serif text-4xl">{{ $order->order_number }}</h1>
    <p class="mt-2 text-muted">Status: {{ $order->status->label() }} · Total {{ money($order->total) }}</p>
    <ol class="mt-8 space-y-4">
        @foreach(\App\Enums\OrderStatus::flow() as $step)
            @php($done = $order->status->step() >= $step->step() && $order->status->step() > 0)
            <li class="flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-full {{ $done ? 'bg-emerald-600 text-white' : 'bg-white text-muted' }}">{{ $done ? '✓' : '○' }}</span>
                <span class="{{ $order->status === $step ? 'font-semibold' : '' }}">{{ $step->label() }}</span>
            </li>
        @endforeach
    </ol>
    @if(in_array($order->status, [\App\Enums\OrderStatus::Cancelled, \App\Enums\OrderStatus::Returned], true))
        <p class="mt-6 rounded-2xl bg-rose-50 p-4 text-rose-800">This order is {{ $order->status->label() }}.</p>
    @endif
    <div class="mt-8 rounded-3xl bg-white p-5 text-sm">
        @foreach($order->items as $item)
            <p class="py-1">{{ $item->product_name_snapshot }} × {{ $item->quantity }}</p>
        @endforeach
    </div>
</div>
@endsection
