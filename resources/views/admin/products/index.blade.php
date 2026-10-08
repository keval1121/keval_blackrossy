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

{{-- Bulk delete form is empty shell; checkboxes/button use form="..." to avoid nesting stock forms. --}}
<form id="products-bulk-delete" method="post" action="{{ route('admin.products.bulk') }}" class="hidden">
    @csrf
</form>

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
            @php
                $variantPayload = $product->variants->map(fn ($variant) => [
                    'id' => $variant->id,
                    'size' => $variant->label() ?: ('Variant #'.$variant->id),
                    'sku' => $variant->sku,
                    'stock' => (int) $variant->stock,
                ])->values();
            @endphp
            <tr>
                <td><input type="checkbox" form="products-bulk-delete" name="ids[]" value="{{ $product->id }}"></td>
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
                <td>
                    @if($product->variants->isNotEmpty())
                        <button
                            type="button"
                            class="stock-chip js-open-stock-modal"
                            data-action="{{ route('admin.products.stock', $product) }}"
                            data-name="{{ $product->name }}"
                            data-variants='@json($variantPayload)'
                        >
                            <span>{{ $product->stock_quantity }}</span>
                            <span class="stock-chip-meta">{{ $product->variants->count() }} sizes</span>
                        </button>
                    @else
                        <form method="post" action="{{ route('admin.products.stock', $product) }}" class="stock-inline">
                            @csrf
                            @method('PATCH')
                            <input class="admin-input" type="number" min="0" name="stock_quantity" value="{{ $product->stock_quantity }}" required aria-label="Stock for {{ $product->name }}">
                            <button type="submit" class="admin-btn admin-btn-ghost">Save</button>
                        </form>
                    @endif
                </td>
                <td>
                    <form method="post" action="{{ route('admin.products.status', $product) }}" class="status-switch">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="active" value="0">
                        <label class="status-switch-label" title="{{ $product->status->label() }}">
                            <input
                                type="checkbox"
                                name="active"
                                value="1"
                                class="status-switch-input"
                                @checked($product->status === \App\Enums\ProductStatus::Active)
                                onchange="this.form.submit()"
                            >
                            <span class="status-switch-track" aria-hidden="true"></span>
                            <span class="status-switch-text">{{ $product->status === \App\Enums\ProductStatus::Active ? 'Active' : 'Inactive' }}</span>
                        </label>
                    </form>
                    @if($product->status === \App\Enums\ProductStatus::Draft)
                        <p class="mt-1 text-[10px] text-stone-400">Was draft — switch sets Active/Inactive</p>
                    @elseif($product->status === \App\Enums\ProductStatus::Active && ! $product->isVisible())
                        <p class="mt-1 text-[10px] font-semibold text-amber-700">Hidden on site — category is off</p>
                    @endif
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
<button type="submit" form="products-bulk-delete" class="mt-3 text-sm text-rose-700 hover:underline">Bulk delete selected</button>
<div class="mt-4">{{ $products->links() }}</div>

<dialog id="stock-modal" class="admin-modal">
    <form method="post" id="stock-modal-form">
        @csrf
        @method('PATCH')
        <div class="admin-modal-header">
            <div>
                <h2 class="admin-modal-title">Update stock</h2>
                <p id="stock-modal-subtitle" class="admin-modal-subtitle"></p>
            </div>
            <button type="button" class="admin-btn admin-btn-ghost js-close-stock-modal" aria-label="Close">&times;</button>
        </div>
        <div class="admin-modal-body">
            <table class="stock-modal-table">
                <thead>
                    <tr>
                        <th>Size</th>
                        <th>SKU</th>
                        <th>Stock</th>
                    </tr>
                </thead>
                <tbody id="stock-modal-rows"></tbody>
            </table>
        </div>
        <div class="admin-modal-footer">
            <p class="admin-modal-total">Total stock: <strong id="stock-modal-total">0</strong></p>
            <div class="flex gap-2">
                <button type="button" class="admin-btn admin-btn-ghost js-close-stock-modal">Cancel</button>
                <button type="submit" class="admin-btn admin-btn-primary">Save stock</button>
            </div>
        </div>
    </form>
</dialog>

<script>
(() => {
    const modal = document.getElementById('stock-modal');
    const form = document.getElementById('stock-modal-form');
    const rows = document.getElementById('stock-modal-rows');
    const subtitle = document.getElementById('stock-modal-subtitle');
    const totalEl = document.getElementById('stock-modal-total');
    if (!modal || !form || !rows || !subtitle || !totalEl) return;

    const syncTotal = () => {
        const total = [...rows.querySelectorAll('input[name*="[stock]"]')].reduce((sum, input) => {
            const value = parseInt(input.value || '0', 10);
            return sum + (Number.isFinite(value) ? value : 0);
        }, 0);
        totalEl.textContent = String(total);
    };

    const closeModal = () => modal.close();

    const buildRow = (variant, index) => {
        const tr = document.createElement('tr');

        const sizeTd = document.createElement('td');
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = `variants[${index}][id]`;
        idInput.value = String(variant.id);
        const sizeLabel = document.createElement('span');
        sizeLabel.className = 'stock-modal-size';
        sizeLabel.textContent = variant.size || ('Variant #' + variant.id);
        sizeTd.append(idInput, sizeLabel);

        const skuTd = document.createElement('td');
        const skuLabel = document.createElement('span');
        skuLabel.className = 'stock-modal-sku';
        skuLabel.textContent = variant.sku || '—';
        skuTd.append(skuLabel);

        const stockTd = document.createElement('td');
        const stockInput = document.createElement('input');
        stockInput.className = 'admin-input';
        stockInput.type = 'number';
        stockInput.min = '0';
        stockInput.name = `variants[${index}][stock]`;
        stockInput.value = String(variant.stock ?? 0);
        stockInput.required = true;
        stockTd.append(stockInput);

        tr.append(sizeTd, skuTd, stockTd);
        return tr;
    };

    document.querySelectorAll('.js-open-stock-modal').forEach((button) => {
        button.addEventListener('click', () => {
            const variants = JSON.parse(button.dataset.variants || '[]');
            form.action = button.dataset.action;
            subtitle.textContent = button.dataset.name || '';
            rows.replaceChildren(...variants.map((variant, index) => buildRow(variant, index)));
            syncTotal();
            modal.showModal();
            rows.querySelector('input[type="number"]')?.focus();
        });
    });

    rows.addEventListener('input', syncTotal);
    document.querySelectorAll('.js-close-stock-modal').forEach((button) => {
        button.addEventListener('click', closeModal);
    });
    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });
})();
</script>
@endsection
