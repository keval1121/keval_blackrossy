@extends('layouts.admin')
@section('title', isset($category) ? 'Edit category' : 'Add category')
@section('content')
@php
    $isSubcategory = filled(old('parent_id', $category->parent_id ?? null));
@endphp
<form method="post" enctype="multipart/form-data" action="{{ isset($category) ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="max-w-xl space-y-4 admin-card">
    @csrf
    @isset($category) @method('PUT') @endisset

    <input class="admin-input" name="name" value="{{ old('name', $category->name ?? '') }}" placeholder="Name" required>
    <input class="admin-input" name="slug" value="{{ old('slug', $category->slug ?? '') }}" placeholder="Slug">
    <div>
        <label class="admin-label">Parent category</label>
        <select class="admin-input" name="parent_id" id="category-parent">
            <option value="">Top-level category</option>
            @foreach($parents as $parent)
                <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id ?? '')==$parent->id)>{{ $parent->name }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-stone-500">Sub-categories (with a parent) appear in the shop menu and homepage mosaic.</p>
    </div>

    <section class="rounded-xl border border-stone-200 bg-stone-50 p-4" id="category-photo-section">
        <label class="admin-label">
            @if($isSubcategory)
                Line photo (menu &amp; homepage)
            @else
                Category photo
            @endif
        </label>
        <p class="mb-3 text-xs text-stone-500">JPG, PNG or WebP, max 4MB. This image shows in Categories dropdown, homepage tiles, and mobile chips.</p>
        @isset($category)
            @if($category->image)
                <div class="mb-3 overflow-hidden rounded-xl border border-stone-200 bg-white">
                    <img src="{{ $category->imageUrl() }}" alt="" class="aspect-[4/3] w-full max-w-xs object-cover">
                </div>
                <label class="mb-3 flex items-center gap-2 text-sm text-stone-600">
                    <input type="checkbox" name="remove_image" value="1" @checked(old('remove_image'))>
                    Remove current photo
                </label>
            @endif
        @endisset
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="admin-input">
    </section>

    <textarea class="admin-input" name="description" placeholder="Description">{{ old('description', $category->description ?? '') }}</textarea>
    <input class="admin-input" name="seo_title" value="{{ old('seo_title', $category->seo_title ?? '') }}" placeholder="SEO title">
    <textarea class="admin-input" name="seo_description" placeholder="SEO description">{{ old('seo_description', $category->seo_description ?? '') }}</textarea>
    <input class="admin-input" name="seo_keywords" value="{{ old('seo_keywords', $category->seo_keywords ?? '') }}" placeholder="SEO keywords">
    <input class="admin-input" type="number" name="display_order" value="{{ old('display_order', $category->display_order ?? 0) }}">
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" @checked(old('is_active', $category->is_active ?? true))> Active</label>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="show_on_homepage" @checked(old('show_on_homepage', $category->show_on_homepage ?? false))> Show on homepage</label>
    <button class="admin-btn admin-btn-primary w-full py-3">Save category</button>
</form>
@endsection
