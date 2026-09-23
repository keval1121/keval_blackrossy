<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Blog;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $sections = HomepageSection::keyed();

        $featuredCategoryIds = $sections->get('featured_categories')?->config['category_ids'] ?? [];
        $featuredProductIds = $sections->get('featured_products')?->config['product_ids'] ?? [];
        $trendingIds = $sections->get('trending')?->config['product_ids'] ?? [];

        $featuredCategories = Category::query()
            ->active()
            ->when($featuredCategoryIds, fn ($query) => $query->whereIn('id', $featuredCategoryIds),
                fn ($query) => $query->where('show_on_homepage', true))
            ->orderBy('display_order')
            ->limit(8)
            ->get();

        $productWith = ['primaryImage', 'brand'];

        return view('storefront.home.index', [
            'banners' => Banner::query()->live()->get(),
            'sections' => $sections,
            'featuredCategories' => $featuredCategories,
            'featuredProducts' => $this->products($featuredProductIds, $productWith, 'is_featured'),
            'trendingProducts' => $this->products($trendingIds, $productWith, 'sold_count'),
            'bestSellers' => Product::query()->active()->with($productWith)->where('is_best_seller', true)->latest('sold_count')->limit(8)->get(),
            'newArrivals' => Product::query()->active()->with($productWith)->where('is_new_arrival', true)->latest()->limit(8)->get(),
            'blogs' => Blog::query()->published()->latest('published_at')->limit(3)->get(),
        ]);
    }

    private function products(array $ids, array $with, string $fallback)
    {
        $query = Product::query()->active()->with($with);
        if ($ids) {
            return $query->whereIn('id', $ids)
                ->orderByRaw('FIELD(id,'.implode(',', array_map('intval', $ids)).')')
                ->get();
        }

        if ($fallback === 'sold_count') {
            return $query->orderByDesc('sold_count')->limit(8)->get();
        }

        return $query->where($fallback, true)->latest()->limit(8)->get();
    }
}
