<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ContactRequest;
use App\Models\Blog;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Page;
use App\Models\Product;
use App\Services\CatalogService;
use App\Services\SearchService;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function search(Request $request, CatalogService $catalog, SearchService $search)
    {
        if ($request->ajax() && $request->boolean('suggest')) {
            return response()->json($search->suggest((string) $request->get('q')));
        }

        $data = $catalog->listing($request);

        return view('storefront.search.index', [
            'q' => trim((string) $request->get('q')),
            'products' => $data['products'],
            'filters' => $data['filters'],
        ]);
    }

    public function blog()
    {
        $posts = Blog::query()->published()->with('category')->latest('published_at')->paginate(9);

        return view('storefront.blog.index', compact('posts'));
    }

    public function blogShow(Blog $blog)
    {
        abort_unless($blog->is_published && $blog->published_at?->lte(now()), 404);
        $blog->load(['category', 'tags']);
        $related = Blog::query()->published()->where('id', '!=', $blog->id)->latest('published_at')->limit(3)->get();

        return view('storefront.blog.show', compact('blog', 'related'));
    }

    public function page(Page $page)
    {
        abort_unless($page->is_active, 404);

        return view('storefront.pages.show', compact('page'));
    }

    public function contact()
    {
        return view('storefront.pages.contact');
    }

    public function contactStore(ContactRequest $request)
    {
        ContactMessage::query()->create($request->validated() + [
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', 'Message sent. We will get back to you soon.');
    }

    public function sitemap()
    {
        $categories = Category::query()->active()->get();
        $products = Product::query()->active()->select('slug', 'updated_at')->get();
        $posts = Blog::query()->published()->select('slug', 'updated_at')->get();
        $pages = Page::query()->where('is_active', true)->get();

        return response()
            ->view('storefront.pages.sitemap', compact('categories', 'products', 'posts', 'pages'))
            ->header('Content-Type', 'application/xml');
    }

    public function robots()
    {
        $sitemap = rtrim((string) config('app.url'), '/').'/sitemap.xml';

        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /cart',
            'Disallow: /checkout',
            'Sitemap: '.$sitemap,
        ];

        return response(implode("\n", $lines)."\n", 200)->header('Content-Type', 'text/plain');
    }

    public function adsTxt()
    {
        // Replace the placeholder line with your AdSense publisher ads.txt entry after approval.
        $lines = [
            '# Black Rossy ads.txt — paste your Google AdSense line from AdSense > Sites after approval',
            '# Example format: google.com, pub-XXXXXXXXXXXXXXXX, DIRECT, f08c47fec0942fa0',
            setting('ads_txt', '# Pending AdSense publisher ID'),
        ];

        return response(implode("\n", $lines), 200)->header('Content-Type', 'text/plain');
    }
}
