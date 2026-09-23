<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ store_name() }} Admin</title>
    @vite(['resources/css/admin.css'])
</head>
<body class="min-h-screen bg-stone-100 font-sans text-stone-800">
<div class="grid min-h-screen md:grid-cols-[240px_1fr]">
    <aside class="bg-stone-950 p-5 text-stone-200">
        <a href="{{ route('admin.dashboard') }}" class="block text-2xl font-semibold text-white">{{ store_name() }}</a>
        <p class="mt-1 text-xs uppercase tracking-widest text-amber-500">Admin</p>
        <nav class="mt-8 space-y-1 text-sm">
            @foreach([
                'admin.dashboard' => 'Dashboard',
                'admin.orders.index' => 'Orders',
                'admin.products.index' => 'Products',
                'admin.categories.index' => 'Categories',
                'admin.brands.index' => 'Brands',
                'admin.banners.index' => 'Banners',
                'admin.homepage.index' => 'Homepage',
                'admin.ads.index' => 'Ads',
                'admin.blog.index' => 'Blog',
                'admin.pages.index' => 'Pages',
                'admin.coupons.index' => 'Coupons',
                'admin.reviews.index' => 'Reviews',
                'admin.reports.index' => 'Reports',
                'admin.messages.index' => 'Messages',
                'admin.blocklist.index' => 'Blocklist',
                'admin.settings.index' => 'Settings',
            ] as $route => $label)
                <a href="{{ route($route) }}" class="block rounded-xl px-3 py-2 hover:bg-white/10 {{ request()->routeIs($route) ? 'bg-white/10 text-white' : '' }}">{{ $label }}</a>
            @endforeach
        </nav>
        <form method="post" action="{{ route('admin.logout') }}" class="mt-8">@csrf<button class="text-sm text-stone-400">Log out</button></form>
    </aside>
    <div>
        <header class="flex items-center justify-between border-b border-stone-200 bg-white px-6 py-4">
            <h1 class="text-lg font-semibold">@yield('title', 'Dashboard')</h1>
            <a href="{{ route('home') }}" class="text-sm text-amber-700" target="_blank">View store</a>
        </header>
        <main class="p-6">
            @if(session('status'))<p class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-emerald-800">{{ session('status') }}</p>@endif
            @if($errors->any())<div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-rose-800">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
