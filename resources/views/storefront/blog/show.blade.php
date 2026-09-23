@extends('layouts.storefront')
@php
    $seoTitle = $blog->seo_title ?: $blog->title;
    $seoDescription = $blog->seo_description ?: $blog->excerpt;
    $ogImage = $blog->imageUrl();
    $ogType = 'article';
    $schema = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $blog->title,
        'datePublished' => $blog->published_at?->toIso8601String(),
        'image' => $blog->imageUrl(),
    ]);
@endphp
@section('content')
<article class="container-store max-w-3xl py-10">
    <x-ad position="blog_top" />
    <p class="text-xs uppercase tracking-widest text-muted">{{ $blog->category?->name }} · {{ $blog->published_at?->format('d M Y') }}</p>
    <h1 class="mt-3 font-serif text-5xl">{{ $blog->title }}</h1>
    <img src="{{ $blog->imageUrl() }}" alt="{{ $blog->title }}" class="my-8 w-full rounded-[2rem] object-cover">
    <div class="prose max-w-none leading-8">{!! nl2br(e($blog->content)) !!}</div>
    <x-ad position="blog_middle" />
    <x-ad position="blog_bottom" />
    @if($related->isNotEmpty())
        <h2 class="mt-12 font-serif text-3xl">More from the journal</h2>
        <div class="mt-4 space-y-2">
            @foreach($related as $item)
                <a class="block text-gold" href="{{ route('blog.show', $item) }}">{{ $item->title }}</a>
            @endforeach
        </div>
    @endif
</article>
@endsection
