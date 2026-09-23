@extends('layouts.admin')
@section('title', 'Pages')
@section('content')
<div class="space-y-5">
    @foreach($pages as $page)
        <form method="post" action="{{ route('admin.pages.save', $page) }}" class="rounded-2xl bg-white p-5">
            @csrf
            <input class="admin-input" name="title" value="{{ $page->title }}">
            <textarea class="admin-input mt-3 min-h-40" name="content">{{ $page->content }}</textarea>
            <input class="admin-input mt-3" name="seo_title" value="{{ $page->seo_title }}" placeholder="SEO title">
            <textarea class="admin-input mt-3" name="seo_description" placeholder="SEO description">{{ $page->seo_description }}</textarea>
            <label class="mt-2 block text-sm"><input type="checkbox" name="is_active" @checked($page->is_active)> Active</label>
            <button class="mt-3 rounded-xl bg-stone-900 px-4 py-2 text-white">Save</button>
        </form>
    @endforeach
</div>
@endsection
