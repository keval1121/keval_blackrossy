<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductVariantUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_product_with_variants_without_price_fields_succeeds(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'name' => 'Bags',
            'slug' => 'bags',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Travel Backpack',
            'slug' => 'travel-backpack',
            'sku' => 'TB-001',
            'mrp' => 1999,
            'selling_price' => 1499,
            'stock_quantity' => 10,
            'status' => ProductStatus::Active,
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'TB-001-M',
            'stock' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->put(route('admin.products.update', $product), [
            'name' => 'Travel Backpack',
            'category_id' => $category->id,
            'mrp' => 1999,
            'selling_price' => 1499,
            'stock_quantity' => 10,
            'status' => 'active',
            'variants' => [
                [
                    'id' => $variant->id,
                    'label' => 'Size:M',
                    'sku' => 'TB-001-M',
                    'stock' => 7,
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'sku' => 'TB-001-M',
            'stock' => 7,
        ]);
    }

    public function test_product_index_includes_delete_action(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin2@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'name' => 'Bags',
            'slug' => 'bags-2',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Day Pack',
            'slug' => 'day-pack',
            'sku' => 'DP-001',
            'mrp' => 999,
            'selling_price' => 799,
            'stock_quantity' => 3,
            'status' => ProductStatus::Active,
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.products.index'));

        $response->assertOk();
        $response->assertSee(route('admin.products.destroy', $product), false);
        $response->assertSee('Delete', false);
    }
}
