@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex justify-center">
        <span class="inline-flex flex-wrap items-center justify-center gap-1.5">
            @if ($paginator->onFirstPage())
                <span class="inline-flex min-h-10 items-center rounded-full border border-line px-4 text-sm font-semibold text-muted">
                    Previous
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex min-h-10 items-center rounded-full border border-ink bg-ink px-4 text-sm font-semibold text-white transition hover:bg-black">
                    Previous
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="inline-flex min-h-10 min-w-10 items-center justify-center text-sm text-muted">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-full bg-ink text-sm font-semibold text-white">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-full border border-line bg-white text-sm font-semibold text-ink transition hover:border-ink" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex min-h-10 items-center rounded-full border border-ink bg-ink px-4 text-sm font-semibold text-white transition hover:bg-black">
                    Next
                </a>
            @else
                <span class="inline-flex min-h-10 items-center rounded-full border border-line px-4 text-sm font-semibold text-muted">
                    Next
                </span>
            @endif
        </span>
    </nav>
@endif
