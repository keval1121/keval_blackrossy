@extends('layouts.admin')
@section('title', isset($product) ? 'Edit product' : 'Add product')
@section('content')
<form method="post" enctype="multipart/form-data" action="{{ isset($product) ? route('admin.products.update', $product) : route('admin.products.store') }}" class="grid gap-6 lg:grid-cols-3">
    @csrf
    @isset($product) @method('PUT') @endisset
    <div class="space-y-4 rounded-2xl bg-white p-5 lg:col-span-2">
        <input class="admin-input" name="name" value="{{ old('name', $product->name ?? '') }}" placeholder="Product name" required>
        <input class="admin-input" name="slug" value="{{ old('slug', $product->slug ?? '') }}" placeholder="Slug (optional)">
        <input class="admin-input" name="sku" value="{{ old('sku', $product->sku ?? '') }}" placeholder="SKU">
        <textarea class="admin-input" name="short_description" placeholder="Short description">{{ old('short_description', $product->short_description ?? '') }}</textarea>
        <textarea class="admin-input min-h-32" name="description" placeholder="Full description">{{ old('description', $product->description ?? '') }}</textarea>
        <textarea class="admin-input min-h-24" name="specifications" placeholder="Specifications (Label: Value per line)">{{ old('specifications', isset($product) ? collect($product->specifications)->map(fn($s)=>$s['label'].': '.$s['value'])->implode("\n") : '') }}</textarea>
        <input type="file" name="images[]" multiple accept="image/*" class="admin-input">
        @isset($product)
            <div class="flex flex-wrap gap-3">
                @foreach($product->images as $image)
                    <label class="block w-24 text-xs">
                        <img src="{{ $image->url('thumb') }}" class="h-24 w-24 rounded-xl object-cover">
                        <input type="radio" name="primary_image_id" value="{{ $image->id }}" @checked($image->is_primary)> Primary
                        <input type="checkbox" name="delete_images[]" value="{{ $image->id }}"> Delete
                    </label>
                @endforeach
            </div>
        @endisset
        <p class="text-sm font-medium">Variants (example: Size:M, Color:Black)</p>
        @foreach(($product->variants ?? collect()) as $i => $variant)
            <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
                <input type="hidden" name="variants[{{ $i }}][id]" value="{{ $variant->id }}">
                <input class="admin-input col-span-2" name="variants[{{ $i }}][label]" value="{{ $variant->values->map(fn($v)=>$v->attribute->name.': '.$v->attributeValue->value)->implode(', ') }}">
                <input class="admin-input" name="variants[{{ $i }}][sku]" value="{{ $variant->sku }}" placeholder="SKU">
                <input class="admin-input" name="variants[{{ $i }}][stock]" value="{{ $variant->stock }}" placeholder="Stock">
            </div>
        @endforeach
        <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
            <input class="admin-input col-span-2" name="variants[new][label]" placeholder="Size:M, Color:Black">
            <input class="admin-input" name="variants[new][sku]" placeholder="SKU">
            <input class="admin-input" name="variants[new][stock]" placeholder="Stock">
        </div>
        <input class="admin-input" name="tags" value="{{ old('tags', isset($product) ? $product->tags->pluck('name')->implode(', ') : '') }}" placeholder="Tags">
    </div>
    <div class="space-y-4 rounded-2xl bg-white p-5">
        <select class="admin-input" name="category_id" required>
            <option value="">Category</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id ?? '')==$category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <select class="admin-input" name="sub_category_id">
            <option value="">Sub-category</option>
            @foreach($categories as $category)
                @foreach($category->children as $child)
                    <option value="{{ $child->id }}" @selected(old('sub_category_id', $product->sub_category_id ?? '')==$child->id)>{{ $category->name }} / {{ $child->name }}</option>
                @endforeach
            @endforeach
        </select>
        <select class="admin-input" name="brand_id">
            <option value="">Brand</option>
            @foreach($brands as $brand)
                <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id ?? '')==$brand->id)>{{ $brand->name }}</option>
            @endforeach
        </select>
        <input class="admin-input" name="mrp" type="number" step="0.01" value="{{ old('mrp', $product->mrp ?? '') }}" placeholder="MRP" required>
        <input class="admin-input" name="selling_price" type="number" step="0.01" value="{{ old('selling_price', $product->selling_price ?? '') }}" placeholder="Selling price" required>
        <input class="admin-input" name="stock_quantity" type="number" value="{{ old('stock_quantity', $product->stock_quantity ?? 0) }}" placeholder="Stock">
        <select class="admin-input" name="status">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="draft">Draft</option>
        </select>
        <label class="block text-sm"><input type="checkbox" name="is_featured" @checked(old('is_featured', $product->is_featured ?? false))> Featured</label>
        <label class="block text-sm"><input type="checkbox" name="is_best_seller" @checked(old('is_best_seller', $product->is_best_seller ?? false))> Best seller</label>
        <label class="block text-sm"><input type="checkbox" name="is_new_arrival" @checked(old('is_new_arrival', $product->is_new_arrival ?? false))> New arrival</label>
        <input class="admin-input" name="seo_title" value="{{ old('seo_title', $product->seo_title ?? '') }}" placeholder="SEO title">
        <textarea class="admin-input" name="seo_description" placeholder="SEO description">{{ old('seo_description', $product->seo_description ?? '') }}</textarea>
        <button class="w-full rounded-xl bg-stone-900 py-3 text-white">Save product</button>
    </div>
</form>
@isset($product)
    <form method="post" action="{{ route('admin.products.destroy', $product) }}" class="mt-4" onsubmit="return confirm('Delete this product?')">
        @csrf @method('DELETE')
        <button class="text-sm text-rose-700">Delete product</button>
    </form>
@endisset
@endsection
