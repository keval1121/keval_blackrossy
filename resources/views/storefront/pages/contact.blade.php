@extends('layouts.storefront')
@section('content')
<div class="container-store max-w-3xl py-12">
    <h1 class="font-serif text-4xl">Contact us</h1>
    <p class="mt-3 text-muted leading-7">
        Reach the Black Rossy team for order help, returns, or privacy requests. We respond on business days.
    </p>

    <div class="mt-8 grid gap-6 rounded-3xl bg-white p-6 text-sm leading-7 md:grid-cols-2">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted">Email</p>
            <p class="mt-1">{{ setting('contact_email', 'hello@blackrossy.com') }}</p>
            <p class="mt-4 text-xs font-semibold uppercase tracking-[0.2em] text-muted">Phone</p>
            <p class="mt-1">{{ setting('contact_number', '9876543210') }}</p>
            @if(setting('whatsapp_number'))
                <p class="mt-4 text-xs font-semibold uppercase tracking-[0.2em] text-muted">WhatsApp</p>
                <p class="mt-1">
                    <a class="underline" href="https://wa.me/{{ preg_replace('/\D+/', '', setting('whatsapp_number')) }}">
                        Chat on WhatsApp
                    </a>
                </p>
            @endif
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted">Business address</p>
            <p class="mt-1 whitespace-pre-line">{{ setting('address', 'India') }}</p>
            <p class="mt-4 text-xs text-muted">
                For privacy requests, include “Privacy request”, your order number (if any), and the registered mobile number.
            </p>
            <p class="mt-2 text-xs text-muted">
                Legal pages:
                <a class="underline" href="{{ url('/privacy-policy') }}">Privacy</a>,
                <a class="underline" href="{{ url('/terms') }}">Terms</a>,
                <a class="underline" href="{{ url('/cookie-policy') }}">Cookies</a>.
            </p>
        </div>
    </div>

    <form method="post" action="{{ route('contact.store') }}" class="mt-8 space-y-4 rounded-3xl bg-white p-6">
        @csrf
        <input name="name" placeholder="Name *" required value="{{ old('name') }}">
        <input name="email" type="email" placeholder="Email" value="{{ old('email') }}">
        <input name="mobile" placeholder="Mobile" value="{{ old('mobile') }}">
        <input name="subject" placeholder="Subject" value="{{ old('subject') }}">
        <textarea name="message" rows="5" placeholder="Message *" required>{{ old('message') }}</textarea>
        <button class="btn btn-primary w-full">Send message</button>
    </form>
</div>
@endsection
