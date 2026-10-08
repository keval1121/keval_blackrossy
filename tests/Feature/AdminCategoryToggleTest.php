<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCategoryToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_can_toggle_active_and_homepage_switches(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin-category@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'name' => 'Decor',
            'slug' => 'decor',
            'is_active' => true,
            'show_on_homepage' => false,
            'display_order' => 1,
        ]);

        $deactivate = $this->actingAs($admin, 'admin')->patch(route('admin.categories.active', $category), [
            'value' => 0,
        ]);
        $deactivate->assertRedirect();
        $this->assertFalse($category->fresh()->is_active);

        $showHome = $this->actingAs($admin, 'admin')->patch(route('admin.categories.homepage', $category), [
            'value' => 1,
        ]);
        $showHome->assertRedirect();
        $this->assertTrue($category->fresh()->show_on_homepage);

        $index = $this->actingAs($admin, 'admin')->get(route('admin.categories.index'));
        $index->assertOk();
        $index->assertSee(route('admin.categories.active', $category), false);
        $index->assertSee(route('admin.categories.homepage', $category), false);
    }

    public function test_subcategory_can_upload_line_photo(): void
    {
        Storage::fake('public');

        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'admin-cat-img@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);

        $parent = Category::query()->create([
            'name' => 'Apparel',
            'slug' => 'apparel',
            'is_active' => true,
        ]);

        $sub = Category::query()->create([
            'parent_id' => $parent->id,
            'name' => 'Mens Line',
            'slug' => 'mens-line',
            'is_active' => true,
        ]);

        $file = UploadedFile::fake()->image('mens-line.jpg', 800, 600);

        $response = $this->actingAs($admin, 'admin')->put(route('admin.categories.update', $sub), [
            'name' => 'Mens Line',
            'parent_id' => $parent->id,
            'is_active' => 1,
            'image' => $file,
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $sub->refresh();
        $this->assertNotNull($sub->image);
        Storage::disk('public')->assertExists($sub->image);
    }
}
