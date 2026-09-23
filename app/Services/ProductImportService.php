<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductImportService
{
    public function import(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            throw new \RuntimeException('Unable to read the import file.');
        }

        $header = $this->normalizeHeader(fgetcsv($handle) ?: []);
        $required = ['product_name', 'category', 'sku', 'mrp', 'selling_price', 'stock'];
        foreach ($required as $column) {
            if (! in_array($column, $header, true)) {
                fclose($handle);
                throw new \RuntimeException('Missing required column: '.$column);
            }
        }

        $created = 0;
        $errors = [];
        $rowNumber = 1;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                if ($this->isEmpty($row)) {
                    continue;
                }
                $data = $this->mapRow($header, $row);
                try {
                    $this->importRow($data);
                    $created++;
                } catch (\Throwable $e) {
                    $errors[] = 'Row '.$rowNumber.': '.$e->getMessage();
                }
            }
            if ($errors) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            throw $e;
        }
        fclose($handle);

        return ['created' => $errors ? 0 : $created, 'errors' => $errors];
    }

    private function importRow(array $data): void
    {
        $name = trim((string) ($data['product_name'] ?? ''));
        if ($name === '') {
            throw new \RuntimeException('Product name is required.');
        }

        $mrp = (float) ($data['mrp'] ?? 0);
        $price = (float) ($data['selling_price'] ?? 0);
        $stock = (int) ($data['stock'] ?? 0);
        if ($mrp <= 0 || $price <= 0) {
            throw new \RuntimeException('MRP and selling price must be greater than zero.');
        }
        if ($price > $mrp) {
            throw new \RuntimeException('Selling price cannot be greater than MRP.');
        }

        $parent = Category::query()->where('name', trim((string) $data['category']))->whereNull('parent_id')->first();
        if (! $parent) {
            throw new \RuntimeException('Category "'.$data['category'].'" was not found.');
        }

        $child = null;
        if (! empty($data['sub_category'])) {
            $child = Category::query()
                ->where('parent_id', $parent->id)
                ->where('name', trim((string) $data['sub_category']))
                ->first();
            if (! $child) {
                throw new \RuntimeException('Sub-category "'.$data['sub_category'].'" was not found.');
            }
        }

        $brand = null;
        if (! empty($data['brand'])) {
            $brand = Brand::query()->firstOrCreate(
                ['slug' => Str::slug($data['brand'])],
                ['name' => trim((string) $data['brand']), 'is_active' => true]
            );
        }

        $sku = trim((string) ($data['sku'] ?? ''));
        if ($sku && Product::query()->where('sku', $sku)->exists()) {
            throw new \RuntimeException('SKU already exists.');
        }

        $status = strtolower(trim((string) ($data['status'] ?? 'active')));
        if (! in_array($status, ['active', 'inactive', 'draft'], true)) {
            $status = 'active';
        }

        $product = Product::query()->create([
            'category_id' => $parent->id,
            'sub_category_id' => $child?->id,
            'brand_id' => $brand?->id,
            'name' => $name,
            'slug' => $this->uniqueSlug($name),
            'sku' => $sku ?: null,
            'description' => $data['description'] ?? null,
            'short_description' => Str::limit(strip_tags((string) ($data['description'] ?? '')), 180),
            'mrp' => $mrp,
            'selling_price' => $price,
            'stock_quantity' => $stock,
            'status' => $status === 'active' ? ProductStatus::Active : ($status === 'draft' ? ProductStatus::Draft : ProductStatus::Inactive),
        ]);

        if (! empty($data['tags'])) {
            $product->syncTagsFromString($data['tags']);
        }
    }

    private function uniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $i = 1;
        while (Product::query()->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$i++;
        }

        return $slug;
    }

    private function normalizeHeader(array $header): array
    {
        return array_map(fn ($column) => Str::snake(trim((string) $column)), $header);
    }

    private function mapRow(array $header, array $row): array
    {
        $data = [];
        foreach ($header as $index => $column) {
            $data[$column] = $row[$index] ?? null;
        }

        return $data;
    }

    private function isEmpty(array $row): bool
    {
        return collect($row)->filter(fn ($value) => trim((string) $value) !== '')->isEmpty();
    }
}
