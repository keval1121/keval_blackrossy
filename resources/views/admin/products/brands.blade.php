@extends('layouts.admin')
@section('title', 'Brands')
@section('content')
@php
    $editing = $editingBrand ?? null;
@endphp

<form method="post" action="{{ route('admin.brands.save') }}" class="mb-4 flex flex-wrap items-center gap-2 rounded-2xl bg-white p-4">
    @csrf
    @if($editing)
        <input type="hidden" name="id" value="{{ $editing->id }}">
    @endif
    <input class="admin-input w-64" name="name" value="{{ old('name', $editing?->name) }}" placeholder="Brand name" required>
    <button class="rounded-xl bg-stone-900 px-4 py-2 text-white">{{ $editing ? 'Update' : 'Add' }}</button>
    @if($editing)
        <a href="{{ route('admin.brands.index') }}" class="text-sm text-stone-500 hover:underline">Cancel</a>
    @endif
</form>

<ul class="rounded-2xl bg-white p-5 text-sm">
    @forelse($brands as $brand)
        <li class="flex items-center justify-between gap-3 border-b border-stone-100 py-3 last:border-b-0">
            <span class="font-medium {{ $editing?->id === $brand->id ? 'text-amber-700' : '' }}">{{ $brand->name }}</span>
            <div class="flex items-center gap-3">
                <a class="font-medium text-amber-700 hover:underline" href="{{ route('admin.brands.index', ['edit' => $brand->id]) }}">Edit</a>
                <form method="post" action="{{ route('admin.brands.delete', $brand) }}" onsubmit="return confirm('Delete this brand? Products keep their data; brand will be cleared.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-rose-700 hover:underline">Delete</button>
                </form>
            </div>
        </li>
    @empty
        <li class="py-2 text-stone-500">No brands yet.</li>
    @endforelse
</ul>
@endsection
