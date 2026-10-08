<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_page_has_no_review_option(): void
    {
        $category = Category::query()->create([
            'name' => 'Apparel',
            'slug' => 'apparel-no-reviews',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'No Review Shirt',
            'slug' => 'no-review-shirt',
            'mrp' => 999,
            'selling_price' => 799,
            'stock_quantity' => 5,
            'status' => ProductStatus::Active,
            'avg_rating' => 4.5,
            'reviews_count' => 3,
        ]);

        $response = $this->get(route('product.show', $product));

        $response->assertOk();
        $response->assertDontSee('Write a review');
        $response->assertDontSee('Submit for approval');
        $response->assertDontSee('aggregateRating', false);
        $response->assertDontSee('/product/no-review-shirt/review', false);

        $this->post('/product/no-review-shirt/review', [
            'name' => 'Asha',
            'rating' => 5,
            'body' => 'Nice',
        ])->assertNotFound();
    }
}
