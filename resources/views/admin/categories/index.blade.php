@extends('layouts.admin')
@section('title', 'Categories')
@section('content')
<div class="mb-4"><a href="{{ route('admin.categories.create') }}" class="rounded-xl bg-amber-600 px-4 py-2 text-sm text-white">Add category</a></div>
<table class="w-full rounded-2xl bg-white text-left text-sm">
    <thead><tr class="text-stone-400"><th class="p-3">Name</th><th>Parent</th><th>Order</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @foreach($categories as $category)
        <tr class="border-t border-stone-100">
            <td class="p-3">{{ $category->name }}</td>
            <td>{{ $category->parent?->name ?: '—' }}</td>
            <td>{{ $category->display_order }}</td>
            <td>{{ $category->is_active ? 'Active' : 'Hidden' }}</td>
            <td><a class="text-amber-700" href="{{ route('admin.categories.edit', $category) }}">Edit</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
<div class="mt-4">{{ $categories->links() }}</div>
@endsection
