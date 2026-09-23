@extends('layouts.storefront')
@section('content')
<div class="container-store py-10">
    <h1 class="font-serif text-4xl">Journal</h1>
    <div class="mt-8 grid gap-6 md:grid-cols-3">
        @foreach($posts as $post)
            <a href="{{ route('blog.show', $post) }}" class="overflow-hidden rounded-3xl bg-white">
                <img src="{{ $post->imageUrl() }}" alt="{{ $post->title }}" class="h-48 w-full object-cover" loading="lazy">
                <div class="p-5">
                    <p class="text-xs uppercase tracking-widest text-muted">{{ $post->published_at?->format('d M Y') }}</p>
                    <p class="mt-2 font-serif text-2xl">{{ $post->title }}</p>
                    <p class="mt-2 line-clamp-3 text-sm text-muted">{{ $post->excerpt }}</p>
                </div>
            </a>
        @endforeach
    </div>
    <div class="mt-8">{{ $posts->links() }}</div>
</div>
@endsection
