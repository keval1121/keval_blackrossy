@extends('layouts.storefront')
@php
    $seoTitle = ($q ? 'Search: '.$q : 'Search').' | '.store_name();
@endphp
@push('head')
    <meta name="robots" content="noindex, follow">
@endpush
@section('content')
<div class="container-store py-8">
    <h1 class="font-serif text-4xl">{{ $q ? 'Results for “'.$q.'”' : 'Search' }}</h1>
    <form class="mt-5" method="get"><input name="q" value="{{ $q }}" placeholder="Search products"></form>
    <div class="mt-8">
        @include('storefront.shop.partials.grid', ['products' => $products])
    </div>
</div>
@endsection
