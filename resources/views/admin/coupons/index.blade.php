@extends('layouts.admin')
@section('title', 'Coupons')
@section('content')
<form method="post" action="{{ route('admin.coupons.save') }}" class="mb-6 grid gap-3 rounded-2xl bg-white p-5 md:grid-cols-3">
    @csrf
    <input class="admin-input" name="code" placeholder="CODE" required>
    <select class="admin-input" name="type"><option value="percentage">Percentage</option><option value="fixed">Fixed</option></select>
    <input class="admin-input" name="value" placeholder="Value" required>
    <input class="admin-input" name="min_order" placeholder="Min order">
    <input class="admin-input" name="max_discount" placeholder="Max discount">
    <input class="admin-input" name="usage_limit" placeholder="Usage limit">
    <label class="text-sm"><input type="checkbox" name="is_active" checked> Active</label>
    <button class="rounded-xl bg-stone-900 text-white">Save coupon</button>
</form>
<table class="w-full rounded-2xl bg-white text-left text-sm">
    @foreach($coupons as $coupon)
        <tr class="border-t border-stone-100"><td class="p-3">{{ $coupon->code }}</td><td>{{ $coupon->type->value }} {{ $coupon->value }}</td><td>{{ $coupon->used_count }} used</td></tr>
    @endforeach
</table>
@endsection
