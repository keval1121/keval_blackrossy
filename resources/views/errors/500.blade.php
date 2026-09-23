@extends('layouts.storefront')
@section('content')
<div class="container-store py-24 text-center">
    <p class="font-serif text-7xl">500</p>
    <h1 class="mt-4 text-2xl">Something went wrong</h1>
    <p class="mt-2 text-muted">Please try again in a moment.</p>
    <a href="{{ route('home') }}" class="btn btn-primary mt-6">Go home</a>
</div>
@endsection
