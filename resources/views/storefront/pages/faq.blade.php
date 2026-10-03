@extends('layouts.storefront')

@push('head')
<style>
    .br-faq { border-top: 1px solid var(--color-line, #e7e0d6); }
    .br-faq-wrap { width: min(760px, calc(100% - 2rem)); margin: 0 auto; padding: 3.5rem 0 4.5rem; }
    .br-faq-title {
        margin: 0;
        text-align: center;
        font-family: var(--font-sans, Outfit, sans-serif);
        font-size: clamp(1.75rem, 3vw, 2.25rem);
        font-weight: 700;
        letter-spacing: -0.02em;
        color: #1c1917;
    }
    .br-faq-lead {
        margin: .85rem auto 0;
        max-width: 34rem;
        text-align: center;
        color: #78716c;
        font-size: .98rem;
        line-height: 1.7;
    }
    .br-faq-section { margin-top: 3.25rem; }
    .br-faq-section-title {
        margin: 0 0 1.5rem;
        text-align: center;
        font-size: clamp(1.45rem, 2.2vw, 1.85rem);
        font-weight: 700;
        color: #1c1917;
    }
    .br-faq-item {
        border-bottom: 1px solid #e5e5e5;
        background: transparent;
    }
    .br-faq-item:first-of-type { border-top: 1px solid #e5e5e5; }
    .br-faq-summary {
        list-style: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.25rem;
        padding: 1.35rem 0;
        cursor: pointer;
        user-select: none;
    }
    .br-faq-summary::-webkit-details-marker,
    .br-faq-summary::marker { display: none; content: ''; }
    .br-faq-q {
        font-size: 1.05rem;
        font-weight: 700;
        line-height: 1.45;
        color: #1c1917;
    }
    .br-faq-chevron {
        flex: 0 0 auto;
        width: 1.1rem;
        height: 1.1rem;
        color: #57534e;
        transition: transform .2s ease;
    }
    .br-faq-item[open] .br-faq-chevron { transform: rotate(180deg); }
    .br-faq-a {
        padding: 0 1.75rem 1.35rem 0;
        color: #57534e;
        font-size: 1rem;
        line-height: 1.75;
    }
    .br-faq-a a {
        color: #2563eb;
        text-decoration: underline;
        text-underline-offset: 2px;
    }
    .br-faq-a a:hover { color: #1d4ed8; }
    .br-faq-help {
        margin-top: 3.5rem;
        padding: 1.75rem 1.5rem;
        border: 1px solid #e7e0d6;
        border-radius: 1.5rem;
        background: #fffdf9;
        text-align: center;
    }
    .br-faq-help p { margin: 0; color: #78716c; font-size: .95rem; line-height: 1.7; }
    .br-faq-help-actions {
        margin-top: 1.1rem;
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: .75rem;
    }
</style>
@endpush

@section('content')
<div class="br-faq">
    <div class="br-faq-wrap">
        <h1 class="br-faq-title">Frequently Asked Questions</h1>
        <p class="br-faq-lead">
            Quick answers for shopping Rossy Apparel, Lustre, Stride and Carry at Black Rossy with Cash on Delivery.
        </p>

        <div class="faq-list">
            @foreach($groups as $group)
                <section class="br-faq-section">
                    <h2 class="br-faq-section-title">{{ $group['title'] }}</h2>

                    @foreach($group['items'] as $item)
                        <details class="br-faq-item faq-item" @if($loop->parent->first && $loop->first) open @endif>
                            <summary class="br-faq-summary">
                                <span class="br-faq-q">{{ $item['question'] }}</span>
                                <svg class="br-faq-chevron" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                    <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </summary>
                            <div class="br-faq-a">
                                {!! $item['answer'] !!}
                            </div>
                        </details>
                    @endforeach
                </section>
            @endforeach
        </div>

        <div class="br-faq-help">
            <p>Need help with an order? Share your order number and we will assist you.</p>
            <div class="br-faq-help-actions">
                <a href="{{ route('contact') }}" class="btn btn-primary">Contact us</a>
                <a href="{{ route('track') }}" class="btn btn-outline">Track order</a>
            </div>
        </div>
    </div>
</div>
@endsection
