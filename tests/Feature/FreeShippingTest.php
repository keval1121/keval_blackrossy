<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreeShippingTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::query()->create(['name' => 'Rossy Carry', 'slug' => 'bags', 'is_active' => true]);

        HomepageSection::query()->firstOrCreate(
            ['key' => 'featured_products'],
            ['title' => 'Featured', 'is_enabled' => true, 'display_order' => 2, 'config' => []]
        );

        $this->product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Slim Card Wallet',
            'slug' => 'slim-card-wallet',
            'mrp' => 499,
            'selling_price' => 299,
            'stock_quantity' => 5,
            'status' => ProductStatus::Active,
        ]);
    }

    public function test_small_orders_ship_free_and_are_saved_without_a_delivery_charge(): void
    {
        $this->postJson(route('cart.add'), ['product_id' => $this->product->id, 'quantity' => 1])->assertOk();

        $this->get(route('checkout.index'))
            ->assertOk()
            ->assertSeeInOrder(['Delivery', 'Free'])
            ->assertSee('₹299');

        $this->post(route('checkout.place'), [
            'name' => 'Priya Sharma',
            'mobile' => '9876543210',
            'address' => 'Flat 1204, Sea Breeze Tower, Linking Road',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400050',
        ])->assertRedirect();

        $order = Order::query()->sole();

        $this->assertSame(0.0, $order->delivery_charge);
        $this->assertSame(299.0, (float) $order->total);
    }

    public function test_storefront_no_longer_mentions_a_free_shipping_threshold(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Free shipping')
            ->assertSee('On every order across India')
            ->assertDontSee('Free above');

        $this->get(route('product.show', $this->product))
            ->assertOk()
            ->assertSee('Free shipping on every order.')
            ->assertDontSee('Free shipping above');

        $this->get(route('faq'))
            ->assertOk()
            ->assertSee('Shipping is free on every order')
            ->assertDontSee('eligible orders above');
    }
}
