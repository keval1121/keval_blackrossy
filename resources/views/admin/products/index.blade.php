@extends('layouts.admin')
@section('title', 'Products')
@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <form class="flex flex-wrap gap-2" method="get">
        <input name="q" value="{{ request('q') }}" class="admin-input w-48" placeholder="Search">
        <select name="category_id" class="admin-input w-44">
            <option value="">All categories</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(request('category_id')==$category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <select name="status" class="admin-input w-36">
            <option value="">Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="draft">Draft</option>
        </select>
        <button class="rounded-xl bg-stone-900 px-4 py-2 text-white">Filter</button>
    </form>
    <div class="flex gap-2">
        <a href="{{ route('admin.products.export') }}" class="rounded-xl border px-4 py-2 text-sm">Export</a>
        <a href="{{ route('admin.products.create') }}" class="rounded-xl bg-amber-600 px-4 py-2 text-sm text-white">Add product</a>
    </div>
</div>
<form method="post" action="{{ route('admin.products.import') }}" enctype="multipart/form-data" class="mb-4 flex gap-2 rounded-2xl bg-white p-3">
    @csrf
    <input type="file" name="file" accept=".csv,text/csv" class="admin-input">
    <button class="rounded-xl bg-stone-900 px-4 text-sm text-white">Import CSV</button>
</form>
<form method="post" action="{{ route('admin.products.bulk') }}">
    @csrf
    <table class="w-full rounded-2xl bg-white text-left text-sm">
        <thead><tr class="text-stone-400"><th class="p-3"></th><th>Product</th><th>SKU</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach($products as $product)
            <tr class="border-t border-stone-100">
                <td class="p-3"><input type="checkbox" name="ids[]" value="{{ $product->id }}"></td>
                <td class="p-3">{{ $product->name }}</td>
                <td>{{ $product->sku }}</td>
                <td>{{ money($product->selling_price) }}</td>
                <td>{{ $product->stock_quantity }}</td>
                <td>{{ $product->status->label() }}</td>
                <td class="p-3"><a class="text-amber-700" href="{{ route('admin.products.edit', $product) }}">Edit</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <button class="mt-3 text-sm text-rose-700">Bulk delete selected</button>
</form>
<div class="mt-4">{{ $products->links() }}</div>
@endsection
