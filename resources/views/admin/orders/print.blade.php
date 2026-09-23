<!DOCTYPE html>
<html><head><title>{{ $order->order_number }}</title>
<style>body{font-family:sans-serif;padding:32px} table{width:100%;border-collapse:collapse} td,th{border-bottom:1px solid #ddd;padding:8px;text-align:left}</style>
</head><body>
<h1>{{ store_name() }}</h1>
<p>{{ $order->order_number }} · {{ $order->created_at }}</p>
<p>{{ $order->customer_name }} · {{ $order->mobile }}</p>
<p>{{ $order->address?->formatted() }}</p>
<table>
<tr><th>Item</th><th>Qty</th><th>Total</th></tr>
@foreach($order->items as $item)
<tr><td>{{ $item->product_name_snapshot }} {{ $item->variant_snapshot }}</td><td>{{ $item->quantity }}</td><td>{{ money($item->subtotal) }}</td></tr>
@endforeach
</table>
<p><strong>Grand total: {{ money($order->total) }} COD</strong></p>
<script>window.print()</script>
</body></html>
