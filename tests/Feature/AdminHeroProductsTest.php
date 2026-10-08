<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Admin;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminHeroProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_select_hero_products_shown_on_homepage(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin-hero@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'name' => 'Apparel',
            'slug' => 'apparel',
            'is_active' => true,
        ]);

        $selected = $this->makeProductWithPhoto($category, 'Hero Selected Ring', 'hero-selected-ring');
        $other = $this->makeProductWithPhoto($category, 'Other Auto Product', 'other-auto-product');

        $section = HomepageSection::query()->firstOrCreate(
            ['key' => 'hero_products'],
            [
                'title' => 'Hero products',
                'is_enabled' => true,
                'display_order' => 0,
                'config' => ['product_ids' => []],
            ]
        );

        $save = $this->actingAs($admin, 'admin')->post(route('admin.homepage.save', $section), [
            'title' => 'Hero products',
            'subtitle' => '',
            'button_text' => '',
            'button_url' => '',
            'display_order' => 0,
            'is_enabled' => 1,
            'product_ids' => [$selected->id, $other->id],
        ]);
        $save->assertRedirect();

        $this->assertSame(
            [$selected->id, $other->id],
            $section->fresh()->config['product_ids'] ?? []
        );

        $home = $this->get(route('home'));
        $home->assertOk();
        $home->assertSee('Hero Selected Ring', false);
        $home->assertSee('Other Auto Product', false);
    }

    public function test_hero_products_save_keeps_only_four_ids(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin-hero-cap@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'name' => 'Carry',
            'slug' => 'carry',
            'is_active' => true,
        ]);

        $ids = [];
        for ($i = 1; $i <= 6; $i++) {
            $ids[] = $this->makeProductWithPhoto($category, "Hero Cap {$i}", "hero-cap-{$i}")->id;
        }

        $section = HomepageSection::query()->firstOrCreate(
            ['key' => 'hero_products'],
            [
                'title' => 'Hero products',
                'is_enabled' => true,
                'display_order' => 0,
                'config' => ['product_ids' => []],
            ]
        );

        $this->actingAs($admin, 'admin')->post(route('admin.homepage.save', $section), [
            'title' => 'Hero products',
            'display_order' => 0,
            'is_enabled' => 1,
            'product_ids' => $ids,
        ])->assertRedirect();

        $this->assertSame(array_slice($ids, 0, 4), $section->fresh()->config['product_ids'] ?? []);
    }

    private function makeProductWithPhoto(Category $category, string $name, string $slug): Product
    {
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => $slug,
            'mrp' => 999,
            'selling_price' => 799,
            'stock_quantity' => 5,
            'status' => ProductStatus::Active,
        ]);

        ProductImage::query()->create([
            'product_id' => $product->id,
            'path_thumb' => "products/{$product->id}/thumb.webp",
            'path_medium' => "products/{$product->id}/medium.webp",
            'path_large' => "products/{$product->id}/large.webp",
            'is_primary' => true,
            'display_order' => 0,
        ]);

        return $product;
    }
}
