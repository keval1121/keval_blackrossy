@extends('layouts.storefront')

@php
    $seoTitle = $product->seo_title ?: $product->name.' | '.store_name();
    $seoDescription = $product->seo_description ?: $product->short_description;
    $ogImage = $product->displayImage()?->url('large');
    $ogType = 'product';
    $schema = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'image' => $product->images->map->url('large')->all(),
        'description' => strip_tags((string) $product->short_description),
        'sku' => $product->sku,
        'brand' => $product->brand?->name,
        'offers' => [
            '@type' => 'Offer',
            'priceCurrency' => 'INR',
            'price' => $product->selling_price,
            'availability' => $product->inStock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url' => $product->url(),
        ],
        'aggregateRating' => $product->reviews_count ? [
            '@type' => 'AggregateRating',
            'ratingValue' => $product->avg_rating,
            'reviewCount' => $product->reviews_count,
        ] : null,
    ]);
@endphp

@section('content')
<div class="container-store py-6 md:grid md:grid-cols-2 md:gap-10">
    <div>
        <div class="overflow-hidden rounded-[2rem] bg-white">
            <img id="main-image" src="{{ $product->displayImage()?->url('large') ?? $product->thumbUrl() }}" alt="{{ $product->name }}" class="aspect-square w-full object-cover">
        </div>
        @if($product->images->count() > 1)
            <div class="mt-3 flex gap-2 overflow-x-auto">
                @foreach($product->images as $image)
                    <button type="button" class="h-16 w-16 overflow-hidden rounded-xl" onclick="document.getElementById('main-image').src='{{ $image->url('large') }}'">
                        <img src="{{ $image->url('thumb') }}" alt="" class="h-full w-full object-cover" loading="lazy">
                    </button>
                @endforeach
            </div>
        @endif
    </div>
    <div>
        <nav class="text-xs text-muted">
            <a href="{{ route('home') }}">Home</a> /
            <a href="{{ $product->category->url() }}">{{ $product->category->name }}</a>
            @if($product->subCategory) / <a href="{{ $product->subCategory->url() }}">{{ $product->subCategory->name }}</a> @endif
        </nav>
        <h1 class="mt-3 font-serif text-4xl md:text-5xl">{{ $product->name }}</h1>
        @if($product->avg_rating)
            <p class="mt-2 text-sm">★ {{ number_format($product->avg_rating, 1) }} · {{ $product->reviews_count }} reviews</p>
        @endif
        <div class="mt-4 flex items-end gap-3">
            <p id="price" class="text-3xl font-semibold">{{ money($product->selling_price) }}</p>
            <p id="mrp" class="price-mrp">{{ money($product->mrp) }}</p>
            @if($product->discountPercent())
                <span class="badge-off">{{ $product->discountPercent() }}% OFF</span>
            @endif
        </div>
        <p id="stock-label" class="mt-2 text-sm text-muted">{{ $product->inStock() ? 'In stock' : 'Out of stock' }}</p>
        <p class="mt-4 text-sm leading-6 text-muted">{{ $product->short_description }}</p>

        <form data-ajax-cart action="{{ route('cart.add') }}" method="post" class="mt-6 space-y-5" id="buy-form">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <input type="hidden" name="variant_id" id="variant_id" value="{{ $product->activeVariants->first()?->id }}">
            @foreach($attributes as $group)
                <div>
                    <p class="mb-2 text-sm font-semibold">{{ $group['attribute']->name }}</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($group['values'] as $index => $value)
                            <label class="cursor-pointer">
                                <input type="radio" name="attr_{{ $group['attribute']->id }}" value="{{ $value->id }}" class="peer sr-only variant-attr" data-attr="{{ $group['attribute']->id }}" @checked($index===0)>
                                <span class="block rounded-full border border-line px-4 py-2 text-sm peer-checked:border-ink peer-checked:bg-ink peer-checked:text-white">{{ $value->value }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
            <div>
                <p class="mb-2 text-sm font-semibold">Quantity</p>
                <div class="flex w-40 items-center rounded-full border border-line bg-white">
                    <button type="button" class="h-12 w-12" onclick="const i=this.parentElement.querySelector('input'); i.value=Math.max(1, Number(i.value)-1)">−</button>
                    <input type="number" name="quantity" value="1" min="1" class="h-12 border-0 text-center">
                    <button type="button" class="h-12 w-12" onclick="this.parentElement.querySelector('input').value=Number(this.parentElement.querySelector('input').value)+1">+</button>
                </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <button class="btn btn-outline w-full" type="submit" onclick="this.form.buy_now.value=0">Add to cart</button>
                <button class="btn btn-gold w-full" type="submit" onclick="this.form.buy_now.value=1">Buy now</button>
            </div>
            <input type="hidden" name="buy_now" value="0">
            <p class="text-sm text-emerald-700">Cash on Delivery available</p>
        </form>
        <x-ad position="product_middle" />
    </div>
</div>

<div class="container-store mt-10 grid gap-8 md:grid-cols-3">
    <article class="md:col-span-2 rounded-3xl bg-white p-6">
        <h2 class="font-serif text-3xl">Description</h2>
        <div class="prose mt-4 max-w-none text-sm leading-7">{!! nl2br(e($product->description)) !!}</div>
        @if($product->specifications)
            <h3 class="mt-8 font-semibold">Specifications</h3>
            <dl class="mt-3 divide-y divide-line text-sm">
                @foreach($product->specifications as $spec)
                    <div class="flex justify-between gap-4 py-2"><dt class="text-muted">{{ $spec['label'] }}</dt><dd>{{ $spec['value'] }}</dd></div>
                @endforeach
            </dl>
        @endif
        <p class="mt-6 text-sm text-muted">{{ $product->shipping_info ?: 'Ships in 2-4 days. Free shipping above '.money(setting('free_shipping_amount', 999)).'.' }}</p>
        <p class="mt-2 text-sm text-muted">{{ $product->return_info ?: '7-day easy returns on unused products with tags intact.' }}</p>
    </article>
    <aside>
        <x-ad position="product_bottom" />
        @if(setting('reviews_enabled', true))
            <form method="post" action="{{ route('product.review', $product) }}" class="mt-4 space-y-3 rounded-3xl bg-white p-5">
                @csrf
                <h3 class="font-semibold">Write a review</h3>
                <input name="name" placeholder="Your name" required>
                <input name="order_number" placeholder="Order ID" required>
                <input name="mobile" placeholder="Mobile" required>
                <select name="rating"><option value="5">5 stars</option><option value="4">4 stars</option><option value="3">3 stars</option><option value="2">2 stars</option><option value="1">1 star</option></select>
                <textarea name="body" rows="3" placeholder="Your review"></textarea>
                <button class="btn btn-primary w-full">Submit for approval</button>
            </form>
        @endif
    </aside>
</div>

@if($product->approvedReviews->isNotEmpty())
<section class="container-store mt-8">
    <h2 class="font-serif text-3xl">Reviews</h2>
    <div class="mt-4 space-y-4">
        @foreach($product->approvedReviews as $review)
            <article class="rounded-3xl bg-white p-5">
                <p class="font-medium">{{ $review->name }} · ★ {{ $review->rating }}</p>
                <p class="mt-2 text-sm text-muted">{{ $review->body }}</p>
            </article>
        @endforeach
    </div>
</section>
@endif

<section class="container-store mt-12">
    <x-section-heading title="You may also like" />
    <div class="grid grid-cols-2 gap-5 md:grid-cols-4">
        @foreach($related as $item)
            <x-product-card :product="$item" />
        @endforeach
    </div>
</section>

@if($recentProducts->isNotEmpty())
<section class="container-store mt-12">
    <x-section-heading title="Recently viewed" />
    <div class="grid grid-cols-2 gap-5 md:grid-cols-4">
        @foreach($recentProducts as $item)
            <x-product-card :product="$item" />
        @endforeach
    </div>
</section>
@endif

<script>
    const variants = @json($variantMap);
    const matchVariant = () => {
        const selected = {};
        document.querySelectorAll('.variant-attr:checked').forEach((el) => selected[el.dataset.attr] = Number(el.value));
        const found = variants.find((variant) => Object.keys(selected).every((key) => Number(variant.attrs[key]) === selected[key]));
        const id = document.getElementById('variant_id');
        if (found && id) {
            id.value = found.id;
            document.getElementById('price').textContent = new Intl.NumberFormat('en-IN', {style:'currency', currency:'INR', maximumFractionDigits:0}).format(found.price);
            document.getElementById('mrp').textContent = new Intl.NumberFormat('en-IN', {style:'currency', currency:'INR', maximumFractionDigits:0}).format(found.mrp);
            document.getElementById('stock-label').textContent = found.stock > 0 ? found.stock + ' in stock' : 'Out of stock';
        }
    };
    document.querySelectorAll('.variant-attr').forEach((el) => el.addEventListener('change', matchVariant));
    matchVariant();
</script>
@endsection
