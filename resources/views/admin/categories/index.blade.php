@extends('layouts.admin')
@section('title', 'Categories')
@section('content')
<div class="mb-5">
    <a href="{{ route('admin.categories.create') }}" class="admin-btn admin-btn-accent">Add category</a>
</div>

<div class="overflow-x-auto">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Photo</th>
                <th>Name</th>
                <th>Parent</th>
                <th>Order</th>
                <th>Active</th>
                <th>Homepage</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($categories as $category)
            <tr>
                <td>
                    <a href="{{ route('admin.categories.edit', $category) }}" class="block h-11 w-11 overflow-hidden rounded-lg border border-stone-200 bg-stone-100" title="Edit photo">
                        <img src="{{ $category->imageUrl() }}" alt="" class="h-full w-full object-cover">
                    </a>
                </td>
                <td class="font-medium text-stone-900">{{ $category->name }}</td>
                <td class="text-stone-500">{{ $category->parent?->name ?: '—' }}</td>
                <td>{{ $category->display_order }}</td>
                <td>
                    <form method="post" action="{{ route('admin.categories.active', $category) }}" class="status-switch">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="value" value="0">
                        <label class="status-switch-label" title="{{ $category->is_active ? 'Active' : 'Inactive' }}">
                            <input
                                type="checkbox"
                                name="value"
                                value="1"
                                class="status-switch-input"
                                @checked($category->is_active)
                                onchange="this.form.submit()"
                            >
                            <span class="status-switch-track" aria-hidden="true"></span>
                            <span class="status-switch-text">{{ $category->is_active ? 'Active' : 'Inactive' }}</span>
                        </label>
                    </form>
                </td>
                <td>
                    <form method="post" action="{{ route('admin.categories.homepage', $category) }}" class="status-switch">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="value" value="0">
                        <label class="status-switch-label" title="{{ $category->show_on_homepage ? 'Shown on homepage' : 'Hidden from homepage' }}">
                            <input
                                type="checkbox"
                                name="value"
                                value="1"
                                class="status-switch-input"
                                @checked($category->show_on_homepage)
                                onchange="this.form.submit()"
                            >
                            <span class="status-switch-track" aria-hidden="true"></span>
                            <span class="status-switch-text">{{ $category->show_on_homepage ? 'On' : 'Off' }}</span>
                        </label>
                    </form>
                </td>
                <td class="whitespace-nowrap">
                    <a class="font-medium text-amber-700 hover:underline" href="{{ route('admin.categories.edit', $category) }}">Edit</a>
                    <form method="post" action="{{ route('admin.categories.destroy', $category) }}" class="ml-3 inline" onsubmit="return confirm('Delete this category?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="font-medium text-rose-700 hover:underline">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="p-6 text-center text-stone-500">No categories found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $categories->links() }}</div>
@endsection
