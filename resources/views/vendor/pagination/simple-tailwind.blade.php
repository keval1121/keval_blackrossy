@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-center gap-2">
        @if ($paginator->onFirstPage())
            <span class="inline-flex min-h-10 items-center rounded-full border border-line px-4 text-sm font-semibold text-muted">
                Previous
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex min-h-10 items-center rounded-full border border-ink bg-ink px-4 text-sm font-semibold text-white transition hover:bg-black">
                Previous
            </a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex min-h-10 items-center rounded-full border border-ink bg-ink px-4 text-sm font-semibold text-white transition hover:bg-black">
                Next
            </a>
        @else
            <span class="inline-flex min-h-10 items-center rounded-full border border-line px-4 text-sm font-semibold text-muted">
                Next
            </span>
        @endif
    </nav>
@endif
