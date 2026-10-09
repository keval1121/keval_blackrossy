<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Banner;
use App\Models\HomepageSection;
use App\Models\Product;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CmsController extends Controller
{
    public function banners()
    {
        return view('admin.banners.index', ['banners' => Banner::query()->orderBy('display_order')->get()]);
    }

    public function bannerSave(Request $request, ImageService $images, ?Banner $banner = null)
    {
        $banner ??= new Banner;
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:180'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'button_text' => ['nullable', 'string', 'max:40'],
            'button_url' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'desktop_image' => [$banner->exists ? 'nullable' : 'required', 'image', 'max:4096'],
            'mobile_image' => ['nullable', 'image', 'max:4096'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        if ($request->hasFile('desktop_image')) {
            $data['desktop_image'] = $images->storeSingle($request->file('desktop_image'), 'banners', 1920);
        }
        if ($request->hasFile('mobile_image')) {
            $data['mobile_image'] = $images->storeSingle($request->file('mobile_image'), 'banners', 900);
        }
        $banner->fill($data)->save();

        return back()->with('status', 'Banner saved.');
    }

    public function bannerDelete(Banner $banner)
    {
        $banner->delete();

        return back()->with('status', 'Banner deleted.');
    }

    public function homepage()
    {
        return view('admin.homepage.index', [
            'sections' => HomepageSection::query()->where('key', '!=', 'blog')->orderBy('display_order')->get(),
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function homepageSave(Request $request, HomepageSection $section)
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('product_ids', [])))));
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('category_ids', [])))));

        if ($section->key === 'hero_products') {
            $productIds = array_slice($productIds, 0, 4);
        }

        $section->update([
            'title' => $request->string('title'),
            'subtitle' => $request->string('subtitle'),
            'button_text' => $request->string('button_text'),
            'button_url' => $request->string('button_url'),
            'is_enabled' => $request->boolean('is_enabled'),
            'display_order' => $request->integer('display_order'),
            'config' => array_filter([
                'product_ids' => $productIds,
                'category_ids' => $categoryIds,
            ]),
        ]);

        return back()->with('status', 'Homepage section updated.');
    }

    public function ads()
    {
        return view('admin.ads.index', ['ads' => Ad::query()->orderBy('position')->get()]);
    }

    public function adSave(Request $request, Ad $ad)
    {
        $code = trim((string) $request->input('code', ''));
        $isActive = $request->boolean('is_active');

        // Incomplete loader-only code must never be activated (AdSense policy / UX safe).
        if ($code !== '' && ! str_contains($code, 'data-ad-slot')) {
            $isActive = false;
        }

        if ($code === '') {
            $isActive = false;
        }

        $ad->update([
            'name' => $request->string('name'),
            'code' => $code !== '' ? $code : null,
            'is_active' => $isActive,
        ]);

        Cache::forget('shop.ads');

        $status = 'Ad placement updated.';
        if ($code !== '' && ! str_contains($code, 'data-ad-slot')) {
            $status = 'Saved as inactive: paste a full ad unit that includes data-ad-slot. The head script is set in Settings → Client ID.';
        }

        return back()->with('status', $status);
    }
}
