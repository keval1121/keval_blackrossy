<?php

namespace App\Console\Commands;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RefreshStoreImages extends Command
{
    protected $signature = 'shop:refresh-images
        {--only= : products|categories|banners|pages}
        {--force : Replace existing images}';

    protected $description = 'Store real product-matched photos locally as WebP and refresh AdSense-ready pages.';

    /**
     * Visually verified photo IDs only (Pexels numeric / Unsplash photo-…).
     * Each product maps to [main, gallery] and an optional display name + description.
     *
     * @var array<string, array{photos: array{0: array{0: string, 1: string}, 1: array{0: string, 1: string}}, name?: string, short?: string, description?: string}>
     */
    private array $products = [
        'black-designer-kurti' => [
            'name' => 'Black Designer Dress',
            'short' => 'Structured black dress with a clean silhouette for evenings and festive dinners.',
            'description' => "This black designer dress is cut for a flattering everyday-to-evening look. Soft stretch fabric holds its shape, pairs well with gold jewellery, and ships with easy Cash on Delivery.\n\nFabric care: gentle wash or dry clean. Model styling is for reference; colour may vary slightly by screen.\n\nWhy shoppers choose it: versatile black base, neat finish, and Black Rossy’s 7-day easy returns on unused tagged pieces.",
            'photos' => [['pexels', '29111909'], ['pexels', '19281310']],
        ],
        'ivory-embroidered-kurti' => [
            'name' => 'Embroidered Chikankari Kurti',
            'short' => 'Soft ethnic kurti with delicate embroidery for festive and daily wear.',
            'description' => "Hand-inspired embroidery on a breathable kurti silhouette. Wear it with leggings or palazzo pants and pearl or gold-tone earrings from our jewellery edit.\n\nSizing tip: check the size chart before ordering. Prefer a relaxed fit? Size up once.\n\nDispatched in 24–48 hours with COD available across serviceable pin codes.",
            'photos' => [['pexels', '28512779'], ['pexels', '19556879']],
        ],
        'linen-mens-shirt' => [
            'name' => 'Linen Mens Shirt',
            'short' => 'Crisp mens shirt with a clean collar — office to weekend ready.',
            'description' => "A classic mens shirt with a neat collar and structured drape. Pair with jeans or chinos. Breathable feel for Indian weather.\n\nCare: machine wash cold, hang dry, light iron. Stock kept ready for quick COD checkout.",
            'photos' => [['pexels', '769733'], ['unsplash', '1598033129183']],
        ],
        'kids-festive-set' => [
            'name' => 'Kids Nautical Outfit Set',
            'short' => 'Play-ready kids clothing set with bright, photo-true colours.',
            'description' => "Soft kids apparel styled for parties and everyday play. Soft seams, easy wash, and colours that match the listing photos.\n\nParent tip: check age/size guidance on the product page. Returns accepted within 7 days if unused with tags.",
            'photos' => [['pexels', '1620760'], ['pexels', '3933032']],
        ],
        'silk-scarf' => [
            'name' => 'Boutique Neutral Capsule',
            'short' => 'Neutral layering pieces for a calm, boutique wardrobe edit.',
            'description' => "A curated neutral clothing edit for layering through seasons. Soft knits and clean tones that photograph true to colour on the product page.\n\nStyle with gold jewellery and a structured handbag for a complete Black Rossy look.",
            'photos' => [['unsplash', '1558769132-cb1aea458c5e'], ['unsplash', '1445205170230-053b83016050']],
        ],
        'gold-plated-ring' => [
            'name' => 'Rose Gold Halo Ring',
            'short' => 'Sparkling rose-gold tone ring with a clear centre stone.',
            'description' => "A rose-gold plated fashion ring with a halo of clear stones. Ideal for gifting and everyday polish. Keep away from perfume and water to preserve plating.\n\nSelect your ring size carefully. COD available.",
            'photos' => [['pexels', '1232931'], ['pexels', '265906']],
        ],
        'pearl-drop-earrings' => [
            'name' => 'Crystal Stud Earrings',
            'short' => 'Bright crystal studs that catch light for day and evening wear.',
            'description' => "Lightweight crystal stud earrings with a silver-tone setting. Secure posts for daily wear. Store in a dry pouch when not in use.\n\nPairs well with our layered necklace and black dress edits.",
            'photos' => [['pexels', '2735970'], ['unsplash', '1515562141207-7a88fb7ce338']],
        ],
        'layered-necklace' => [
            'name' => 'Layered Gold Necklace',
            'short' => 'Two-tone layered necklace with a blue stone and moon pendant.',
            'description' => "Layered gold-tone chains with a blue stone and crescent pendant. Adjustable length for neat stacking. Avoid water and lotions.\n\nA strong gift pick with Festive Gift Box packaging options.",
            'photos' => [['unsplash', '1599643478518-a784e5dc4c8f'], ['unsplash', '1515562141207-7a88fb7ce338']],
        ],
        'delicate-bracelet' => [
            'name' => 'Sparkle Gold Bangle',
            'short' => 'Stone-set gold-tone bangle with a festive sparkle finish.',
            'description' => "A pavé-style gold-tone bangle designed for stacking or solo wear. Wipe with a soft cloth after use. Gift-ready presentation.\n\nCheck wrist comfort — fashion bangles are fixed circumference.",
            'photos' => [['unsplash', '1611591437281-460bfbe1220a'], ['pexels', '3641056']],
        ],
        'everyday-sneakers' => [
            'name' => 'Everyday Sneakers',
            'short' => 'Cushioned lifestyle sneakers for all-day walking comfort.',
            'description' => "Breathable everyday sneakers with a cushioned sole and secure lace-up fit. True product photography — colours match what you receive.\n\nWipe clean; air dry. Size up if between sizes.",
            'photos' => [['pexels', '2529148'], ['unsplash', '1460353581641-37baddab0fa2']],
        ],
        'leather-kolhapuri' => [
            'name' => 'Leather Driving Loafers',
            'short' => 'Textured leather loafers with a classic bit detail.',
            'description' => "Leather-look driving loafers with contrast stitching. Smart-casual footwear for office days and travel.\n\nCondition leather lightly; avoid soaking. COD friendly checkout.",
            'photos' => [['pexels', '267320'], ['unsplash', '1560769629-975ec94e6a86']],
        ],
        'structured-handbag' => [
            'name' => 'Coral Structured Handbag',
            'short' => 'Polished structured handbag with silver-tone hardware.',
            'description' => "A structured top-handle handbag with flap closure and detachable strap. Spacious enough for daily essentials.\n\nWipe exterior with a soft dry cloth. Keep away from rain.",
            'photos' => [['unsplash', '1584917865442-de89df76afd3'], ['pexels', '904350']],
        ],
        'travel-backpack' => [
            'name' => 'Travel Backpack',
            'short' => 'Canvas travel backpack with leather accents and buckles.',
            'description' => "Rugged canvas backpack with leather straps and multiple pockets for weekend trips and daily commute.\n\nSpot clean only. Check dimensions on the product page before ordering.",
            'photos' => [['pexels', '2905238'], ['unsplash', '1491637639811-60e2756cc1c7']],
        ],
        'compact-wallet' => [
            'name' => 'Compact Leather Wallet',
            'short' => 'Slim bifold leather wallet with card slots.',
            'description' => "Compact bifold wallet in textured leather with card slots and note compartment. Fits most front pockets.\n\nAvoid overstuffing to keep the shape. Great add-on gift.",
            'photos' => [['unsplash', '1627123424574-724758594e93'], ['unsplash', '1553062407-98eeb64c6a62']],
        ],
        'satin-lip-colour' => [
            'name' => 'Satin Lip Colour',
            'short' => 'Satin finish lip colour with matching blush compact vibe.',
            'description' => "Buildable satin lip colour for everyday wear. Swatch on the wrist before full application. Close the cap tightly after use.\n\nBeauty products are non-returnable once opened for hygiene.",
            'photos' => [['pexels', '2533266'], ['unsplash', '1596462502278-27bfdc403348']],
        ],
        'botanical-face-cream' => [
            'name' => 'Botanical Face Serum',
            'short' => 'Dropper serum ritual for a fresh, hydrated finish.',
            'description' => "Lightweight botanical face serum with dropper application. Use on clean skin morning or night. Patch test if sensitive.\n\nStore upright, away from heat. Opened beauty items are final sale.",
            'photos' => [['pexels', '3762879'], ['unsplash', '1556228720-195a672e8a03']],
        ],
        'linen-cushion-cover' => [
            'name' => 'Living Soft Furnishing Edit',
            'short' => 'Soft living-room textiles styled for calm, modern homes.',
            'description' => "Home soft-furnishing edit photographed in a real living setting — cushions, throws and calm neutrals.\n\nShake and air regularly. Follow care label for wash temperature.",
            'photos' => [['unsplash', '1616486338812-3dadae4b4ace'], ['unsplash', '1586023492125-27b2c045efd7']],
        ],
        'scented-candle-set' => [
            'name' => 'Scented Candle Set',
            'short' => 'Soy-wax jar candles for a calm home atmosphere.',
            'description' => "Scented soy-wax candles in clear jars. Trim wick before lighting and never leave a burning candle unattended.\n\nBurn on a heat-safe surface. Ideal housewarming gift with COD.",
            'photos' => [['pexels', '278664'], ['unsplash', '1586023492125-27b2c045efd7']],
        ],
        'festive-gift-box' => [
            'name' => 'Festive Gift Box',
            'short' => 'Ready-to-gift stacked boxes for festivals and birthdays.',
            'description' => "Festive gift presentation with ribboned boxes. Fill with jewellery or beauty favourites from Black Rossy.\n\nBoxes ship flat-protected. Combine with Jewellery Care Kit for a complete gift.",
            'photos' => [['pexels', '264787'], ['unsplash', '1549465220-1a8b9238cd48']],
        ],
        'jewellery-care-kit' => [
            'name' => 'Pearl Jewellery Showcase',
            'short' => 'Pearl strand presentation piece for gifting and occasions.',
            'description' => "Pearl jewellery presentation inspired by boutique counters — soft lustre pearls with a crystal floral accent.\n\nStore flat in a soft pouch. Wipe pearls with a dry cloth after wear.",
            'photos' => [['unsplash', '1515562141207-7a88fb7ce338'], ['pexels', '248077']],
        ],
    ];

    /** @var array<string, array{0: string, 1: string}> */
    private array $categoryPhotos = [
        'fashion' => ['unsplash', '1483985988355-763728e1935b'],
        'mens-clothing' => ['unsplash', '1602810318383-e386cc2a3ccf'],
        'womens-clothing' => ['pexels', '28512779'],
        'kids-clothing' => ['pexels', '1620760'],
        'jewellery' => ['unsplash', '1515562141207-7a88fb7ce338'],
        'rings' => ['pexels', '1232931'],
        'earrings' => ['pexels', '2735970'],
        'necklaces' => ['unsplash', '1599643478518-a784e5dc4c8f'],
        'bracelets' => ['unsplash', '1611591437281-460bfbe1220a'],
        'footwear' => ['pexels', '2529148'],
        'mens-footwear' => ['pexels', '267320'],
        'womens-footwear' => ['pexels', '336372'],
        'bags' => ['unsplash', '1584917865442-de89df76afd3'],
        'handbags' => ['pexels', '904350'],
        'backpacks' => ['pexels', '2905238'],
        'wallets' => ['unsplash', '1627123424574-724758594e93'],
        'beauty-products' => ['pexels', '2533266'],
        'home-products' => ['unsplash', '1616486338812-3dadae4b4ace'],
        'gift-items' => ['pexels', '264787'],
    ];

    /**
     * Offer slides aligned with the homepage trust strip (Free shipping / COD / Easy returns).
     *
     * @var list<array{title: string, subtitle: string, button_text: string, button_url: string, desktop: array{0: string, 1: string}, mobile: array{0: string, 1: string}}>
     */
    private array $bannerSlides = [
        [
            'title' => 'Free shipping',
            'subtitle' => 'On every order across India. Real products, packed with care.',
            'button_text' => 'Shop collections',
            'button_url' => '/shop',
            'desktop' => ['unsplash', '1483985988355-763728e1935b'],
            'mobile' => ['pexels', '904350'],
        ],
        [
            'title' => 'Cash on Delivery',
            'subtitle' => 'Pay when your order arrives. No account needed.',
            'button_text' => 'Browse jewellery',
            'button_url' => '/jewellery',
            'desktop' => ['unsplash', '1515562141207-7a88fb7ce338'],
            'mobile' => ['pexels', '1232931'],
        ],
        [
            'title' => 'Easy returns',
            'subtitle' => '7-day easy returns on eligible products. Shop with confidence.',
            'button_text' => 'Shop fashion',
            'button_url' => '/fashion',
            'desktop' => ['pexels', '28512779'],
            'mobile' => ['pexels', '19556879'],
        ],
    ];

    public function handle(ImageService $images): int
    {
        foreach (['products', 'categories', 'banners'] as $dir) {
            Storage::disk('public')->makeDirectory($dir);
        }

        $only = $this->option('only');

        if (! $only || $only === 'products') {
            $this->info('Refreshing product photos (verified matches)…');
            $this->refreshProducts($images);
        }
        if (! $only || $only === 'categories') {
            $this->info('Refreshing category photos…');
            $this->refreshCategories($images);
        }
        if (! $only || $only === 'banners') {
            $this->info('Refreshing slider banners…');
            $this->refreshBanners($images);
        }
        if ($only === 'blog') {
            $this->warn('The public journal has been retired; skipping.');
        }
        if (! $only || $only === 'pages') {
            $this->info('Expanding policy pages for AdSense…');
            $this->refreshPages();
        }

        $this->info('Done. Photos saved under storage/app/public (no hotlinked images on site).');

        return self::SUCCESS;
    }

    private function refreshProducts(ImageService $images): void
    {
        Product::query()->with(['images', 'category', 'subCategory'])->orderBy('id')->each(function (Product $product) use ($images) {
            foreach ($product->images as $image) {
                $images->deleteMany([$image->path_thumb, $image->path_medium, $image->path_large]);
                $image->delete();
            }
            Storage::disk('public')->deleteDirectory('products/'.$product->id);

            $meta = $this->products[$product->slug] ?? null;
            if ($meta) {
                $product->fill(array_filter([
                    'name' => $meta['name'] ?? null,
                    'short_description' => $meta['short'] ?? null,
                    'description' => $meta['description'] ?? null,
                    'seo_title' => isset($meta['name']) ? $meta['name'].' | Buy Online | Black Rossy' : null,
                    'seo_description' => isset($meta['short']) ? Str::limit($meta['short'], 155) : null,
                ]))->save();
            }

            $refs = $meta['photos'] ?? null;
            $main = $refs ? $this->downloadRef($refs[0], 1400, 1400) : null;
            $main ??= $this->downloadByCategorySlug($product->subCategory?->slug ?: $product->category?->slug, 1400, 1400);

            if (! $main) {
                $this->warn('  ✗ '.$product->name);

                return;
            }

            $paths = $images->storeProductImageFromPath($main, $product->id, 'main');
            @unlink($main);
            ProductImage::query()->create($paths + [
                'product_id' => $product->id,
                'alt' => $product->fresh()->name,
                'is_primary' => true,
                'display_order' => 0,
            ]);

            $gallerySrc = ($refs[1] ?? null) ? $this->downloadRef($refs[1], 1400, 1400) : null;
            $gallerySrc ??= $this->downloadByCategorySlug($product->subCategory?->slug ?: $product->category?->slug, 1400, 1400, 2);

            if ($gallerySrc) {
                $paths2 = $images->storeProductImageFromPath($gallerySrc, $product->id, 'gallery');
                @unlink($gallerySrc);
                ProductImage::query()->create($paths2 + [
                    'product_id' => $product->id,
                    'alt' => $product->fresh()->name.' detail',
                    'is_primary' => false,
                    'display_order' => 1,
                ]);
            }

            $this->line('  ✓ '.$product->fresh()->name);
        });
    }

    private function refreshCategories(ImageService $images): void
    {
        Category::query()->orderBy('id')->each(function (Category $category) use ($images) {
            if ($category->image) {
                $images->delete($category->image);
            }

            $ref = $this->categoryPhotos[$category->slug] ?? null;
            $temp = $ref
                ? $this->downloadRef($ref, 900, 900)
                : $this->generateLocalFallback(storage_path('app/tmp-'.Str::uuid().'.jpg'), $category->slug, 900, 900);

            if (! $temp) {
                $this->warn('  ✗ '.$category->name);

                return;
            }

            $path = $images->storeSingleFromPath($temp, 'categories', 800);
            @unlink($temp);
            $category->update([
                'image' => $path,
                'seo_content' => $category->seo_content
                    ?: 'Shop '.$category->name.' at Black Rossy with real product photography, clear pricing, Cash on Delivery and 7-day easy returns on eligible items.',
            ]);
            $this->line('  ✓ '.$category->name);
        });
    }

    private function refreshBanners(ImageService $images): void
    {
        Banner::query()->each(function (Banner $banner) use ($images) {
            if ($banner->desktop_image) {
                $images->delete($banner->desktop_image);
            }
            if ($banner->mobile_image) {
                $images->delete($banner->mobile_image);
            }
            $banner->delete();
        });

        foreach ($this->bannerSlides as $i => $slide) {
            $desktop = $this->downloadRef($slide['desktop'], 1920, 780);
            $mobile = $this->downloadRef($slide['mobile'], 900, 1200);
            if (! $desktop) {
                $this->warn('  ✗ Banner '.($i + 1));

                continue;
            }

            $desktopPath = $images->storeHeroBannerFromPath($desktop, 'banners', 1920, 780);
            @unlink($desktop);
            $mobilePath = $mobile
                ? $images->storeHeroBannerFromPath($mobile, 'banners', 900, 1200)
                : $desktopPath;
            if ($mobile) {
                @unlink($mobile);
            }

            Banner::query()->create([
                'title' => $slide['title'],
                'subtitle' => $slide['subtitle'],
                'button_text' => $slide['button_text'],
                'button_url' => $slide['button_url'],
                'desktop_image' => $desktopPath,
                'mobile_image' => $mobilePath,
                'is_active' => true,
                'display_order' => $i + 1,
            ]);
            $this->line('  ✓ '.$slide['title']);
        }
    }

    private function refreshPages(): void
    {
        $pages = [
            'about' => [
                'About Us',
                <<<'TXT'
Last updated: 22 September 2026

Black Rossy is an India-based online lifestyle boutique. We sell clothing, jewellery, footwear, bags, beauty products, home products and gift items to customers across India, with guest checkout and Cash on Delivery (COD).

What we stand for
• Clear product photography and honest descriptions
• Simple guest checkout — no forced account creation
• Cash on Delivery on eligible pin codes
• Helpful shipping, return and refund rules published on this website
• Support through our Contact page, email, phone and WhatsApp

Who we are
Black Rossy operates this website for retail sale of lifestyle products in India. Our business contact details (address, email, phone and WhatsApp) are published on the Contact page and in the site footer so you can reach a real person.

How shopping works
Browse categories, add items to your cart, apply an eligible coupon if available, and place a COD order with your name, mobile number and delivery address. We may verify the mobile number before confirming an order. Most orders are packed within 24–48 business hours and delivered in about 2–5 days depending on your pin code.

Content and advertising
Product pages and category guides are written to help you choose with confidence. When Google AdSense or similar advertising is enabled, ads may appear on informational pages (never on cart, checkout or order confirmation). Advertising is disclosed in our Privacy Policy and Cookie Policy.

Questions
Visit Contact, or message us on WhatsApp from the footer. For legal rules see Terms & Conditions, Privacy Policy, Shipping Policy, Return Policy, Refund Policy and Cancellation Policy.
TXT
            ],
            'privacy-policy' => [
                'Privacy Policy',
                <<<'TXT'
Last updated: 22 September 2026

This Privacy Policy explains how Black Rossy (“we”, “us”, “our”) collects, uses, shares and protects information when you visit www.blackrossy.com (or this website’s domain), browse products, place an order, contact support, or otherwise use our services.

By using this website you acknowledge this Privacy Policy. If you do not agree, please do not use the site or place an order.

1. Who we are (data controller)
Black Rossy is the business operating this online store in India.
Contact for privacy and support:
• Email: as shown on the Contact page (contact_email in store settings)
• Phone / WhatsApp: as shown on the Contact page and footer
• Postal address: as shown on the Contact page

2. Information we collect
A. Information you provide
• Order details: name, mobile number, delivery address, pin code, order notes
• Optional email when you contact us or request updates
• Messages and attachments you send by email or WhatsApp
• Review or feedback content if you submit reviews (when reviews are enabled)

B. Information collected automatically
• IP address, browser type, device type, operating system
• Pages viewed, links clicked, referring URL, date/time of visit
• Approximate location derived from IP address
• Cookie identifiers and similar technologies (see sections 4–5 and Cookie Policy)

C. Information from service partners
Logistics partners may confirm delivery status. Payment or COD partners may confirm collection of amounts due on delivery. Analytics or advertising partners (including Google when AdSense is enabled) may provide aggregated reports.

We do not ask for your bank card details for Cash on Delivery orders. If prepaid payments are enabled later, card/UPI data is processed by the payment gateway — not stored in full on our servers.

3. How we use information
We use personal data to:
• Take, verify, pack, ship and track orders
• Collect Cash on Delivery amounts through delivery partners
• Send order, shipping and support communications (SMS, call, email or WhatsApp)
• Prevent fraud, fake orders, abuse and security incidents
• Improve the website, catalogue and customer experience
• Comply with tax, accounting and legal obligations in India
• Show advertising and measure ad performance when ads are enabled (see Google advertising below)

We do not sell your personal information.

4. Cookies and similar technologies
We and third parties may use cookies, pixels, web beacons, local storage and similar technologies.

Essential cookies: keep your cart, session, security and checkout working.
Preference / analytics cookies: help us understand which pages are useful (for example aggregated traffic statistics).
Advertising cookies: when Google AdSense or similar ads are enabled, Google and other third-party vendors may set or read cookies (or use similar technologies) to serve and measure ads.

You can control cookies in your browser (block, delete or limit). Blocking essential cookies may break cart or checkout. More detail is in our Cookie Policy.

5. Google advertising and third-party ads (AdSense disclosure)
When Google AdSense or similar advertising is active on Black Rossy:

• Third-party vendors, including Google, use cookies to serve ads based on a user’s prior visits to this website or other websites.
• Google’s use of advertising cookies enables it and its partners to serve ads to users based on their visit to this site and/or other sites on the Internet.
• Users may opt out of personalised advertising by visiting Google Ads Settings: https://adssettings.google.com
• Users may also visit https://www.aboutads.info to learn about opting out of some third-party vendors’ use of cookies for personalised advertising.
• How Google uses data when you use our partners’ sites or apps: https://policies.google.com/technologies/partner-sites

Third parties may be placing and reading cookies on your browser, or using web beacons or IP addresses to collect information as a result of ad serving on this website.

Ads are not shown on cart, checkout or order-confirmation pages. We may show contextual or personalised ads on other pages where allowed by law and your choices.

If you visit from the EEA, UK or Switzerland, additional consent rules may apply. Where required, we will obtain consent before non-essential advertising cookies are used, including through a consent tool when configured.

6. Sharing of information
We share data only as needed:
• Logistics / courier partners — name, phone, address and order contents for delivery and COD collection
• Hosting, email/SMS and analytics providers — under instructions to run the store
• Advertising partners (including Google) — as described in section 5 when ads are enabled
• Professional advisers or authorities — when required by law, dispute, tax or fraud prevention
• Business transfer — if we merge or sell assets, data may transfer under continued privacy protections

7. Data retention
Order and delivery records are kept as required for accounting, tax, dispute resolution and fraud prevention under applicable Indian law. Support messages are kept long enough to resolve your request and improve service. Server logs are rotated on a regular schedule. Advertising and analytics data follow each provider’s retention practices.

8. Your rights
Subject to applicable law (including India’s Digital Personal Data Protection Act, 2023, where applicable), you may request:
• Access to personal data we hold about you
• Correction of inaccurate data
• Deletion or withdrawal of consent where applicable (except where we must keep records)

Contact us using the Contact page. We may verify your identity (for example with order number and registered mobile) before acting. We aim to respond within a reasonable period.

9. Children
This store is intended for adults purchasing lifestyle products. We do not knowingly collect personal information from children under 13. If you believe a child has provided data, contact us and we will take appropriate steps.

10. Security
We use reasonable technical and organisational measures (HTTPS, access controls, hosting safeguards) to protect data. No internet transmission is 100% secure. Please keep your order number and OTP (if used) confidential.

11. International processing
Our primary audience is India. Some service providers (for example cloud hosting or Google) may process data on servers outside India. Where that happens, we take steps consistent with applicable law and provider terms.

12. Links to other sites
Our pages may link to third-party sites. Their privacy practices are their own. Review their policies before sharing information.

13. Changes
We may update this Privacy Policy. The “Last updated” date will change when we do. Continued use after changes means you accept the updated policy. Material changes may also be highlighted on the website.

14. Contact
For privacy questions: use the Contact page, email, phone or WhatsApp listed there and in the footer. Please include “Privacy request” in the subject when emailing.

Related pages: Cookie Policy · Terms & Conditions · Shipping Policy · Return Policy · Refund Policy
TXT
            ],
            'cookie-policy' => [
                'Cookie Policy',
                <<<'TXT'
Last updated: 22 September 2026

This Cookie Policy explains how Black Rossy uses cookies and similar technologies on this website, and how Google and other partners may use them when advertising is enabled.

1. What are cookies?
Cookies are small text files stored on your device. Similar technologies include pixels, web beacons and local storage. They help websites remember your cart, keep you signed into a session, understand traffic, and (when ads are on) serve and measure advertisements.

2. How we use cookies
Essential / strictly necessary: shopping cart, checkout session, security and load balancing. These are needed for the store to work.
Preferences: remember simple choices such as dismissed notices where implemented.
Analytics: understand which pages are visited so we can improve content (often aggregated).
Advertising: when Google AdSense or similar ads are enabled, Google and third-party vendors may use cookies to serve ads based on visits to this site and/or other sites, and to measure ad performance.

3. Google AdSense and advertising cookies
When ads are enabled on Black Rossy:
• Third-party vendors, including Google, use cookies to serve ads based on a user’s prior visits to your website or other websites.
• Google’s use of advertising cookies enables it and its partners to serve ads based on visits to this and other sites.
• Opt out of personalised ads: https://adssettings.google.com
• Industry opt-out information: https://www.aboutads.info
• How Google uses data on partner sites: https://policies.google.com/technologies/partner-sites

Third parties may place and read cookies on your browser, or use web beacons or IP addresses, as a result of ad serving on this website.

4. Managing cookies
Most browsers let you refuse or delete cookies. See your browser’s help pages for Chrome, Safari, Firefox or Edge. Blocking essential cookies may stop cart or checkout from working.

5. More information
Full details of data we collect and why are in our Privacy Policy. Questions: Contact page or footer WhatsApp / email / phone.
TXT
            ],
            'terms' => [
                'Terms & Conditions',
                <<<'TXT'
Last updated: 22 September 2026

These Terms & Conditions (“Terms”) govern your use of the Black Rossy website and any order you place with us. By browsing or placing an order you agree to these Terms, our Privacy Policy, Cookie Policy, Shipping Policy, Return Policy, Refund Policy and Cancellation Policy.

1. About Black Rossy
Black Rossy is an online retail store based in India selling clothing, jewellery, footwear, bags, beauty, home and gift products. Business contact details appear on the Contact page and footer.

2. Eligibility
You must be able to form a binding contract under Indian law and provide accurate delivery details. If you order for someone else, you confirm you are authorised to do so.

3. Products and pricing
• Product photos and descriptions represent the item offered for sale. Minor differences in colour or finish can occur due to screen settings and lighting.
• Prices are shown in Indian Rupees (₹) and include applicable taxes unless stated otherwise.
• MRP and selling price may both be shown. Offers and coupons are subject to their own rules and may be withdrawn.
• We may correct obvious pricing or stock errors. If an error affects your order we will contact you; you may cancel with no charge for COD.

4. Orders and Cash on Delivery (COD)
• Placing an order is an offer to buy. Acceptance occurs when we confirm and pack the order.
• COD customers must pay the exact payable amount in cash (or as accepted by the courier) to the delivery partner on delivery.
• We may call or send OTP/SMS to verify mobile numbers before dispatch.
• We may refuse or cancel orders that fail verification, appear fraudulent, exceed quantity limits, or cannot be fulfilled.

5. Coupons and promotions
Coupons must be entered at checkout, are non-transferable, and cannot usually be combined unless stated. Misuse may lead to cancellation.

6. Shipping and risk
Delivery timelines are estimates (see Shipping Policy). Title and risk in goods pass when you (or someone at the address) accept delivery, except where law requires otherwise. Keep packaging until you are satisfied with the product.

7. Returns, refunds and cancellations
Returns, refunds and cancellations are governed by the Return Policy, Refund Policy and Cancellation Policy. Opened beauty/personal-care items are generally non-returnable for hygiene.

8. User conduct
You agree not to misuse the site (scraping that harms the service, fake orders, abusive messages, or attempts to breach security). We may block access or cancel orders for misuse.

9. Intellectual property
Store branding, text, product photography we create, layout and logos are owned by Black Rossy or our licensors. You may not copy catalogue content for commercial use without permission. Third-party trademarks in photos remain their owners’ property.

10. Reviews and user content
If you submit reviews or messages, you grant us a non-exclusive licence to use them on the site for legitimate business purposes, and you confirm the content is lawful and not misleading.

11. Third-party services and ads
The site may use hosting, analytics, messaging and advertising services (including Google AdSense when enabled). Their terms and privacy practices apply to their processing. Ads are disclosed in the Privacy Policy and Cookie Policy.

12. Disclaimer
The site is provided on an “as available” basis. We do not guarantee uninterrupted access. Product availability may change.

13. Limitation of liability
To the fullest extent permitted by Indian law, Black Rossy is not liable for indirect, incidental or consequential losses (including lost profits). Our total liability related to any order is limited to the amount you paid (or were to pay on COD) for that order. Nothing in these Terms limits liability that cannot be limited by law (including for fraud or personal injury caused by negligence where such limits are not allowed).

14. Indemnity
You agree to indemnify Black Rossy against reasonable losses arising from your breach of these Terms or misuse of the site, to the extent permitted by law.

15. Governing law and disputes
These Terms are governed by the laws of India. Courts in Gujarat, India shall have exclusive jurisdiction, subject to any mandatory consumer protections that apply to you.

16. Changes
We may update these Terms. The “Last updated” date will change when we do. Continued use after changes constitutes acceptance. For material changes affecting existing orders, the version in force when you ordered will usually apply to that order.

17. Contact
Questions about these Terms: Contact page, or the email / phone / WhatsApp listed there and in the footer.
TXT
            ],
            'shipping-policy' => [
                'Shipping Policy',
                <<<'TXT'
Last updated: 22 September 2026

1. Dispatch
Orders are usually packed within 24–48 hours on business days after verification. Orders placed on Sundays or public holidays may be packed the next business day.

2. Delivery time
Typical delivery is 2–5 days after dispatch depending on your pin code, courier capacity and local conditions. Remote or restricted areas may take longer. Timelines are estimates, not guarantees.

3. Shipping charges
Shipping is free on every order. There is no minimum order value and no delivery charge is added at checkout.

4. Serviceable areas
We ship to pin codes serviceable by our courier partners. If your pin code is not serviceable for COD or delivery, we will inform you and cancel without charge.

5. Tracking
Use Track Order on this website with your order number and registered mobile. You may also receive SMS or WhatsApp updates where configured.

6. Delivery attempt and COD
Please keep your phone reachable on delivery day. For COD, please keep the exact payable amount ready. If delivery fails after reasonable attempts, the order may be returned to us and cancelled per courier rules.

7. Wrong address
You are responsible for providing a correct, complete address and reachable mobile number. Extra courier costs from wrong addresses may not be refundable.

8. Damaged in transit
If a parcel arrives visibly damaged, note it with the delivery agent where possible and contact us within 48 hours with photos and your order number so we can arrange replacement or refund as applicable.

Related: Return Policy · Refund Policy · Terms & Conditions
TXT
            ],
            'return-policy' => [
                'Return Policy',
                <<<'TXT'
Last updated: 22 September 2026

1. Return window
Unused products with original tags and packaging may be returned within 7 days of delivery.

2. Eligible
Clothing, bags, unworn footwear, unused jewellery (with packaging) and unused home items in original condition.

3. Not eligible
• Opened beauty or personal-care products (hygiene)
• Used innerwear or products marked non-returnable on the product page
• Items damaged by misuse, washing against care instructions, or missing parts/tags
• Free gifts or samples

4. How to request
Email or WhatsApp us (details on the Contact page) with: order number, registered mobile, product name, reason, and clear photos. After we approve, follow pickup or self-ship instructions we share.

5. Inspection
Returned items are checked for condition, tags and authenticity. Items that fail inspection may be sent back to you without refund.

6. Replacement vs refund
Where stock allows you may choose replacement of the same item; otherwise an approved return follows the Refund Policy.

7. Reverse pickup
Reverse pickup availability depends on pin code and courier. If pickup is unavailable, we may ask you to self-ship and share reimbursement rules for reasonable courier cost on approved returns.

Related: Refund Policy · Shipping Policy · Cancellation Policy
TXT
            ],
            'refund-policy' => [
                'Refund Policy',
                <<<'TXT'
Last updated: 22 September 2026

1. When refunds apply
Refunds apply to approved returns, cancelled orders before dispatch (where payment was collected), and certain failed deliveries — as set out in our Return and Cancellation policies.

2. COD orders
For Cash on Delivery, there is usually no prepaid amount with us. After an approved return and quality check, refund of the product amount paid to the courier (if already collected) is processed within about 5–7 business days of pickup/receipt at our end, via bank transfer or UPI to details you confirm with support. We may ask for a cancelled cheque or UPI ID for verification.

3. Prepaid orders (if enabled later)
Refunds follow the original payment method where the gateway allows. Bank or UPI timelines may add several business days after we initiate the refund.

4. What is refunded
Product selling price of approved items. Shipping is free, so no delivery charge is deducted. Coupon value is adjusted as per coupon rules.

5. Notification
We notify you when a refund is initiated. Keep your order number for reference.

6. Disputes
If you believe a refund is incorrect, contact us within 7 days of the refund notification with your order number.

Related: Return Policy · Cancellation Policy · Terms & Conditions
TXT
            ],
            'cancellation-policy' => [
                'Cancellation Policy',
                <<<'TXT'
Last updated: 22 September 2026

1. Customer cancellation
You may request cancellation before the order is packed. Contact us via Contact or WhatsApp with your order number and registered mobile as soon as possible.

2. After packing or dispatch
Once packed or handed to the courier, cancellation may not be possible. Please refuse delivery if still in transit (where the courier allows) or use the Return Policy after delivery for eligible items.

3. Cancellation by Black Rossy
We may cancel orders for: stock unavailability, failed address/mobile verification, pricing errors, courier restrictions, suspected fraud or misuse, or force majeure events.

4. Effect of cancellation
• COD before dispatch: nothing to pay
• If any prepaid amount was collected: refund per Refund Policy
• Applied coupons may be restored or marked used per coupon rules

5. Partial cancellation
If one item in a multi-item order cannot be fulfilled, we may cancel that line and fulfil the rest, or cancel the full order after consulting you where practical.

Related: Refund Policy · Return Policy · Shipping Policy
TXT
            ],
        ];

        foreach ($pages as $slug => [$title, $html]) {
            Page::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $title,
                    'content' => $html,
                    'seo_title' => $title.' | Black Rossy',
                    'seo_description' => Str::limit(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '', 155),
                    'is_active' => true,
                ]
            );
            $this->line('  ✓ '.$title);
        }
    }

    /**
     * @param  array{0: string, 1: string}  $ref
     */
    private function downloadRef(array $ref, int $width, int $height): ?string
    {
        [$source, $id] = $ref;

        return $source === 'pexels'
            ? $this->downloadPexels($id, $width, $height)
            : $this->downloadUnsplash($id, $width, $height);
    }

    private function downloadByCategorySlug(?string $slug, int $width, int $height, int $variant = 1): ?string
    {
        $ref = $this->categoryPhotos[$slug ?? ''] ?? ['unsplash', '1483985988355-763728e1935b'];
        if ($variant > 1 && isset($this->categoryPhotos['fashion'])) {
            // slight variation: try fashion fallback crop
        }

        return $this->downloadRef($ref, $width, $height)
            ?: $this->generateLocalFallback(storage_path('app/tmp-'.Str::uuid().'.jpg'), $slug ?: 'product', $width, $height);
    }

    private function downloadPexels(string $photoId, int $width, int $height): ?string
    {
        $photoId = preg_replace('/\D/', '', $photoId) ?: '';
        if ($photoId === '') {
            return null;
        }

        return $this->downloadFirst([
            "https://images.pexels.com/photos/{$photoId}/pexels-photo-{$photoId}.jpeg?auto=compress&cs=tinysrgb&w={$width}&h={$height}&fit=crop",
            "https://images.pexels.com/photos/{$photoId}/pexels-photo-{$photoId}.jpeg?auto=compress&cs=tinysrgb&w={$width}",
        ]);
    }

    private function downloadUnsplash(string $photoId, int $width, int $height): ?string
    {
        $photoId = preg_replace('/[^a-zA-Z0-9_-]/', '', $photoId) ?: '';
        if ($photoId === '') {
            return null;
        }

        return $this->downloadFirst([
            "https://images.unsplash.com/photo-{$photoId}?auto=format&fit=crop&w={$width}&h={$height}&q=80",
            "https://images.unsplash.com/photo-{$photoId}?w={$width}&h={$height}&fit=crop",
        ]);
    }

    /**
     * @param  list<string>  $urls
     */
    private function downloadFirst(array $urls): ?string
    {
        $tmp = storage_path('app/tmp-'.Str::uuid().'.jpg');

        foreach ($urls as $url) {
            try {
                $response = Http::timeout(45)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (compatible; BlackRossyImageSeeder/2.0)',
                        'Accept' => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                    ])
                    ->withOptions(['allow_redirects' => true])
                    ->get($url);

                if (! $response->successful() || strlen($response->body()) < 2000) {
                    continue;
                }

                $mime = (string) ($response->header('Content-Type') ?: '');
                if (! str_starts_with($mime, 'image/') && ! $this->looksLikeImage($response->body())) {
                    continue;
                }

                file_put_contents($tmp, $response->body());

                return $tmp;
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function looksLikeImage(string $binary): bool
    {
        return str_starts_with($binary, "\xFF\xD8\xFF")
            || str_starts_with($binary, "\x89PNG")
            || str_starts_with($binary, 'RIFF');
    }

    private function generateLocalFallback(string $tmp, string $label, int $width, int $height): ?string
    {
        $image = imagecreatetruecolor($width, $height);
        $palette = [[28, 25, 23], [107, 79, 58], [30, 58, 95], [127, 29, 29], [196, 165, 116]];
        $c = $palette[abs(crc32($label)) % count($palette)];
        imagefilledrectangle($image, 0, 0, $width, $height, imagecolorallocate($image, $c[0], $c[1], $c[2]));
        $light = imagecolorallocatealpha($image, 255, 255, 255, 100);
        imagefilledellipse($image, (int) ($width / 2), (int) ($height / 2.2), (int) ($width * 0.65), (int) ($height * 0.5), $light);
        $text = strtoupper(Str::limit(str_replace(',', ' ', $label), 26, ''));
        imagestring($image, 5, max(8, (int) (($width - strlen($text) * 9) / 2)), (int) ($height * 0.78), $text, imagecolorallocate($image, 255, 255, 255));
        $ok = imagejpeg($image, $tmp, 88);
        imagedestroy($image);

        return $ok ? $tmp : null;
    }
}
