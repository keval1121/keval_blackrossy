<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontCollectionCopyTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_copy_follows_category_toggles(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin-copy@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);

        Category::query()->create(['name' => 'Rossy Apparel', 'slug' => 'fashion', 'is_active' => true, 'display_order' => 1]);
        $jewellery = Category::query()->create(['name' => 'Rossy Lustre', 'slug' => 'jewellery', 'is_active' => false, 'display_order' => 2]);
        Category::query()->create(['name' => 'Rossy Carry', 'slug' => 'bags', 'is_active' => true, 'display_order' => 3]);

        $hidden = $this->get(route('about'));
        $hidden->assertOk();
        $hidden->assertSee('A curated boutique for Rossy Apparel and Carry.', false);
        $hidden->assertSee('Search clothing, bags...', false);
        $hidden->assertDontSee('Lustre');
        $hidden->assertDontSee('jewellery,');

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.categories.active', $jewellery), ['value' => 1])
            ->assertRedirect();

        $shown = $this->get(route('about'));
        $shown->assertSee('A curated boutique for Rossy Apparel, Lustre and Carry.', false);
        $shown->assertSee('Search clothing, jewellery, bags...', false);
    }

    public function test_default_seo_title_names_only_active_collections(): void
    {
        Category::query()->create(['name' => 'Rossy Apparel', 'slug' => 'fashion', 'is_active' => true, 'display_order' => 1]);
        Category::query()->create(['name' => 'Rossy Stride', 'slug' => 'footwear', 'is_active' => false, 'display_order' => 2]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('<title>'.store_name().' | Rossy Apparel</title>', false);
        $response->assertSee('Shop clothing at '.store_name(), false);
        $response->assertDontSee('Stride');
        $response->assertDontSee('footwear');
    }
}
