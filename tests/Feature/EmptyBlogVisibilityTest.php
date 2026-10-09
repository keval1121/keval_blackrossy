<?php

namespace Tests\Feature;

use App\Models\Blog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmptyBlogVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_urls_are_gone_and_left_out_of_indexing(): void
    {
        Blog::query()->create([
            'title' => 'Festival Outfit Checklist',
            'slug' => 'festival-outfit-checklist',
            'content' => 'Published content.',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $index = $this->get('/blog');
        $index->assertGone();
        $index->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $index->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $index->assertDontSee('Festival Outfit Checklist');

        $this->get('/blog/festival-outfit-checklist')->assertGone();

        $sitemap = $this->get('/sitemap.xml');
        $sitemap->assertOk();
        $this->assertDoesNotMatchRegularExpression('#<loc>[^<]*/blog(/[^<]*)?</loc>#', $sitemap->getContent());

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('From the journal')
            ->assertDontSee('Festival Outfit Checklist');
    }
}
