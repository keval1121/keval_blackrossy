<footer class="mt-16 border-t border-line bg-cream pb-8 pt-12">
    <div class="container-store grid gap-10 md:grid-cols-4">
        <div>
            <p class="font-serif text-3xl">{{ store_name() }}</p>
            <p class="mt-3 text-sm leading-6 text-muted">A curated boutique for clothing, jewellery, footwear and thoughtful gifts. Guest checkout. Cash on Delivery.</p>
        </div>
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[0.2em] text-muted">Shop</p>
            <div class="space-y-2 text-sm">
                @foreach($navCategories->take(6) as $cat)
                    <a class="block" href="{{ $cat->url() }}">{{ $cat->name }}</a>
                @endforeach
            </div>
        </div>
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[0.2em] text-muted">Help</p>
            <div class="space-y-2 text-sm">
                <a class="block" href="{{ route('track') }}">Track Order</a>
                <a class="block" href="{{ url('/shipping-policy') }}">Shipping Policy</a>
                <a class="block" href="{{ url('/return-policy') }}">Return Policy</a>
                <a class="block" href="{{ url('/cancellation-policy') }}">Cancellation</a>
                <a class="block" href="{{ route('contact') }}">Contact</a>
            </div>
        </div>
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[0.2em] text-muted">Company</p>
            <div class="space-y-2 text-sm">
                <a class="block" href="{{ url('/about') }}">About</a>
                <a class="block" href="{{ url('/privacy-policy') }}">Privacy Policy</a>
                <a class="block" href="{{ url('/cookie-policy') }}">Cookie Policy</a>
                <a class="block" href="{{ url('/terms') }}">Terms &amp; Conditions</a>
                <a class="block" href="{{ url('/refund-policy') }}">Refund Policy</a>
            </div>
            @if(setting('whatsapp_number'))
                <a href="https://wa.me/{{ preg_replace('/\D+/', '', setting('whatsapp_number')) }}" class="btn btn-gold mt-5 text-sm">WhatsApp us</a>
            @endif
        </div>
    </div>
    <p class="container-store mt-10 text-xs text-muted">© {{ date('Y') }} {{ store_name() }}. All rights reserved.</p>
</footer>
