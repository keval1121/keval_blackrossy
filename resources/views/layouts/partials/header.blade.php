<header class="sticky top-0 z-40 border-b border-line bg-cream/95 backdrop-blur">
    <div class="container-store flex items-center gap-4 py-3">
        <a href="{{ route('home') }}" class="font-serif text-3xl tracking-tight text-ink">{{ store_name() }}</a>
        <form action="{{ route('search') }}" method="get" class="relative hidden flex-1 md:block">
            <input id="live-search" name="q" value="{{ request('q') }}" placeholder="Search apparel, lustre, stride, carry..." class="pl-4 pr-12" autocomplete="off">
            <div id="search-suggest" class="absolute z-50 mt-2 hidden w-full overflow-hidden rounded-2xl border border-line bg-white shadow-xl"></div>
        </form>
        <nav class="ml-auto hidden items-center gap-5 text-sm font-medium lg:flex">
            <a href="{{ route('home') }}" class="hover:text-gold">Home</a>
            <div class="group relative">
                <a href="{{ route('categories') }}" class="hover:text-gold">Categories</a>
                <div class="invisible absolute left-0 top-full z-50 grid w-[520px] grid-cols-2 gap-4 rounded-2xl border border-line bg-white p-5 opacity-0 shadow-xl transition group-hover:visible group-hover:opacity-100">
                    @foreach($navCategories as $cat)
                        <div>
                            <a href="{{ $cat->url() }}" class="font-semibold">{{ $cat->name }}</a>
                            <div class="mt-2 space-y-1 text-sm text-muted">
                                @foreach($cat->children as $child)
                                    <a class="block hover:text-ink" href="{{ $child->url() }}">{{ $child->name }}</a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <a href="{{ route('faq') }}" class="hover:text-gold">FAQ</a>
            <a href="{{ route('about') }}" class="hover:text-gold">About</a>
            <a href="{{ route('contact') }}" class="hover:text-gold">Contact</a>
            <a href="{{ route('policy') }}" class="hover:text-gold">Policy</a>
        </nav>
        <div class="ml-auto flex items-center gap-3 md:ml-0">
            <button class="md:hidden" data-toggle="search-modal" aria-label="Search">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="9" r="6"/><path d="M14 14l5 5"/></svg>
            </button>
            <a href="{{ route('cart.index') }}" class="relative" aria-label="Cart">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16l-1.5 11h-13z"/><path d="M8 6V5a3 3 0 016 0v1"/></svg>
                <span data-cart-count class="{{ $cartCount ? '' : 'hidden' }} absolute -right-2 -top-2 flex h-5 min-w-5 items-center justify-center rounded-full bg-gold px-1 text-[10px] font-bold text-white">{{ $cartCount }}</span>
            </a>
        </div>
    </div>
    <div class="border-t border-line md:hidden">
        <div class="container-store flex gap-4 overflow-x-auto py-2 text-sm">
            @foreach($navCategories as $cat)
                <a href="{{ $cat->url() }}" class="whitespace-nowrap text-muted">{{ $cat->name }}</a>
            @endforeach
        </div>
    </div>
</header>
