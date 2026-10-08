<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CatalogService;

class ProductController extends Controller
{
    public function show(Product $product, CatalogService $catalog)
    {
        $product->load([
            'images',
            'brand',
            'category',
            'subCategory',
            'tags',
            'activeVariants.values.attribute',
            'activeVariants.values.attributeValue',
        ]);

        abort_unless($product->isVisible(), 404);

        $attributes = $product->activeVariants
            ->flatMap->values
            ->groupBy('attribute_id')
            ->map(function ($values) {
                $attribute = $values->first()->attribute;

                return [
                    'attribute' => $attribute,
                    'values' => $values->map->attributeValue->unique('id')->values(),
                ];
            })
            ->values();

        return view('storefront.product.show', [
            'product' => $product,
            'attributes' => $attributes,
            'related' => $catalog->related($product),
            'variantMap' => $product->activeVariants->map(fn ($variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'price' => $variant->sellingPrice(),
                'mrp' => $variant->listPrice(),
                'stock' => $variant->stock,
                'label' => $variant->label(),
                'attrs' => $variant->attributeMap(),
            ])->values(),
        ]);
    }
}
