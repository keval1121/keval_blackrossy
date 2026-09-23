@extends('layouts.admin')
@section('title', $order->order_number)
@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="rounded-2xl bg-white p-5 lg:col-span-2">
        <p class="text-sm text-stone-500">{{ $order->customer_name }} · {{ $order->mobile }}</p>
        <p class="mt-2 text-sm">{{ $order->address?->formatted() }}</p>
        <table class="mt-4 w-full text-sm">
            @foreach($order->items as $item)
                <tr class="border-t border-stone-100">
                    <td class="py-2">{{ $item->product_name_snapshot }} <span class="text-stone-400">{{ $item->variant_snapshot }}</span></td>
                    <td>× {{ $item->quantity }}</td>
                    <td>{{ money($item->subtotal) }}</td>
                </tr>
            @endforeach
        </table>
        <p class="mt-4 font-semibold">Total {{ money($order->total) }} · COD</p>
        @if($order->is_suspicious)<p class="mt-2 text-sm text-rose-700">Flagged: {{ $order->suspicious_reason }}</p>@endif
    </div>
    <div class="space-y-4">
        <form method="post" action="{{ route('admin.orders.status', $order) }}" class="rounded-2xl bg-white p-5">
            @csrf
            <select name="status" class="admin-input">
                @foreach(\App\Enums\OrderStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected($order->status===$status)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <input class="admin-input mt-2" name="note" placeholder="Note">
            <button class="mt-3 w-full rounded-xl bg-stone-900 py-2 text-white">Update status</button>
        </form>
        <a href="{{ route('admin.orders.print', $order) }}" class="block rounded-xl border bg-white p-3 text-center text-sm" target="_blank">Print</a>
        <div class="rounded-2xl bg-white p-5 text-sm">
            <p class="font-semibold">Timeline</p>
            @foreach($order->statusLogs as $log)
                <p class="mt-2">{{ $log->to_status->label() }} · {{ $log->created_at->format('d M H:i') }}</p>
            @endforeach
        </div>
    </div>
</div>
@endsection
