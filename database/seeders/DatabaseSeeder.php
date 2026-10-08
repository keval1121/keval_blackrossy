<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Ad;
use App\Models\Admin;
use App\Models\Attribute;
use App\Models\Banner;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\HomepageSection;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use App\Services\PlaceholderImageService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $images = app(PlaceholderImageService::class);

        Admin::query()->updateOrCreate(['email' => 'admin@blackrossy.com'], [
            'name' => 'Black Rossy Admin',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $settings = [
            'website_name' => 'Black Rossy',
            'contact_number' => '8128161775',
            'whatsapp_number' => '918128161775',
            'contact_email' => 'Support@blackrossy.com',
            'address' => 'Gujarat, India 500068',
            'cod_enabled' => '1',
            'otp_enabled' => '0',
            'coupons_enabled' => '1',
            'reviews_enabled' => '1',
            'adsense_enabled' => '0',
            'adsense_client_id' => 'ca-pub-1983284873156439',
            'ga_measurement_id' => 'G-ZGN19R100B',
            'ads_txt' => '',
            'maintenance_mode' => '0',
            'restore_stock_on_cancel' => '1',
            'seo_title' => 'Black Rossy | Online Fashion Store India',
            'seo_description' => 'Buy fashion clothing online in India at Black Rossy. Kurtis, ethnic wear and more with Cash on Delivery.',
            'currency_symbol' => '₹',
        ];
        foreach ($settings as $key => $value) {
            Setting::put($key, $value, 'store');
        }

        $tree = [
            'Fashion' => ['Men\'s Clothing', 'Women\'s Clothing', 'Kids Clothing'],
            'Jewellery' => ['Rings', 'Earrings', 'Necklaces', 'Bracelets'],
            'Footwear' => ['Men\'s Footwear', 'Women\'s Footwear'],
            'Bags' => ['Handbags', 'Backpacks', 'Wallets'],
            'Beauty Products' => [],
            'Home Products' => [],
            'Gift Items' => [],
        ];
        $cats = [];
        $order = 1;
        foreach ($tree as $parent => $children) {
            $p = Category::query()->create([
                'name' => $parent,
                'slug' => Str::slug($parent),
                'is_active' => true,
                'show_on_homepage' => true,
                'display_order' => $order++,
                'seo_title' => $parent.' | Black Rossy',
                'seo_description' => 'Shop '.$parent.' with Cash on Delivery.',
                'seo_content' => 'Discover curated '.$parent.' pieces, chosen for everyday luxury and easy COD checkout.',
            ]);
            $p->update(['image' => $this->saveImage($images->productWebp($parent, $this->color($parent), 800), 'categories/'.$p->slug.'.webp')]);
            $cats[$parent] = $p;
            foreach ($children as $childName) {
                $c = Category::query()->create([
                    'parent_id' => $p->id,
                    'name' => $childName,
                    'slug' => Str::slug($childName),
                    'is_active' => true,
                    'display_order' => $order++,
                    'seo_title' => $childName.' | Black Rossy',
                    'seo_description' => 'Shop '.$childName.' online with Cash on Delivery.',
                ]);
                $cats[$childName] = $c;
            }
        }

        $brands = collect(['Black Rossy', 'Aurelia', 'Nivaan', 'Casa Luxe'])->mapWithKeys(function ($name) {
            $brand = Brand::query()->create(['name' => $name, 'slug' => Str::slug($name), 'is_active' => true]);

            return [$name => $brand];
        });

        $size = Attribute::query()->create(['name' => 'Size', 'slug' => 'size', 'type' => 'select', 'is_filterable' => true, 'display_order' => 1]);
        $color = Attribute::query()->create(['name' => 'Color', 'slug' => 'color', 'type' => 'color', 'is_filterable' => true, 'display_order' => 2]);
        $material = Attribute::query()->create(['name' => 'Material', 'slug' => 'material', 'type' => 'select', 'is_filterable' => true, 'display_order' => 3]);
        $sizes = collect(['S', 'M', 'L', 'XL', 'XXL'])->mapWithKeys(fn ($v, $i) => [$v => $size->values()->create(['value' => $v, 'slug' => Str::slug($v), 'display_order' => $i])]);
        $ringSizes = collect(['6', '7', '8', '9'])->mapWithKeys(fn ($v, $i) => [$v => $size->values()->create(['value' => $v, 'slug' => 'ring-'.$v, 'display_order' => 10 + $i])]);
        $colors = collect(['Black' => '#111111', 'Ivory' => '#f4efe6', 'Maroon' => '#7f1d1d', 'Gold' => '#c4a574', 'Blue' => '#1e3a5f'])
            ->map(fn ($hex, $name) => $color->values()->create(['value' => $name, 'slug' => Str::slug($name), 'hex_color' => $hex]));
        $materials = collect(['Gold Plated', 'Sterling Silver'])->mapWithKeys(fn ($v, $i) => [$v => $material->values()->create(['value' => $v, 'slug' => Str::slug($v), 'display_order' => $i])]);

        $products = [
            ['Black Designer Kurti', 'Women\'s Clothing', 'Fashion', 1499, 899, true, true, false, 'kurti, black', ['Size' => ['S', 'M', 'L', 'XL'], 'Color' => ['Black', 'Maroon']]],
            ['Ivory Embroidered Kurti', 'Women\'s Clothing', 'Fashion', 1899, 1299, true, false, true, 'kurti, ivory', ['Size' => ['S', 'M', 'L'], 'Color' => ['Ivory']]],
            ['Linen Mens Shirt', 'Men\'s Clothing', 'Fashion', 1999, 1299, false, true, true, 'shirt, linen', ['Size' => ['M', 'L', 'XL'], 'Color' => ['Blue', 'Ivory']]],
            ['Kids Festive Set', 'Kids Clothing', 'Fashion', 1299, 799, false, false, true, 'kids', ['Size' => ['S', 'M'], 'Color' => ['Maroon']]],
            ['Gold Plated Ring', 'Rings', 'Jewellery', 2499, 1499, true, true, false, 'ring, jewellery', ['Size' => ['6', '7', '8', '9'], 'Material' => ['Gold Plated']]],
            ['Pearl Drop Earrings', 'Earrings', 'Jewellery', 1799, 999, true, false, true, 'earrings', []],
            ['Layered Necklace', 'Necklaces', 'Jewellery', 2199, 1399, false, true, false, 'necklace', []],
            ['Delicate Bracelet', 'Bracelets', 'Jewellery', 1299, 799, false, false, true, 'bracelet', []],
            ['Everyday Sneakers', 'Women\'s Footwear', 'Footwear', 2999, 1999, true, true, false, 'sneakers', ['Size' => ['S', 'M', 'L'], 'Color' => ['Black', 'Ivory']]],
            ['Leather Kolhapuri', 'Men\'s Footwear', 'Footwear', 2499, 1699, false, false, true, 'footwear', ['Size' => ['M', 'L', 'XL'], 'Color' => ['Black']]],
            ['Structured Handbag', 'Handbags', 'Bags', 3499, 2299, true, true, false, 'bag', ['Color' => ['Black', 'Maroon']]],
            ['Travel Backpack', 'Backpacks', 'Bags', 1999, 1299, false, false, true, 'backpack', ['Color' => ['Blue', 'Black']]],
            ['Compact Wallet', 'Wallets', 'Bags', 999, 599, false, true, false, 'wallet', []],
            ['Satin Lip Colour', 'Beauty Products', 'Beauty Products', 799, 499, true, false, true, 'beauty', ['Color' => ['Maroon', 'Gold']]],
            ['Botanical Face Cream', 'Beauty Products', 'Beauty Products', 1299, 899, false, false, true, 'skincare', []],
            ['Linen Cushion Cover', 'Home Products', 'Home Products', 899, 549, false, false, true, 'home', ['Color' => ['Ivory', 'Gold']]],
            ['Scented Candle Set', 'Home Products', 'Home Products', 1499, 999, true, true, false, 'home, candle', []],
            ['Festive Gift Box', 'Gift Items', 'Gift Items', 2499, 1799, true, true, true, 'gift', []],
            ['Jewellery Care Kit', 'Gift Items', 'Gift Items', 699, 449, false, false, true, 'gift, jewellery', []],
            ['Silk Scarf', 'Fashion', 'Fashion', 1299, 849, false, false, true, 'scarf', ['Color' => ['Gold', 'Blue']]],
        ];

        foreach ($products as $index => $row) {
            [$name, $sub, $parent, $mrp, $price, $featured, $best, $fresh, $tags, $attrs] = $row;
            $parentCat = $cats[$parent];
            $subCat = $cats[$sub] ?? null;
            $product = Product::query()->create([
                'category_id' => $parentCat->id,
                'sub_category_id' => $subCat && $subCat->id !== $parentCat->id ? $subCat->id : null,
                'brand_id' => $brands->values()[$index % $brands->count()]->id,
                'name' => $name,
                'slug' => Str::slug($name),
                'sku' => 'VL'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'short_description' => 'A Black Rossy exclusive: '.$name.' designed for everyday wear and easy COD checkout.',
                'description' => $name.' is crafted for comfort and polish. Pair it with jewellery from our collection. Free shipping on every order. 7-day easy returns.',
                'specifications' => [
                    ['label' => 'Brand', 'value' => 'Black Rossy'],
                    ['label' => 'Care', 'value' => 'Keep away from moisture and perfume'],
                    ['label' => 'Origin', 'value' => 'India'],
                ],
                'shipping_info' => 'Dispatched in 24-48 hours. Delivered in 2-5 days.',
                'return_info' => '7-day return on unused products with original tags.',
                'mrp' => $mrp,
                'selling_price' => $price,
                'stock_quantity' => 40,
                'min_order_qty' => 1,
                'max_order_qty' => 5,
                'status' => ProductStatus::Active,
                'is_featured' => $featured,
                'is_best_seller' => $best,
                'is_new_arrival' => $fresh,
                'seo_title' => $name.' | Buy Online | Black Rossy',
                'seo_description' => 'Buy '.$name.' online with Cash on Delivery. Free shipping on eligible orders.',
                'seo_keywords' => $tags,
            ]);
            $product->syncTagsFromString($tags);
            $hex = $this->color($name);
            foreach ([400, 800, 1400] as $sizePx) {
                // generated below per size
            }
            $base = 'products/'.$product->id.'/main';
            Storage::disk('public')->put($base.'_thumb.webp', $images->productWebp($name, $hex, 400));
            Storage::disk('public')->put($base.'_medium.webp', $images->productWebp($name, $hex, 800));
            Storage::disk('public')->put($base.'_large.webp', $images->productWebp($name, $hex, 1400));
            $product->images()->create([
                'path_thumb' => $base.'_thumb.webp',
                'path_medium' => $base.'_medium.webp',
                'path_large' => $base.'_large.webp',
                'alt' => $name,
                'is_primary' => true,
                'display_order' => 0,
            ]);

            if ($attrs) {
                $combos = [[]];
                foreach ($attrs as $attrName => $values) {
                    $next = [];
                    foreach ($combos as $combo) {
                        foreach ($values as $valueName) {
                            $next[] = $combo + [$attrName => $valueName];
                        }
                    }
                    $combos = $next;
                }
                foreach ($combos as $comboIndex => $combo) {
                    $variant = $product->variants()->create([
                        'sku' => $product->sku.'-'.($comboIndex + 1),
                        'stock' => 12,
                        'is_active' => true,
                    ]);
                    foreach ($combo as $attrName => $valueName) {
                        $attribute = Attribute::query()->where('name', $attrName)->first();
                        $value = $attribute->values()->where('value', $valueName)->first();
                        if ($attribute && $value) {
                            $variant->values()->create([
                                'attribute_id' => $attribute->id,
                                'attribute_value_id' => $value->id,
                            ]);
                        }
                    }
                }
                $product->update(['stock_quantity' => $product->variants()->sum('stock')]);
            }
        }

        Storage::disk('public')->put('banners/hero-desktop.webp', $images->bannerWebp('New Season Jewellery', '#1c1917', 1600, 700));
        Storage::disk('public')->put('banners/hero-mobile.webp', $images->bannerWebp('New Season', '#1c1917', 800, 900));
        Banner::query()->create([
            'title' => 'Jewellery that travels with you',
            'subtitle' => 'Gold-plated essentials. Easy Cash on Delivery.',
            'button_text' => 'Shop jewellery',
            'button_url' => url('/jewellery'),
            'desktop_image' => 'banners/hero-desktop.webp',
            'mobile_image' => 'banners/hero-mobile.webp',
            'is_active' => true,
            'display_order' => 1,
        ]);

        foreach ([
            ['hero_products', 'Hero products', 0],
            ['featured_categories', 'Shop by category', 1],
            ['featured_products', 'Featured', 2],
            ['trending', 'Trending now', 3],
            ['bestsellers', 'Best sellers', 4],
            ['new_arrivals', 'New arrivals', 5],
            ['blog', 'From the journal', 6],
        ] as [$key, $title, $display]) {
            HomepageSection::query()->create([
                'key' => $key,
                'title' => $title,
                'is_enabled' => true,
                'display_order' => $display,
            ]);
        }

        foreach (config('shop.ad_positions') as $position => $label) {
            Ad::query()->create([
                'name' => $label,
                'position' => $position,
                'code' => '',
                'is_active' => false,
            ]);
        }

        $blogCat = BlogCategory::query()->create(['name' => 'Style Guides', 'slug' => 'style-guides', 'is_active' => true]);
        $posts = [
            ['How to Choose the Right Jewellery', 'A practical guide to metals, stones and everyday wear.'],
            ['How to Choose Kurti Size', 'Measure once, order with confidence. Includes a simple size chart.'],
            ['Best Jewellery Gifts', 'Thoughtful pieces for festivals, weddings and birthdays.'],
            ['Jewellery Care Guide', 'Keep gold-plated jewellery bright for longer.'],
        ];
        foreach ($posts as $i => [$title, $excerpt]) {
            $path = 'blog/'.Str::slug($title).'.webp';
            Storage::disk('public')->put($path, $images->bannerWebp($title, '#3f3a34', 1200, 700));
            $post = Blog::query()->create([
                'blog_category_id' => $blogCat->id,
                'title' => $title,
                'slug' => Str::slug($title),
                'excerpt' => $excerpt,
                'content' => $excerpt."\n\nBlack Rossy pieces are designed to be worn often. Choose a size that sits comfortably, keep jewellery away from perfume, and pair kurtis with gold-toned accessories from our jewellery collection.\n\nExplore Women's Clothing and Jewellery on Black Rossy with Cash on Delivery.",
                'featured_image' => $path,
                'seo_title' => $title.' | Black Rossy Journal',
                'seo_description' => $excerpt,
                'is_published' => true,
                'published_at' => now()->subDays($i + 1),
            ]);
            $post->syncTagsFromString('fashion, jewellery');
        }

        $legal = [
            'about' => ['About Us', 'Black Rossy is a mobile-first boutique for clothing, jewellery, bags and gifts. We keep checkout simple: no account, Cash on Delivery, and honest product pages.'],
            'privacy-policy' => ['Privacy Policy', 'We collect only the information needed to deliver your order: name, mobile number and address. We do not sell personal data.'],
            'terms' => ['Terms & Conditions', 'By placing an order you agree to pay the Cash on Delivery amount to the delivery partner and to our return windows.'],
            'shipping-policy' => ['Shipping Policy', 'Orders are usually dispatched within 24-48 hours. Shipping is free on every order.'],
            'return-policy' => ['Return Policy', 'Unused products can be returned within 7 days with original tags. Jewellery returns must include all packaging.'],
            'refund-policy' => ['Refund Policy', 'Approved COD returns are refunded after quality check, typically within 5-7 business days of pickup.'],
            'cancellation-policy' => ['Cancellation Policy', 'You may request cancellation before the order is packed. Packed and shipped orders follow the return policy.'],
        ];
        foreach ($legal as $slug => [$title, $content]) {
            Page::query()->create([
                'title' => $title,
                'slug' => $slug,
                'content' => $content,
                'seo_title' => $title.' | Black Rossy',
                'seo_description' => Str::limit($content, 150),
                'is_active' => true,
            ]);
        }

        Coupon::query()->create([
            'code' => 'WELCOME10',
            'type' => 'percentage',
            'value' => 10,
            'min_order' => 799,
            'max_discount' => 200,
            'is_active' => true,
            'usage_limit' => 1000,
        ]);
    }

    private function saveImage(string $binary, string $path): string
    {
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    private function color(string $seed): string
    {
        $palette = ['#3f3a34', '#6b4f3a', '#1e3a5f', '#7f1d1d', '#3f4f3a', '#5c4a6b'];

        return $palette[abs(crc32($seed)) % count($palette)];
    }
}
