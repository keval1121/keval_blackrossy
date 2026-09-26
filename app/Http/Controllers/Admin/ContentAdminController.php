<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedMobile;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Brand;
use App\Models\ContactMessage;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Page;
use App\Models\Review;
use App\Models\Setting;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ContentAdminController extends Controller
{
    public function blogs()
    {
        return view('admin.blog.index', [
            'posts' => Blog::query()->with('category')->latest()->paginate(20),
            'categories' => BlogCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function blogSave(Request $request, ImageService $images, ?Blog $blog = null)
    {
        $blog ??= new Blog;
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:180'],
            'blog_category_id' => ['nullable', 'exists:blog_categories,id'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'seo_title' => ['nullable', 'string', 'max:180'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'featured_image' => ['nullable', 'image', 'max:4096'],
        ]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);
        $data['is_published'] = $request->boolean('is_published');
        $data['published_at'] = $data['is_published'] ? ($blog->published_at ?: now()) : $blog->published_at;
        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $images->storeSingle($request->file('featured_image'), 'blog', 1400);
        }
        $blog->fill($data)->save();
        $blog->syncTagsFromString($request->string('tags'));

        return redirect()->route('admin.blog.index')->with('status', 'Article saved.');
    }

    public function blogDelete(Blog $blog)
    {
        $blog->delete();

        return back()->with('status', 'Article deleted.');
    }

    public function pages()
    {
        return view('admin.pages.index', ['pages' => Page::query()->orderBy('title')->get()]);
    }

    public function pageSave(Request $request, Page $page)
    {
        $page->update($request->validate([
            'title' => ['required', 'string', 'max:180'],
            'content' => ['required', 'string'],
            'seo_title' => ['nullable', 'string', 'max:180'],
            'seo_description' => ['nullable', 'string', 'max:320'],
        ]) + ['is_active' => $request->boolean('is_active', true)]);

        return back()->with('status', 'Page updated.');
    }

    public function settings()
    {
        return view('admin.settings.index');
    }

    public function settingsSave(Request $request, ImageService $images)
    {
        $data = $request->validate([
            'website_name' => ['required', 'string', 'max:80'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'contact_email' => ['nullable', 'email'],
            'address' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'url'],
            'instagram' => ['nullable', 'url'],
            'youtube' => ['nullable', 'url'],
            'default_delivery_charge' => ['nullable', 'numeric', 'min:0'],
            'free_shipping_amount' => ['nullable', 'numeric', 'min:0'],
            'seo_title' => ['nullable', 'string', 'max:180'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'adsense_client_id' => ['nullable', 'string', 'max:40'],
            'ads_txt' => ['nullable', 'string', 'max:2000'],
            'ga_measurement_id' => ['nullable', 'string', 'max:20', 'regex:/^G-[A-Za-z0-9]+$/'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', 'image', 'max:1024'],
        ]);

        foreach ([
            'website_name', 'contact_number', 'whatsapp_number', 'contact_email', 'address',
            'facebook', 'instagram', 'youtube', 'default_delivery_charge', 'free_shipping_amount',
            'seo_title', 'seo_description', 'adsense_client_id', 'ads_txt', 'ga_measurement_id',
        ] as $key) {
            Setting::put($key, $data[$key] ?? '', 'store');
        }

        Setting::put('cod_enabled', $request->boolean('cod_enabled'), 'store');
        Setting::put('otp_enabled', $request->boolean('otp_enabled'), 'store');
        Setting::put('coupons_enabled', $request->boolean('coupons_enabled'), 'store');
        Setting::put('reviews_enabled', $request->boolean('reviews_enabled'), 'store');
        Setting::put('adsense_enabled', $request->boolean('adsense_enabled'), 'store');
        Setting::put('maintenance_mode', $request->boolean('maintenance_mode'), 'store');
        Setting::put('restore_stock_on_cancel', $request->boolean('restore_stock_on_cancel'), 'store');

        Cache::forget('shop.ads');

        if ($request->hasFile('logo')) {
            Setting::put('logo', $images->storeSingle($request->file('logo'), 'brand', 400), 'store');
        }
        if ($request->hasFile('favicon')) {
            Setting::put('favicon', $images->storeSingle($request->file('favicon'), 'brand', 64), 'store');
        }

        return back()->with('status', 'Settings saved.');
    }

    public function coupons()
    {
        return view('admin.coupons.index', ['coupons' => Coupon::query()->latest()->get()]);
    }

    public function couponSave(Request $request, ?Coupon $coupon = null)
    {
        $coupon ??= new Coupon;
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30'],
            'type' => ['required', 'in:fixed,percentage'],
            'value' => ['required', 'numeric', 'min:0'],
            'min_order' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
        ]);
        $data['code'] = strtoupper($data['code']);
        $data['is_active'] = $request->boolean('is_active', true);
        $coupon->fill($data)->save();

        return back()->with('status', 'Coupon saved.');
    }

    public function reviews()
    {
        return view('admin.reviews.index', ['reviews' => Review::query()->with('product')->latest()->paginate(20)]);
    }

    public function reviewApprove(Review $review)
    {
        $review->update(['is_approved' => true]);
        $this->refreshProductRating($review);

        return back()->with('status', 'Review approved.');
    }

    public function reviewDelete(Review $review)
    {
        $review->delete();
        $this->refreshProductRating($review);

        return back()->with('status', 'Review removed.');
    }

    public function brands()
    {
        return view('admin.products.brands', ['brands' => Brand::query()->orderBy('name')->get()]);
    }

    public function brandSave(Request $request)
    {
        Brand::query()->updateOrCreate(
            ['id' => $request->integer('id') ?: null],
            ['name' => $request->string('name'), 'slug' => Str::slug($request->string('name')), 'is_active' => true]
        );

        return back()->with('status', 'Brand saved.');
    }

    public function contacts()
    {
        return view('admin.customers.contacts', ['messages' => ContactMessage::query()->latest()->paginate(20)]);
    }

    public function blocklist()
    {
        return view('admin.customers.blocklist', ['numbers' => BlockedMobile::query()->latest()->get()]);
    }

    public function blockSave(Request $request)
    {
        $data = $request->validate(['mobile' => ['required', 'regex:/^[6-9]\d{9}$/'], 'reason' => ['nullable', 'string', 'max:180']]);
        BlockedMobile::query()->firstOrCreate(['mobile' => $data['mobile']], ['reason' => $data['reason'] ?? null]);

        return back()->with('status', 'Mobile blocked.');
    }

    public function reports()
    {
        $from = request('from', now()->subDays(30)->toDateString());
        $to = request('to', now()->toDateString());
        $orders = Order::query()->whereBetween('created_at', [$from, $to.' 23:59:59'])->get();

        return view('admin.reports.index', compact('orders', 'from', 'to'));
    }

    private function refreshProductRating(Review $review): void
    {
        $product = $review->product;
        if (! $product) {
            return;
        }
        $approved = $product->reviews()->where('is_approved', true);
        $product->update([
            'reviews_count' => $approved->count(),
            'avg_rating' => round((float) $approved->avg('rating'), 2),
        ]);
    }
}
