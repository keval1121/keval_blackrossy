@extends('layouts.admin')
@section('title', 'Banners')
@section('content')
<form method="post" action="{{ route('admin.banners.save') }}" enctype="multipart/form-data" class="mb-6 grid gap-3 rounded-2xl bg-white p-5 md:grid-cols-2">
    @csrf
    <input class="admin-input" name="title" placeholder="Title">
    <input class="admin-input" name="subtitle" placeholder="Subtitle">
    <input class="admin-input" name="button_text" placeholder="Button text">
    <input class="admin-input" name="button_url" placeholder="Button URL">
    <input type="file" name="desktop_image" class="admin-input" required>
    <input type="file" name="mobile_image" class="admin-input">
    <label class="text-sm"><input type="checkbox" name="is_active" checked> Active</label>
    <button class="rounded-xl bg-stone-900 py-2 text-white">Add banner</button>
</form>
<div class="grid gap-4 md:grid-cols-2">
    @foreach($banners as $banner)
        <article class="rounded-2xl bg-white p-4">
            <img src="{{ $banner->desktopUrl() }}" class="h-32 w-full rounded-xl object-cover">
            <form method="post" action="{{ route('admin.banners.save', $banner) }}" class="mt-3 space-y-2">
                @csrf
                <input class="admin-input" name="title" value="{{ $banner->title }}">
                <input class="admin-input" name="button_url" value="{{ $banner->button_url }}">
                <label class="text-sm"><input type="checkbox" name="is_active" @checked($banner->is_active)> Active</label>
                <button class="rounded-lg bg-stone-900 px-3 py-1 text-sm text-white">Save</button>
            </form>
            <form method="post" action="{{ route('admin.banners.delete', $banner) }}">@csrf @method('DELETE')<button class="mt-2 text-sm text-rose-700">Delete</button></form>
        </article>
    @endforeach
</div>
@endsection
