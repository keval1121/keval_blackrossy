@extends('layouts.admin')
@section('title', 'Blog')
@section('content')
<form method="post" action="{{ route('admin.blog.save') }}" enctype="multipart/form-data" class="mb-6 space-y-3 rounded-2xl bg-white p-5">
    @csrf
    <input class="admin-input" name="title" placeholder="Title" required>
    <select class="admin-input" name="blog_category_id">
        <option value="">Category</option>
        @foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
    </select>
    <textarea class="admin-input" name="excerpt" placeholder="Excerpt"></textarea>
    <textarea class="admin-input min-h-40" name="content" placeholder="Content" required></textarea>
    <input class="admin-input" name="tags" placeholder="Tags">
    <input type="file" name="featured_image" class="admin-input">
    <label class="text-sm"><input type="checkbox" name="is_published" checked> Publish</label>
    <button class="rounded-xl bg-stone-900 px-4 py-2 text-white">Create article</button>
</form>
<table class="w-full rounded-2xl bg-white text-left text-sm">
    @foreach($posts as $post)
        <tr class="border-t border-stone-100">
            <td class="p-3">{{ $post->title }}</td>
            <td>{{ $post->is_published ? 'Published' : 'Draft' }}</td>
            <td>
                <form method="post" action="{{ route('admin.blog.delete', $post) }}">@csrf @method('DELETE')<button class="text-rose-700">Delete</button></form>
            </td>
        </tr>
    @endforeach
</table>
<div class="mt-4">{{ $posts->links() }}</div>
@endsection
