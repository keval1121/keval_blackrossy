@extends('layouts.storefront')
@php
    $hideAds = true;
    $seoTitle = 'Gone | '.store_name();
@endphp
@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush
@section('content')
<div class="container-store py-24 text-center">
    <p class="font-serif text-7xl">410</p>
    <h1 class="mt-4 text-2xl">This page has been removed</h1>
    <a href="{{ route('home') }}" class="btn btn-primary mt-6">Go home</a>
</div>
@endsection
