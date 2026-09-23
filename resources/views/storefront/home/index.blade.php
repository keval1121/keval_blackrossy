@extends('layouts.storefront')

@php
    $seoTitle = setting('seo_title', store_name().' | Fashion, Jewellery & Lifestyle');
    $seoDescription = setting('seo_description');
    $schema = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => store_name(),
        'url' => url('/'),
    ]);
@endphp

@section('content')
<section class="hero-slider" data-hero-slider aria-roledescription="carousel" aria-label="Store offers">
    <div class="hero-track" data-hero-track>
        @forelse($banners as $index => $banner)
            <article class="hero-slide relative" data-hero-slide @if($index === 0) aria-hidden="false" @else aria-hidden="true" @endif>
                <picture>
                    <source media="(max-width: 768px)" srcset="{{ $banner->mobileUrl() }}">
                    <img src="{{ $banner->desktopUrl() }}" alt="{{ $banner->title }}" class="hero-slide-image" @if($index === 0) fetchpriority="high" @else loading="lazy" @endif>
                </picture>
                <div class="hero-slide-shade"></div>
                <div class="container-store hero-slide-copy text-white">
                    <p class="font-serif text-4xl md:text-6xl">{{ $banner->title }}</p>
                    <p class="mt-2 max-w-xl text-sm md:text-base text-white/90">{{ $banner->subtitle }}</p>
                    @if($banner->button_url)
                        <a href="{{ str_starts_with($banner->button_url, 'http') ? $banner->button_url : url($banner->button_url) }}" class="btn btn-gold mt-5">{{ $banner->button_text ?: 'Shop now' }}</a>
                    @endif
                </div>
            </article>
        @empty
            <div class="hero-slide flex h-[380px] items-end bg-stone-900 p-8 text-white">
                <div class="container-store">
                    <p class="font-serif text-5xl">Dress the everyday in gold.</p>
                    <a href="{{ route('shop') }}" class="btn btn-gold mt-6">Shop collections</a>
                </div>
            </div>
        @endforelse
    </div>

    @if($banners->count() > 1)
        <button type="button" class="hero-nav hero-nav-prev" data-hero-prev aria-label="Previous offer">‹</button>
        <button type="button" class="hero-nav hero-nav-next" data-hero-next aria-label="Next offer">›</button>
        <div class="hero-dots" data-hero-dots>
            @foreach($banners as $index => $banner)
                <button type="button" class="hero-dot @if($index === 0) is-active @endif" data-hero-dot="{{ $index }}" aria-label="Go to slide {{ $index + 1 }}"></button>
            @endforeach
        </div>
    @endif
</section>

<section class="container-store mt-10">
    <div class="flex gap-3 overflow-x-auto pb-2">
        @foreach($navCategories as $cat)
            <a href="{{ $cat->url() }}" class="whitespace-nowrap rounded-full border border-line bg-white px-4 py-2 text-sm">{{ $cat->name }}</a>
        @endforeach
    </div>
</section>

@if($sections->get('featured_categories')?->is_enabled !== false)
<section class="container-store mt-12">
    <x-section-heading :title="$sections->get('featured_categories')->title ?? 'Shop by category'" subtitle="Find something for every occasion" />
    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        @foreach($featuredCategories as $category)
            <a href="{{ $category->url() }}" class="overflow-hidden rounded-3xl bg-white">
                <img src="{{ $category->imageUrl() }}" alt="{{ $category->name }}" class="h-36 w-full object-cover md:h-44" loading="lazy">
                <p class="p-3 font-medium">{{ $category->name }}</p>
            </a>
        @endforeach
    </div>
</section>
@endif

@if($sections->get('featured_products')?->is_enabled !== false)
<section class="container-store mt-14">
    <x-section-heading :title="$sections->get('featured_products')->title ?? 'Featured'" />
    <div class="grid grid-cols-2 gap-5 md:grid-cols-4">
        @foreach($featuredProducts as $product)
            <x-product-card :product="$product" />
        @endforeach
    </div>
</section>
@endif

<div class="container-store"><x-ad position="home_top" /></div>

@if($sections->get('trending')?->is_enabled !== false)
<section class="container-store mt-8">
    <x-section-heading :title="$sections->get('trending')->title ?? 'Trending now'" />
    <div class="grid grid-cols-2 gap-5 md:grid-cols-4">
        @foreach($trendingProducts as $product)
            <x-product-card :product="$product" />
        @endforeach
    </div>
</section>
@endif

<div class="container-store"><x-ad position="home_middle" /></div>

@if($sections->get('bestsellers')?->is_enabled !== false)
<section class="container-store mt-8">
    <x-section-heading :title="$sections->get('bestsellers')->title ?? 'Best sellers'" />
    <div class="grid grid-cols-2 gap-5 md:grid-cols-4">
        @foreach($bestSellers as $product)
            <x-product-card :product="$product" />
        @endforeach
    </div>
</section>
@endif

@if($sections->get('new_arrivals')?->is_enabled !== false)
<section class="container-store mt-14">
    <x-section-heading :title="$sections->get('new_arrivals')->title ?? 'New arrivals'" />
    <div class="grid grid-cols-2 gap-5 md:grid-cols-4">
        @foreach($newArrivals as $product)
            <x-product-card :product="$product" />
        @endforeach
    </div>
</section>
@endif

<section class="container-store mt-16 grid gap-4 rounded-[2rem] bg-stone-900 p-8 text-white md:grid-cols-3">
    <div><p class="font-serif text-2xl">Free shipping</p><p class="mt-2 text-sm text-stone-300">On orders above {{ money(setting('free_shipping_amount', 999)) }}</p></div>
    <div><p class="font-serif text-2xl">Cash on Delivery</p><p class="mt-2 text-sm text-stone-300">Pay when your order arrives. No account needed.</p></div>
    <div><p class="font-serif text-2xl">Easy returns</p><p class="mt-2 text-sm text-stone-300">7-day easy returns on eligible products.</p></div>
</section>

<div class="container-store"><x-ad position="home_bottom" /></div>

@if($blogs->isNotEmpty() && $sections->get('blog')?->is_enabled !== false)
<section class="container-store mt-14">
    <x-section-heading title="From the journal">
        <a href="{{ route('blog.index') }}" class="text-sm text-gold">View all</a>
    </x-section-heading>
    <div class="grid gap-6 md:grid-cols-3">
        @foreach($blogs as $post)
            <a href="{{ route('blog.show', $post) }}" class="overflow-hidden rounded-3xl bg-white">
                <img src="{{ $post->imageUrl() }}" alt="{{ $post->title }}" class="h-44 w-full object-cover" loading="lazy">
                <div class="p-5">
                    <p class="font-serif text-2xl">{{ $post->title }}</p>
                    <p class="mt-2 line-clamp-2 text-sm text-muted">{{ $post->excerpt }}</p>
                </div>
            </a>
        @endforeach
    </div>
</section>
@endif
@endsection
