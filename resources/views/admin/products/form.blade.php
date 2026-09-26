@extends('layouts.admin')
@section('title', isset($product) ? 'Edit product' : 'Add product')
@section('content')
<form method="post" enctype="multipart/form-data" action="{{ isset($product) ? route('admin.products.update', $product) : route('admin.products.store') }}" class="space-y-6">
    @csrf
    @isset($product) @method('PUT') @endisset

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="admin-card">
                <h2 class="admin-card-title">Basic info</h2>
                <p class="admin-card-help">Name, SKU and descriptions shown on the storefront.</p>
                <div class="mt-4 space-y-3">
                    <div>
                        <label class="admin-label">Product name</label>
                        <input class="admin-input" name="name" value="{{ old('name', $product->name ?? '') }}" required>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="admin-label">Slug (optional)</label>
                            <input class="admin-input" name="slug" value="{{ old('slug', $product->slug ?? '') }}" placeholder="auto from name">
                        </div>
                        <div>
                            <label class="admin-label">SKU</label>
                            <input class="admin-input" name="sku" value="{{ old('sku', $product->sku ?? '') }}">
                        </div>
                    </div>
                    <div>
                        <label class="admin-label">Short description</label>
                        <textarea class="admin-input min-h-20" name="short_description">{{ old('short_description', $product->short_description ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="admin-label">Full description</label>
                        <textarea class="admin-input min-h-40" name="description">{{ old('description', $product->description ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="admin-label">Specifications <span class="font-normal text-stone-400">(Label: Value, one per line)</span></label>
                        <textarea class="admin-input min-h-24" name="specifications" placeholder="Brand: Black Rossy&#10;Care: Gentle wash">{{ old('specifications', isset($product) ? collect($product->specifications)->map(fn ($s) => $s['label'].': '.$s['value'])->implode("\n") : '') }}</textarea>
                    </div>
                    <div>
                        <label class="admin-label">Tags</label>
                        <input class="admin-input" name="tags" value="{{ old('tags', isset($product) ? $product->tags->pluck('name')->implode(', ') : '') }}" placeholder="home, decor, cushion">
                    </div>
                </div>
            </section>

            <section class="admin-card">
                <h2 class="admin-card-title">Images</h2>
                <p class="admin-card-help">JPG / PNG / WebP, max 4MB each, up to 8 files. Use Remove to delete instantly. Set as primary without saving the whole form.</p>
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="admin-label">Add new images</label>
                        <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" class="admin-input">
                        <p class="mt-1 text-xs text-stone-500">New uploads apply when you click Save product.</p>
                    </div>
                    @isset($product)
                        @if($product->images->isNotEmpty())
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                @foreach($product->images as $image)
                                    <div class="overflow-hidden rounded-xl border border-stone-200 bg-stone-50">
                                        <div class="relative">
                                            <img src="{{ $image->url('thumb') }}" alt="" class="aspect-square w-full object-cover">
                                            @if($image->is_primary)
                                                <span class="absolute left-2 top-2 rounded-full bg-amber-500 px-2 py-0.5 text-[10px] font-semibold text-white">Primary</span>
                                            @endif
                                        </div>
                                        <div class="flex flex-col gap-1.5 p-2">
                                            @unless($image->is_primary)
                                                <form method="post" action="{{ route('admin.products.images.primary', [$product, $image]) }}">
                                                    @csrf
                                                    <button class="admin-btn admin-btn-ghost w-full py-1.5 text-xs">Set as primary</button>
                                                </form>
                                            @endunless
                                            <form method="post" action="{{ route('admin.products.images.destroy', [$product, $image]) }}" onsubmit="return confirm('Remove this image?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="admin-btn admin-btn-danger w-full py-1.5 text-xs">Remove</button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="rounded-xl bg-stone-50 px-3 py-2 text-xs text-stone-500">No images yet — choose files above and Save product.</p>
                        @endif
                    @endisset
                </div>
            </section>

            <section class="admin-card">
                <h2 class="admin-card-title">Variants</h2>
                <p class="admin-card-help">Optional. Example: <code class="rounded bg-stone-100 px-1">Size:M, Color:Black</code></p>
                <div class="mt-4 space-y-2">
                    @foreach(($product->variants ?? collect()) as $i => $variant)
                        <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
                            <input type="hidden" name="variants[{{ $i }}][id]" value="{{ $variant->id }}">
                            <input class="admin-input col-span-2" name="variants[{{ $i }}][label]" value="{{ $variant->values->map(fn ($v) => $v->attribute->name.': '.$v->attributeValue->value)->implode(', ') }}">
                            <input class="admin-input" name="variants[{{ $i }}][sku]" value="{{ $variant->sku }}" placeholder="SKU">
                            <input class="admin-input" name="variants[{{ $i }}][stock]" value="{{ $variant->stock }}" placeholder="Stock">
                        </div>
                    @endforeach
                    <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
                        <input class="admin-input col-span-2" name="variants[new][label]" placeholder="Size:M, Color:Black">
                        <input class="admin-input" name="variants[new][sku]" placeholder="SKU">
                        <input class="admin-input" name="variants[new][stock]" placeholder="Stock">
                    </div>
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="admin-card">
                <h2 class="admin-card-title">Organisation</h2>
                <div class="mt-4 space-y-3">
                    <div>
                        <label class="admin-label">Category</label>
                        <select class="admin-input" name="category_id" required>
                            <option value="">Select category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id ?? '') == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="admin-label">Sub-category</label>
                        <select class="admin-input" name="sub_category_id">
                            <option value="">None</option>
                            @foreach($categories as $category)
                                @foreach($category->children as $child)
                                    <option value="{{ $child->id }}" @selected(old('sub_category_id', $product->sub_category_id ?? '') == $child->id)>{{ $category->name }} / {{ $child->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="admin-label">Brand</label>
                        <select class="admin-input" name="brand_id">
                            <option value="">None</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id ?? '') == $brand->id)>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="admin-label">Status</label>
                        <select class="admin-input" name="status">
                            @foreach(['active' => 'Active', 'inactive' => 'Inactive', 'draft' => 'Draft'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $product->status?->value ?? 'active') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>

            <section class="admin-card">
                <h2 class="admin-card-title">Pricing &amp; stock</h2>
                <div class="mt-4 space-y-3">
                    <div>
                        <label class="admin-label">MRP</label>
                        <input class="admin-input" name="mrp" type="number" step="0.01" value="{{ old('mrp', $product->mrp ?? '') }}" required>
                    </div>
                    <div>
                        <label class="admin-label">Selling price</label>
                        <input class="admin-input" name="selling_price" type="number" step="0.01" value="{{ old('selling_price', $product->selling_price ?? '') }}" required>
                    </div>
                    <div>
                        <label class="admin-label">Stock quantity</label>
                        <input class="admin-input" name="stock_quantity" type="number" value="{{ old('stock_quantity', $product->stock_quantity ?? 0) }}">
                    </div>
                </div>
            </section>

            <section class="admin-card">
                <h2 class="admin-card-title">Flags</h2>
                <div class="mt-4 space-y-2 text-sm">
                    <label class="flex items-center gap-2 rounded-xl border border-stone-200 px-3 py-2.5">
                        <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured ?? false))>
                        Featured
                    </label>
                    <label class="flex items-center gap-2 rounded-xl border border-stone-200 px-3 py-2.5">
                        <input type="checkbox" name="is_best_seller" value="1" @checked(old('is_best_seller', $product->is_best_seller ?? false))>
                        Best seller
                    </label>
                    <label class="flex items-center gap-2 rounded-xl border border-stone-200 px-3 py-2.5">
                        <input type="checkbox" name="is_new_arrival" value="1" @checked(old('is_new_arrival', $product->is_new_arrival ?? false))>
                        New arrival
                    </label>
                </div>
            </section>

            <section class="admin-card">
                <h2 class="admin-card-title">SEO</h2>
                <div class="mt-4 space-y-3">
                    <div>
                        <label class="admin-label">SEO title</label>
                        <input class="admin-input" name="seo_title" value="{{ old('seo_title', $product->seo_title ?? '') }}">
                    </div>
                    <div>
                        <label class="admin-label">SEO description</label>
                        <textarea class="admin-input min-h-24" name="seo_description">{{ old('seo_description', $product->seo_description ?? '') }}</textarea>
                    </div>
                </div>
            </section>

            <button type="submit" class="admin-btn admin-btn-primary w-full py-3.5">Save product</button>
        </div>
    </div>
</form>

@isset($product)
    <form method="post" action="{{ route('admin.products.destroy', $product) }}" class="mt-6" onsubmit="return confirm('Delete this product permanently?')">
        @csrf @method('DELETE')
        <button type="submit" class="text-sm text-rose-700 underline-offset-2 hover:underline">Delete product</button>
    </form>
@endisset
@endsection
