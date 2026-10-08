<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Cart;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HiddenCategoryProductsTest extends TestCase
{
    use RefreshDatabase;

    private Product $visibleProduct;

    private Product $offCategoryProduct;

    private Product $offSubCategoryProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $apparel = Category::query()->create(['name' => 'Apparel', 'slug' => 'apparel-visible', 'is_active' => true]);
        $lustre = Category::query()->create(['name' => 'Lustre', 'slug' => 'lustre-off', 'is_active' => false]);
        $stride = Category::query()->create(['name' => 'Stride', 'slug' => 'stride-on', 'is_active' => true]);
        $mensStride = Category::query()->create(['parent_id' => $stride->id, 'name' => 'Mens Stride', 'slug' => 'mens-stride-off', 'is_active' => false]);

        HomepageSection::query()->firstOrCreate(
            ['key' => 'featured_products'],
            ['title' => 'Featured', 'is_enabled' => true, 'display_order' => 2, 'config' => []]
        );

        $this->visibleProduct = $this->product($apparel, null, 'Visible Linen Shirt', 'visible-linen-shirt');
        $this->offCategoryProduct = $this->product($lustre, null, 'Hidden Halo Ring', 'hidden-halo-ring');
        $this->offSubCategoryProduct = $this->product($stride, $mensStride, 'Hidden Canvas Sneaker', 'hidden-canvas-sneaker');
    }

    public function test_products_from_inactive_categories_are_hidden_from_listings(): void
    {
        foreach ([route('home'), route('shop'), route('search', ['q' => 'Hidden'])] as $url) {
            $response = $this->get($url);

            $response->assertOk();
            $response->assertDontSee('Hidden Halo Ring');
            $response->assertDontSee('Hidden Canvas Sneaker');
        }

        $this->get(route('shop'))->assertSee('Visible Linen Shirt');
    }

    public function test_sitemap_omits_products_and_subcategories_that_are_switched_off(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertSee('visible-linen-shirt');
        $response->assertDontSee('hidden-halo-ring');
        $response->assertDontSee('hidden-canvas-sneaker');
        $response->assertDontSee('mens-stride-off');
    }

    public function test_product_page_returns_not_found_when_its_category_is_off(): void
    {
        $this->get(route('product.show', $this->offCategoryProduct))->assertNotFound();
        $this->get(route('product.show', $this->offSubCategoryProduct))->assertNotFound();

        $visible = $this->get(route('product.show', $this->visibleProduct));
        $visible->assertOk();
        $visible->assertDontSee('Recently viewed');
    }

    public function test_hidden_products_cannot_be_added_to_cart_or_checked_out(): void
    {
        $this->postJson(route('cart.add'), ['product_id' => $this->offCategoryProduct->id, 'quantity' => 1])
            ->assertUnprocessable()
            ->assertJson(['message' => 'This product is no longer available.']);

        $cart = Cart::query()->create(['token' => 'hidden-category-cart']);
        $cart->items()->create(['product_id' => $this->offSubCategoryProduct->id, 'quantity' => 1]);

        $errors = app(CartService::class)->validateStock($cart);

        $this->assertSame(['Hidden Canvas Sneaker in your cart is no longer available. Please remove it to continue.'], $errors->all());
    }

    private function product(Category $category, ?Category $subCategory, string $name, string $slug): Product
    {
        return Product::query()->create([
            'category_id' => $category->id,
            'sub_category_id' => $subCategory?->id,
            'name' => $name,
            'slug' => $slug,
            'mrp' => 999,
            'selling_price' => 799,
            'stock_quantity' => 5,
            'status' => ProductStatus::Active,
        ]);
    }
}
