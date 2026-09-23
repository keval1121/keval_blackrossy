@extends('layouts.storefront')
@section('content')
<div class="container-store py-8">
    <h1 class="font-serif text-4xl">Categories</h1>
    <div class="mt-8 grid gap-6 md:grid-cols-2">
        @foreach($categories as $category)
            <article class="rounded-3xl bg-white p-5">
                <a href="{{ $category->url() }}" class="font-serif text-3xl">{{ $category->name }}</a>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach($category->children as $child)
                        <a href="{{ $child->url() }}" class="rounded-full bg-sand px-3 py-1 text-sm">{{ $child->name }}</a>
                    @endforeach
                </div>
            </article>
        @endforeach
    </div>
</div>
@endsection
