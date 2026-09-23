@if($items->isEmpty())
    <div class="rounded-[2rem] bg-white p-10 text-center">
        <p class="font-serif text-4xl">Your cart is empty</p>
        <a href="{{ route('shop') }}" class="btn btn-primary mt-6">Continue shopping</a>
    </div>
@else
    <h1 class="font-serif text-4xl">Cart</h1>
    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_320px]">
        <div class="space-y-4">
            @foreach($items as $line)
                <article class="flex gap-4 rounded-3xl bg-white p-4">
                    <img src="{{ $line['image'] }}" alt="" class="h-24 w-24 rounded-2xl object-cover">
                    <div class="flex-1">
                        <a href="{{ $line['url'] }}" class="font-medium">{{ $line['name'] }}</a>
                        @if($line['variant'])<p class="text-xs text-muted">{{ $line['variant'] }}</p>@endif
                        <p class="mt-1">{{ money($line['total']) }}</p>
                        <form data-ajax-cart action="{{ route('cart.update') }}" method="post" class="mt-2 flex items-center gap-2">
                            @csrf
                            <input type="hidden" name="item_id" value="{{ $line['item']->id }}">
                            <input type="number" name="quantity" value="{{ $line['qty'] }}" min="1" class="w-20">
                            <button class="text-sm text-gold">Update</button>
                        </form>
                    </div>
                    <form data-ajax-cart action="{{ route('cart.remove') }}" method="post">
                        @csrf
                        <input type="hidden" name="item_id" value="{{ $line['item']->id }}">
                        <button class="text-sm text-rose-700">Remove</button>
                    </form>
                </article>
            @endforeach
        </div>
        <aside class="h-fit rounded-3xl bg-white p-5">
            <p class="flex justify-between py-1"><span>Subtotal</span><span>{{ money($subtotal) }}</span></p>
            <p class="flex justify-between py-1"><span>Discount</span><span>- {{ money($discount) }}</span></p>
            <p class="flex justify-between py-1"><span>Delivery</span><span>{{ $delivery ? money($delivery) : 'Free' }}</span></p>
            <p class="mt-3 flex justify-between border-t border-line pt-3 text-lg font-semibold"><span>Total</span><span>{{ money($total) }}</span></p>
            @if(setting('coupons_enabled', true))
                <form data-ajax-cart action="{{ route('cart.coupon') }}" method="post" class="mt-4 flex gap-2">
                    @csrf
                    <input name="code" placeholder="Coupon code" value="{{ $coupon?->code }}">
                    <button class="btn btn-outline px-4">Apply</button>
                </form>
            @endif
            <a href="{{ route('checkout.index') }}" class="btn btn-gold mt-5 w-full">Proceed to checkout</a>
        </aside>
    </div>
@endif
