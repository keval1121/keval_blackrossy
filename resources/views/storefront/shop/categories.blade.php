@extends('layouts.storefront')
@section('content')
<section class="lookbook lookbook-page py-10 md:py-14">
    <div class="container-store">
        <div class="lookbook-head">
            <div>
                <p class="lookbook-kicker">Collections</p>
                <h1 class="lookbook-title">Shop every line</h1>
            </div>
        </div>

        <div class="lookbook-mosaic">
            @foreach($subcategories as $index => $category)
                <a href="{{ $category->url() }}" class="lookbook-tile @if($index % 5 === 0) lookbook-tile-wide @endif" style="--tile-i: {{ $index }}">
                    <img src="{{ $category->imageUrl() }}" alt="{{ $category->name }}" loading="lazy">
                    <span class="lookbook-tile-shade"></span>
                    <span class="lookbook-tile-label">
                        <span class="lookbook-tile-name">{{ $category->name }}</span>
                        <span class="lookbook-tile-cta">Shop</span>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endsection
