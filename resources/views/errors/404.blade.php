@extends('layouts.storefront')
@php($hideAds = true)
@section('content')
<div class="container-store py-24 text-center">
    <p class="font-serif text-7xl">404</p>
    <h1 class="mt-4 text-2xl">This page is not available</h1>
    <a href="{{ route('home') }}" class="btn btn-primary mt-6">Go home</a>
</div>
@endsection
