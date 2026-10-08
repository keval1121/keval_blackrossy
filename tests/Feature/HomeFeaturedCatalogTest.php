<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeFeaturedCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_featured_section_lists_all_products_with_featured_first_and_pagination(): void
    {
        $category = Category::query()->create([
            'name' => 'Apparel',
            'slug' => 'apparel-home-featured',
            'is_active' => true,
        ]);

        HomepageSection::query()->firstOrCreate(
            ['key' => 'featured_products'],
            [
                'title' => 'Featured',
                'is_enabled' => true,
                'display_order' => 2,
                'config' => [],
            ]
        );

        $regular = [];
        for ($i = 1; $i <= 13; $i++) {
            $regular[] = Product::query()->create([
                'category_id' => $category->id,
                'name' => "Regular Product {$i}",
                'slug' => "regular-product-{$i}",
                'mrp' => 500,
                'selling_price' => 400,
                'stock_quantity' => 3,
                'status' => ProductStatus::Active,
                'is_featured' => false,
            ]);
        }

        $featured = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Featured Star Piece',
            'slug' => 'featured-star-piece',
            'mrp' => 900,
            'selling_price' => 700,
            'stock_quantity' => 2,
            'status' => ProductStatus::Active,
            'is_featured' => true,
        ]);

        $pageOne = $this->get(route('home'));
        $pageOne->assertOk();
        $pageOne->assertSee('Featured Star Piece', false);
        $pageOne->assertSee('id="featured"', false);
        $pageOne->assertSee('Regular Product', false);

        $names = collect($pageOne->viewData('featuredProducts')->items())->pluck('name')->all();
        $this->assertSame('Featured Star Piece', $names[0]);
        $this->assertCount(12, $names);
        $this->assertTrue($pageOne->viewData('featuredProducts')->hasPages());

        $pageTwo = $this->get(route('home', ['page' => 2]));
        $pageTwo->assertOk();
        $this->assertCount(2, $pageTwo->viewData('featuredProducts')->items());
        $this->assertTrue(
            collect($pageTwo->viewData('featuredProducts')->items())->contains('id', $regular[11]->id)
            || collect($pageTwo->viewData('featuredProducts')->items())->contains(fn (Product $product) => str_starts_with($product->name, 'Regular Product'))
        );
    }
}
