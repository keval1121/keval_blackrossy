@extends('layouts.admin')
@section('title', isset($category) ? 'Edit category' : 'Add category')
@section('content')
<form method="post" enctype="multipart/form-data" action="{{ isset($category) ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="max-w-xl space-y-4 rounded-2xl bg-white p-6">
    @csrf
    @isset($category) @method('PUT') @endisset
    <input class="admin-input" name="name" value="{{ old('name', $category->name ?? '') }}" placeholder="Name" required>
    <input class="admin-input" name="slug" value="{{ old('slug', $category->slug ?? '') }}" placeholder="Slug">
    <select class="admin-input" name="parent_id">
        <option value="">Top-level category</option>
        @foreach($parents as $parent)
            <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id ?? '')==$parent->id)>{{ $parent->name }}</option>
        @endforeach
    </select>
    <textarea class="admin-input" name="description" placeholder="Description">{{ old('description', $category->description ?? '') }}</textarea>
    <textarea class="admin-input" name="seo_content" placeholder="SEO content">{{ old('seo_content', $category->seo_content ?? '') }}</textarea>
    <input class="admin-input" name="seo_title" value="{{ old('seo_title', $category->seo_title ?? '') }}" placeholder="SEO title">
    <textarea class="admin-input" name="seo_description" placeholder="SEO description">{{ old('seo_description', $category->seo_description ?? '') }}</textarea>
    <input class="admin-input" name="seo_keywords" value="{{ old('seo_keywords', $category->seo_keywords ?? '') }}" placeholder="SEO keywords">
    <input class="admin-input" type="number" name="display_order" value="{{ old('display_order', $category->display_order ?? 0) }}">
    <input type="file" name="image" class="admin-input">
    <label class="block text-sm"><input type="checkbox" name="is_active" @checked(old('is_active', $category->is_active ?? true))> Active</label>
    <label class="block text-sm"><input type="checkbox" name="show_on_homepage" @checked(old('show_on_homepage', $category->show_on_homepage ?? false))> Show on homepage</label>
    <button class="rounded-xl bg-stone-900 px-5 py-3 text-white">Save</button>
</form>
@endsection
