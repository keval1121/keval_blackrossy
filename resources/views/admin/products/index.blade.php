@extends('layouts.admin')
@section('title', 'Products')
@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <form class="flex flex-wrap gap-2" method="get">
        <input name="q" value="{{ request('q') }}" class="admin-input w-48" placeholder="Search name or SKU">
        <select name="category_id" class="admin-input w-44">
            <option value="">All categories</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(request('category_id')==$category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <select name="status" class="admin-input w-36">
            <option value="">Status</option>
            <option value="active" @selected(request('status')==='active')>Active</option>
            <option value="inactive" @selected(request('status')==='inactive')>Inactive</option>
            <option value="draft" @selected(request('status')==='draft')>Draft</option>
        </select>
        <button class="admin-btn admin-btn-primary">Filter</button>
    </form>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.products.export') }}" class="admin-btn admin-btn-ghost">Export</a>
        <a href="{{ route('admin.products.create') }}" class="admin-btn admin-btn-accent">Add product</a>
    </div>
</div>

<form method="post" action="{{ route('admin.products.import') }}" enctype="multipart/form-data" class="admin-card mb-5 flex flex-wrap items-center gap-2">
    @csrf
    <input type="file" name="file" accept=".csv,text/csv" class="admin-input max-w-md">
    <button class="admin-btn admin-btn-primary">Import CSV</button>
</form>

<form method="post" action="{{ route('admin.products.bulk') }}">
    @csrf
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th class="w-10"></th>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($products as $product)
                <tr>
                    <td><input type="checkbox" name="ids[]" value="{{ $product->id }}"></td>
                    <td>
                        <div class="flex items-center gap-3">
                            @if($product->primaryImage)
                                <img src="{{ $product->primaryImage->url('thumb') }}" alt="" class="h-10 w-10 rounded-lg object-cover">
                            @else
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-stone-100 text-[10px] text-stone-400">No img</div>
                            @endif
                            <span class="font-medium text-stone-900">{{ $product->name }}</span>
                        </div>
                    </td>
                    <td class="text-stone-500">{{ $product->sku }}</td>
                    <td>{{ money($product->selling_price) }}</td>
                    <td>{{ $product->stock_quantity }}</td>
                    <td>
                        <span class="rounded-full bg-stone-100 px-2.5 py-1 text-xs text-stone-600">{{ $product->status->label() }}</span>
                    </td>
                    <td class="whitespace-nowrap">
                        <a class="font-medium text-amber-700 hover:underline" href="{{ route('admin.products.edit', $product) }}">Edit</a>
                        <form method="post" action="{{ route('admin.products.destroy', $product) }}" class="ml-3 inline" onsubmit="return confirm('Delete this product permanently?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="font-medium text-rose-700 hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="p-6 text-center text-stone-500">No products found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <button class="mt-3 text-sm text-rose-700 hover:underline">Bulk delete selected</button>
</form>
<div class="mt-4">{{ $products->links() }}</div>
@endsection
