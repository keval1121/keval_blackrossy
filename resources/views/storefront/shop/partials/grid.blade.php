@if($products->isEmpty())
    <p class="rounded-3xl bg-white p-10 text-center text-muted">No products match your filters.</p>
@else
    <div class="grid grid-cols-2 gap-5 lg:grid-cols-3">
        @foreach($products as $index => $product)
            <x-product-card :product="$product" />
            @if($index === 5 && ! $loop->last && ! request()->ajax() && ad_code('listing_middle'))
                <div class="col-span-2 lg:col-span-3"><x-ad position="listing_middle" /></div>
            @endif
        @endforeach
    </div>
    <div class="mt-8">{{ $products->links() }}</div>
@endif
