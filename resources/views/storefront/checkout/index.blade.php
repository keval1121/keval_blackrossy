@extends('layouts.storefront')
@php
    $seoTitle = 'Checkout | '.store_name();
    $hideAds = true;
@endphp
@push('head')
    <meta name="robots" content="noindex, follow">
@endpush
@section('content')
<div class="container-store py-8 md:py-12">
    <header class="checkout-head">
        <div>
            <p class="checkout-eyebrow">Black Rossy · Secure checkout</p>
            <h1 class="font-serif text-4xl md:text-5xl">Almost yours.</h1>
            <p class="mt-2 text-sm text-muted">Share your contact and delivery details, and we'll get your order on its way.</p>
        </div>
        <ol class="checkout-steps" aria-label="Checkout progress">
            <li class="is-done">
                <a href="{{ route('cart.index') }}"><span class="checkout-step-dot">&#10003;</span>Bag</a>
            </li>
            <li class="is-active" aria-current="step"><span class="checkout-step-dot">2</span>Confirmation Details</li>
        </ol>
    </header>

    <div class="mt-8 grid grid-cols-[minmax(0,1fr)] gap-6 lg:grid-cols-[minmax(0,1fr)_380px] lg:gap-8">
        <form method="post" action="{{ route('checkout.place') }}" class="checkout-form space-y-5">
            @csrf
            <section class="checkout-section">
                <div class="checkout-section-head">
                    <span class="checkout-section-num">01</span>
                    <div>
                        <h2 class="font-serif text-2xl">Contact details</h2>
                        <p class="text-sm text-muted">We'll use these to confirm your order.</p>
                    </div>
                </div>
                <div class="space-y-4">
                    <div>
                        <label for="name" class="field-label">Full name <span class="field-required">*</span></label>
                        <input name="name" id="name" value="{{ old('name') }}" placeholder="e.g. Priya Sharma" autocomplete="name" required>
                    </div>
                    <div>
                        <label for="mobile" class="field-label">Mobile number <span class="field-required">*</span></label>
                        <div @class(['grid gap-3', 'sm:grid-cols-[1fr_auto]' => setting('otp_enabled')])>
                            <div class="phone-field">
                                <span class="phone-field-prefix" aria-hidden="true">+91</span>
                                <input name="mobile" id="mobile" value="{{ old('mobile') }}" placeholder="9876543210" inputmode="numeric" maxlength="10" autocomplete="tel-national" required>
                            </div>
                            @if(setting('otp_enabled'))
                                <button type="button" id="otp-btn" class="btn btn-outline">Send OTP</button>
                            @endif
                        </div>
                    </div>
                    @if(setting('otp_enabled'))
                        <div>
                            <label for="otp" class="field-label">OTP <span class="field-required">*</span></label>
                            <input name="otp" id="otp" placeholder="e.g. 482913" inputmode="numeric" maxlength="6" autocomplete="one-time-code">
                            <p id="otp-help" class="mt-1 text-xs text-muted"></p>
                        </div>
                    @endif
                </div>
            </section>

            <section class="checkout-section">
                <div class="checkout-section-head">
                    <span class="checkout-section-num">02</span>
                    <div>
                        <h2 class="font-serif text-2xl">Delivery address</h2>
                        <p class="text-sm text-muted">Where should your parcel arrive?</p>
                    </div>
                </div>
                <div class="space-y-4">
                    <div>
                        <label for="address" class="field-label">Address <span class="field-required">*</span></label>
                        <textarea name="address" id="address" rows="3" placeholder="e.g. Flat 1204, Sea Breeze Tower, Linking Road" autocomplete="street-address" required>{{ old('address') }}</textarea>
                    </div>
                    <div>
                        <label for="area" class="field-label">Area / Landmark <span class="field-optional">(optional)</span></label>
                        <input name="area" id="area" value="{{ old('area') }}" placeholder="e.g. Bandra West, near Bandra Station">
                    </div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label for="city" class="field-label">City <span class="field-required">*</span></label>
                            <input name="city" id="city" value="{{ old('city') }}" placeholder="e.g. Mumbai" autocomplete="address-level2" required>
                        </div>
                        <div>
                            <label for="state" class="field-label">State <span class="field-required">*</span></label>
                            <select name="state" id="state" autocomplete="address-level1" required>
                                <option value="">Select state</option>
                                @foreach(indian_states() as $state)
                                    <option value="{{ $state }}" @selected(old('state')===$state)>{{ $state }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="pincode" class="field-label">Pincode <span class="field-required">*</span></label>
                            <input name="pincode" id="pincode" value="{{ old('pincode') }}" placeholder="e.g. 400050" inputmode="numeric" maxlength="6" autocomplete="postal-code" required>
                        </div>
                    </div>
                </div>
            </section>

            <div class="flex gap-3 rounded-2xl border border-line border-l-4 border-l-gold bg-cream p-4" role="note">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-gold-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 16v-4M12 8h.01" stroke-linecap="round"/>
                </svg>
                <div>
                    <p class="font-serif text-xl font-semibold text-ink">Before you continue</p>
                    <ul class="mt-2 space-y-1.5 text-sm leading-6 text-muted">
                        <li>Double-check your name and mobile number. We use them to confirm your order and coordinate delivery.</li>
                        <li>Add a complete address with a nearby landmark so our courier can find you easily.</li>
                        <li>Incorrect details can delay or prevent delivery.</li>
                    </ul>
                </div>
            </div>

            <button class="btn btn-gold checkout-cta w-full">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <rect x="4" y="11" width="16" height="10" rx="2"/>
                    <path d="M8 11V7a4 4 0 0 1 8 0v4"/>
                </svg>
                Proceed to payment
                <span class="checkout-cta-total">{{ money($total) }}</span>
            </button>
        </form>

        <aside class="checkout-receipt">
            <div class="flex items-baseline justify-between">
                <h2 class="font-serif text-2xl">Your bag</h2>
                <span class="text-xs uppercase tracking-[0.18em] text-white/60">{{ $count }} {{ Str::plural('item', $count) }}</span>
            </div>
            <ul class="mt-5 space-y-4">
                @foreach($items as $line)
                    <li class="flex items-center gap-3">
                        <span class="checkout-receipt-thumb">
                            <img src="{{ $line['image'] }}" alt="" loading="lazy">
                            <span class="checkout-receipt-qty">{{ $line['qty'] }}</span>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium">{{ $line['name'] }}</span>
                            @if($line['variant'])
                                <span class="block truncate text-xs text-white/60">{{ $line['variant'] }}</span>
                            @endif
                        </span>
                        <span class="text-sm">{{ money($line['total']) }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="checkout-receipt-divider" aria-hidden="true"></div>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-white/70">Subtotal</dt><dd>{{ money($subtotal) }}</dd></div>
                @if($discount > 0)
                    <div class="flex justify-between"><dt class="text-white/70">Discount</dt><dd>- {{ money($discount) }}</dd></div>
                @endif
                <div class="flex justify-between"><dt class="text-white/70">Delivery</dt><dd>{{ $delivery ? money($delivery) : 'Free' }}</dd></div>
            </dl>
            <div class="mt-5 flex items-end justify-between border-t border-white/15 pt-5">
                <span class="text-xs uppercase tracking-[0.18em] text-white/60">Total</span>
                <span class="font-serif text-4xl text-gold">{{ money($total) }}</span>
            </div>
        </aside>
    </div>
</div>
@if(setting('otp_enabled'))
<script>
    document.getElementById('otp-btn')?.addEventListener('click', async () => {
        const mobile = document.getElementById('mobile').value;
        try {
            const data = await BlackRossy.jsonFetch('{{ route('checkout.otp') }}', { method: 'POST', body: JSON.stringify({ mobile, _token: BlackRossy.csrf() }) });
            document.getElementById('otp-help').textContent = data.debug_otp ? `Demo OTP: ${data.debug_otp}` : data.message;
        } catch (e) { BlackRossy.toast(e.message, 'error'); }
    });
</script>
@endif
@endsection
