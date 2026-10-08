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

    public function test_creating_product_with_multiple_variants_at_once(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin-create-variants@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'name' => 'Apparel',
            'slug' => 'apparel',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.products.store'), [
            'name' => 'Cotton Tee',
            'category_id' => $category->id,
            'mrp' => 799,
            'selling_price' => 599,
            'stock_quantity' => 20,
            'status' => 'active',
            'variants' => [
                [
                    'label' => 'Size:S, Color:Black',
                    'sku' => 'TEE-S-BLK',
                    'stock' => 5,
                ],
                [
                    'label' => 'Size:M, Color:Black',
                    'sku' => 'TEE-M-BLK',
                    'stock' => 8,
                ],
                [
                    'label' => 'Size:L, Color:White',
                    'sku' => 'TEE-L-WHT',
                    'stock' => 4,
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('name', 'Cotton Tee')->first();
        $this->assertNotNull($product);
        $this->assertSame(3, $product->variants()->count());
        $this->assertSame(17, $product->stock_quantity);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'sku' => 'TEE-S-BLK',
            'stock' => 5,
        ]);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'sku' => 'TEE-M-BLK',
            'stock' => 8,
        ]);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'sku' => 'TEE-L-WHT',
            'stock' => 4,
        ]);
    }

    public function test_index_can_update_simple_product_stock(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin-stock@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'name' => 'Home',
            'slug' => 'home-stock',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Cushion',
            'slug' => 'cushion-stock',
            'sku' => 'CU-001',
            'mrp' => 499,
            'selling_price' => 399,
            'stock_quantity' => 2,
            'status' => ProductStatus::Active,
        ]);

        $response = $this->actingAs($admin, 'admin')->patch(route('admin.products.stock', $product), [
            'stock_quantity' => 15,
        ]);

        $response->assertRedirect();
        $this->assertSame(15, $product->fresh()->stock_quantity);
    }

    public function test_index_variant_stock_update_sets_product_total(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin-variant-stock@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'name' => 'Wear',
            'slug' => 'wear-stock',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Hoodie',
            'slug' => 'hoodie-stock',
            'sku' => 'HD-001',
            'mrp' => 1999,
            'selling_price' => 1499,
            'stock_quantity' => 0,
            'status' => ProductStatus::Active,
        ]);

        $small = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'HD-S',
            'stock' => 1,
            'is_active' => true,
        ]);
        $large = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'HD-L',
            'stock' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->patch(route('admin.products.stock', $product), [
            'variants' => [
                ['id' => $small->id, 'stock' => 6],
                ['id' => $large->id, 'stock' => 9],
            ],
        ]);

        $response->assertRedirect();
        $this->assertSame(6, $small->fresh()->stock);
        $this->assertSame(9, $large->fresh()->stock);
        $this->assertSame(15, $product->fresh()->stock_quantity);
    }

    public function test_updating_product_can_add_multiple_new_variants_in_one_save(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin-add-variants@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'name' => 'Shoes',
            'slug' => 'shoes',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Runner',
            'slug' => 'runner',
            'sku' => 'RN-001',
            'mrp' => 2999,
            'selling_price' => 2499,
            'stock_quantity' => 12,
            'status' => ProductStatus::Active,
        ]);

        $existing = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'RN-001-8',
            'stock' => 3,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->put(route('admin.products.update', $product), [
            'name' => 'Runner',
            'category_id' => $category->id,
            'mrp' => 2999,
            'selling_price' => 2499,
            'stock_quantity' => 12,
            'status' => 'active',
            'variants' => [
                [
                    'id' => $existing->id,
                    'label' => 'Size:8',
                    'sku' => 'RN-001-8',
                    'stock' => 3,
                ],
                [
                    'label' => 'Size:9',
                    'sku' => 'RN-001-9',
                    'stock' => 4,
                ],
                [
                    'label' => 'Size:10',
                    'sku' => 'RN-001-10',
                    'stock' => 5,
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.products.index'));
        $this->assertSame(3, $product->fresh()->variants()->count());
        $this->assertSame(12, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'sku' => 'RN-001-9',
            'stock' => 4,
        ]);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'sku' => 'RN-001-10',
            'stock' => 5,
        ]);
    }

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

    public function test_index_can_toggle_product_active_inactive(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin-status@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'name' => 'Status Cat',
            'slug' => 'status-cat',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Toggle Tee',
            'slug' => 'toggle-tee',
            'sku' => 'TT-001',
            'mrp' => 500,
            'selling_price' => 400,
            'stock_quantity' => 5,
            'status' => ProductStatus::Active,
        ]);

        $deactivate = $this->actingAs($admin, 'admin')->patch(route('admin.products.status', $product), [
            'active' => 0,
        ]);
        $deactivate->assertRedirect();
        $this->assertSame(ProductStatus::Inactive, $product->fresh()->status);

        $activate = $this->actingAs($admin, 'admin')->patch(route('admin.products.status', $product), [
            'active' => 1,
        ]);
        $activate->assertRedirect();
        $this->assertSame(ProductStatus::Active, $product->fresh()->status);
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
        $response->assertSee(route('admin.products.stock', $product), false);
    }

    public function test_deleting_product_redirects_to_products_index(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin3@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'name' => 'Bags',
            'slug' => 'bags-3',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Compact Wallet',
            'slug' => 'compact-wallet',
            'sku' => 'CW-001',
            'mrp' => 599,
            'selling_price' => 449,
            'stock_quantity' => 2,
            'status' => ProductStatus::Active,
        ]);

        $response = $this->from(route('admin.products.edit', $product))
            ->actingAs($admin, 'admin')
            ->delete(route('admin.products.destroy', $product));

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('status', 'Product deleted.');
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
