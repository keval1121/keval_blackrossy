@extends('layouts.storefront')
@php
    $seoTitle = ($q ? 'Search: '.$q : 'Search').' | '.store_name();
@endphp
@section('content')
<div class="container-store py-8">
    <h1 class="font-serif text-4xl">{{ $q ? 'Results for “'.$q.'”' : 'Search' }}</h1>
    <form class="mt-5" method="get"><input name="q" value="{{ $q }}" placeholder="Search products"></form>
    <x-ad position="search_middle" />
    <div class="mt-8">
        @include('storefront.shop.partials.grid', ['products' => $products])
    </div>
</div>
@endsection
