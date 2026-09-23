@extends('layouts.storefront')
@php
    $seoTitle = $page->seo_title ?: $page->title;
    $seoDescription = $page->seo_description;
@endphp
@section('content')
<article class="container-store max-w-3xl py-12">
    <h1 class="font-serif text-5xl">{{ $page->title }}</h1>
    <div class="prose mt-8 max-w-none leading-8">{!! nl2br(e($page->content)) !!}</div>
</article>
@endsection
