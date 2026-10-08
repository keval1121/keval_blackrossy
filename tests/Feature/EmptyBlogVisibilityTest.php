<?php

namespace Tests\Feature;

use App\Models\Blog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmptyBlogVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_blog_is_left_out_of_sitemap_and_marked_noindex(): void
    {
        Blog::query()->create([
            'title' => 'Draft Kurti Size Guide',
            'slug' => 'draft-kurti-size-guide',
            'content' => 'Draft content.',
            'is_published' => false,
        ]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertDontSee(url('/blog').'</loc>', false);

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, follow">', false);
    }

    public function test_blog_returns_to_sitemap_once_a_post_is_published(): void
    {
        Blog::query()->create([
            'title' => 'Festival Outfit Checklist',
            'slug' => 'festival-outfit-checklist',
            'content' => 'Published content.',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(url('/blog').'</loc>', false);

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertDontSee('noindex', false);
    }
}
