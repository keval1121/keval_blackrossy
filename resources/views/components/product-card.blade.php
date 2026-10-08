@props(['product'])
@php($off = $product->discountPercent())
<a href="{{ $product->url() }}" class="product-card group block">
    <div class="relative overflow-hidden rounded-3xl bg-white">
        <img src="{{ $product->thumbUrl() }}" alt="{{ $product->name }}" width="400" height="400" loading="lazy" decoding="async" class="product-card-img aspect-square w-full object-cover transition duration-300">
        @if($off)
            <span class="badge-off absolute left-3 top-3">{{ $off }}% OFF</span>
        @endif
    </div>
    <div class="pt-3">
        <p class="line-clamp-2 text-sm font-medium">{{ $product->name }}</p>
        <p class="mt-1 flex items-baseline gap-2">
            <span class="font-semibold">{{ money($product->selling_price) }}</span>
            @if($product->mrp > $product->selling_price)
                <span class="price-mrp text-xs">{{ money($product->mrp) }}</span>
            @endif
        </p>
    </div>
</a>
