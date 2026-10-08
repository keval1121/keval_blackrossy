@extends('layouts.admin')
@section('title', 'Homepage')
@section('content')
<div class="space-y-5">
    @foreach($sections as $section)
        <form method="post" action="{{ route('admin.homepage.save', $section) }}" class="rounded-2xl bg-white p-5">
            @csrf
            <p class="font-semibold">{{ str_replace('_', ' ', $section->key) }}</p>
            @if($section->key === 'hero_products')
                <p class="mt-1 text-sm text-stone-500">Homepage top mosaic — pick up to <strong>4 products with photos</strong>. Hold Ctrl/Cmd to select multiple. Order follows selection list order.</p>
            @endif
            <div class="mt-3 grid gap-3 md:grid-cols-2">
                <input class="admin-input" name="title" value="{{ $section->title }}" placeholder="Title">
                <input class="admin-input" name="subtitle" value="{{ $section->subtitle }}" placeholder="Subtitle">
                <input class="admin-input" name="button_text" value="{{ $section->button_text }}" placeholder="Button">
                <input class="admin-input" name="button_url" value="{{ $section->button_url }}" placeholder="URL">
                <input class="admin-input" type="number" name="display_order" value="{{ $section->display_order }}">
            </div>
            @if(in_array($section->key, ['hero_products','featured_products','trending','bestsellers','new_arrivals']))
                <p class="mt-3 text-sm text-stone-500">{{ $section->key === 'hero_products' ? 'Hero products (max 4)' : 'Featured products' }}</p>
                <select name="product_ids[]" multiple class="admin-input min-h-32" @if($section->key === 'hero_products') size="10" @endif>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected(in_array($product->id, $section->config['product_ids'] ?? []))>{{ $product->name }}</option>
                    @endforeach
                </select>
            @endif
            <label class="mt-3 block text-sm"><input type="checkbox" name="is_enabled" @checked($section->is_enabled)> Enabled</label>
            <button class="mt-3 rounded-xl bg-stone-900 px-4 py-2 text-white">Save</button>
        </form>
    @endforeach
</div>
@endsection
