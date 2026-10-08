@extends('layouts.storefront')
@php
    $seoTitle = 'Your Bag | '.store_name();
    $hideAds = true;
@endphp
@push('head')
    <meta name="robots" content="noindex, follow">
@endpush
@section('content')
<div class="container-store py-8" id="cart-contents">
    @include('storefront.cart.partials.contents')
</div>
@endsection
