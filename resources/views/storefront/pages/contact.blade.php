@extends('layouts.storefront')
@section('content')
<div class="container-store max-w-3xl py-12">
    <h1 class="font-serif text-4xl">Contact us</h1>
    <p class="mt-3 text-muted leading-7">
        Reach the {{ store_name() }} team by email, phone or WhatsApp for order help, returns or privacy requests. Please share your order number if you have one — we reply on business days.
    </p>

    <div class="mt-8 grid gap-6 rounded-3xl bg-white p-6 text-sm leading-7 md:grid-cols-2">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted">Email</p>
            <p class="mt-1"><a class="underline" href="mailto:{{ setting('contact_email', 'hello@blackrossy.com') }}">{{ setting('contact_email', 'hello@blackrossy.com') }}</a></p>
            <p class="mt-4 text-xs font-semibold uppercase tracking-[0.2em] text-muted">Phone</p>
            <p class="mt-1"><a class="underline" href="tel:{{ preg_replace('/[^\d+]+/', '', setting('contact_number', '9876543210')) }}">{{ setting('contact_number', '9876543210') }}</a></p>
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
</div>
@endsection
