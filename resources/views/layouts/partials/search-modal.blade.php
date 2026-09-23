<div id="search-modal" class="fixed inset-0 z-50 hidden bg-cream/95 p-5 backdrop-blur md:hidden">
    <form action="{{ route('search') }}" method="get" class="mt-8">
        <input name="q" autofocus placeholder="Search products..." class="text-lg">
        <div class="mt-4 flex gap-3">
            <button class="btn btn-primary flex-1">Search</button>
            <button type="button" data-toggle="search-modal" class="btn btn-outline flex-1">Close</button>
        </div>
    </form>
</div>
