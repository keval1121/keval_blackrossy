@extends('layouts.storefront')
@section('content')
<div class="container-store max-w-lg py-12">
    <h1 class="font-serif text-4xl">Track order</h1>
    <form method="post" class="mt-6 space-y-4 rounded-3xl bg-white p-6">
        @csrf
        <input name="order_number" value="{{ old('order_number') }}" placeholder="Order ID (e.g. ORD10001)" required>
        <input name="mobile" value="{{ old('mobile') }}" placeholder="Mobile number" required>
        <button class="btn btn-primary w-full">Track order</button>
    </form>
</div>
@endsection
