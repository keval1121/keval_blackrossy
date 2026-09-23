<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $seoTitle ?? setting('seo_title', store_name().' | Fashion, Jewellery & Lifestyle') }}</title>
    <meta name="description" content="{{ $seoDescription ?? setting('seo_description', 'Shop clothing, jewellery, footwear, bags and gifts with easy Cash on Delivery.') }}">
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">
    @if(setting('favicon'))
        <link rel="icon" href="{{ asset('storage/'.setting('favicon')) }}">
    @endif
    <meta property="og:title" content="{{ $seoTitle ?? store_name() }}">
    <meta property="og:description" content="{{ $seoDescription ?? setting('seo_description') }}">
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
    @if(!empty($ogImage))
        <meta property="og:image" content="{{ $ogImage }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @isset($schema)
        <script type="application/ld+json">{!! $schema !!}</script>
    @endisset
</head>
<body class="bg-sand text-ink antialiased pb-24 md:pb-0">
    <div id="toast-wrap" class="toast-wrap"></div>
    @include('layouts.partials.header')
    <main>
        @if(session('status'))
            <div class="container-store pt-4">
                <p class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
            </div>
        @endif
        @if($errors->any())
            <div class="container-store pt-4">
                <p class="rounded-2xl bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</p>
            </div>
        @endif
        @yield('content')
    </main>
    @include('layouts.partials.footer')
    @include('layouts.partials.bottom-nav')
    @include('layouts.partials.search-modal')
    @include('layouts.partials.cookie-notice')
</body>
</html>
