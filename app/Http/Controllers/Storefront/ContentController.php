<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Category;
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

    public function about()
    {
        return view('storefront.pages.about', [
            'seoTitle' => 'About Us | Black Rossy',
            'seoDescription' => store_name().' boutique for '.storefront_collection_names().' with guest checkout and Cash on Delivery across India.',
        ]);
    }

    public function policy()
    {
        return view('storefront.pages.policy', [
            'seoTitle' => 'Our Policies | Black Rossy',
            'seoDescription' => 'Black Rossy policies for privacy, shipping, returns and website terms with Cash on Delivery shopping in India.',
        ]);
    }

    public function faq()
    {
        $returnPolicyUrl = e(url('/return-policy'));
        $shippingPolicyUrl = e(url('/shipping-policy'));

        $groups = [
            [
                'title' => 'Ordering & Payment',
                'items' => [
                    [
                        'question' => 'How do I pay for my Black Rossy order?',
                        'answer' => 'Black Rossy uses Cash on Delivery for eligible Indian pin codes. Pay the courier only after your parcel reaches you. No card, UPI or wallet payment is needed at checkout.',
                    ],
                    [
                        'question' => 'Can an order be changed or stopped after placing it?',
                        'answer' => 'Yes, if packing has not started. Message us from Contact or WhatsApp with your order number right away. After dispatch, the return process applies instead of cancellation.',
                    ],
                ],
            ],
            [
                'title' => 'Shipping & Delivery',
                'items' => [
                    [
                        'question' => 'When will my parcel leave and arrive?',
                        'answer' => 'Orders are usually packed in 1–2 working days. Most deliveries reach in 2–5 days based on your pin code and courier route. Shipping is free on every order — no minimum cart value.',
                    ],
                    [
                        'question' => 'Which areas do you deliver to?',
                        'answer' => 'We ship across India wherever our courier partners support COD. If a pin code cannot be served, we will inform you after the order is received. Full details are on our <a href="'.$shippingPolicyUrl.'">Shipping Policy</a> page.',
                    ],
                ],
            ],
            [
                'title' => 'Returns & Exchanges',
                'items' => [
                    [
                        'question' => 'How do returns work at Black Rossy?',
                        'answer' => 'Eligible unused items may be returned within 7 days of delivery when tags and packaging are intact. Jewellery and a few personal items can have extra limits. Read the full rules on our <a href="'.$returnPolicyUrl.'">Return Policy</a> page.',
                    ],
                ],
            ],
        ];

        return view('storefront.pages.faq', [
            'groups' => $groups,
            'seoTitle' => 'FAQ | Black Rossy',
            'seoDescription' => 'Black Rossy FAQ for Cash on Delivery, delivery timelines, pin-code shipping and 7-day returns.',
        ]);
    }

    public function contact()
    {
        return view('storefront.pages.contact', [
            'seoTitle' => 'Contact Us | '.store_name(),
            'seoDescription' => 'Contact '.store_name().' for order help, delivery questions, returns and Cash on Delivery support across India.',
        ]);
    }

    public function sitemap()
    {
        $categories = Category::query()
            ->active()
            ->where(fn ($visible) => $visible->whereNull('parent_id')->orWhereHas('parent', fn ($parent) => $parent->active()))
            ->get();
        $products = Product::query()->active()->select('slug', 'updated_at')->get();
        // Thin journal posts are kept offline for AdSense review; omit from sitemap.
        $posts = collect();
        $hasPublishedPosts = Blog::query()->published()->exists();
        $pages = Page::query()->where('is_active', true)->get();

        return response()
            ->view('storefront.pages.sitemap', compact('categories', 'products', 'posts', 'hasPublishedPosts', 'pages'))
            ->header('Content-Type', 'application/xml');
    }

    public function robots()
    {
        $sitemap = rtrim((string) config('app.url'), '/').'/sitemap.xml';

        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            '',
            'Sitemap: '.$sitemap,
        ];

        return response(implode("\n", $lines)."\n", 200)->header('Content-Type', 'text/plain');
    }

    public function adsTxt()
    {
        $custom = trim((string) setting('ads_txt', ''));
        if ($custom !== '' && ! str_starts_with($custom, '#')) {
            return response($custom."\n", 200)->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        $publisher = adsense_publisher_id();
        $lines = [];
        if ($publisher) {
            // Standard AdSense authorized seller line (certification authority f08c47fec0942fa0).
            $lines[] = 'google.com, '.$publisher.', DIRECT, f08c47fec0942fa0';
        }
        if ($custom !== '') {
            $lines[] = $custom;
        }
        if ($lines === []) {
            $lines[] = '# Black Rossy ads.txt — add adsense_client_id in Admin → Settings';
        }

        return response(implode("\n", $lines)."\n", 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
