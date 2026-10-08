<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductRelatedCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_related_products_list_every_other_product_with_same_category_first(): void
    {
        $apparel = Category::query()->create([
            'name' => 'Apparel',
            'slug' => 'apparel-related',
            'is_active' => true,
        ]);
        $carry = Category::query()->create([
            'name' => 'Carry',
            'slug' => 'carry-related',
            'is_active' => true,
        ]);

        $current = Product::query()->create([
            'category_id' => $apparel->id,
            'name' => 'Current Product',
            'slug' => 'current-product-related',
            'mrp' => 999,
            'selling_price' => 799,
            'stock_quantity' => 5,
            'status' => ProductStatus::Active,
        ]);

        $sameCategory = [];
        for ($i = 1; $i <= 10; $i++) {
            $sameCategory[] = Product::query()->create([
                'category_id' => $apparel->id,
                'name' => "Same Category {$i}",
                'slug' => "same-category-{$i}",
                'mrp' => 800,
                'selling_price' => 600,
                'stock_quantity' => 3,
                'status' => ProductStatus::Active,
            ]);
        }

        for ($i = 1; $i <= 5; $i++) {
            Product::query()->create([
                'category_id' => $carry->id,
                'name' => "Other Category {$i}",
                'slug' => "other-category-{$i}",
                'mrp' => 700,
                'selling_price' => 500,
                'stock_quantity' => 2,
                'status' => ProductStatus::Active,
            ]);
        }

        $response = $this->get(route('product.show', $current));
        $response->assertOk();
        $response->assertSee('id="related"', false);
        $response->assertDontSee('#related', false);

        $related = $response->viewData('related');
        $this->assertFalse($related->contains('id', $current->id));
        $this->assertCount(15, $related);

        $names = $related->pluck('name');
        $this->assertTrue($names->take(10)->every(fn (string $name) => str_starts_with($name, 'Same Category')));
        $this->assertTrue($names->slice(10)->every(fn (string $name) => str_starts_with($name, 'Other Category')));
    }
}
