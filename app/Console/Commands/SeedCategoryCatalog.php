<?php

namespace App\Console\Commands;

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ImageService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Signature('shop:seed-catalog {--per=6 : Target products per leaf category (5-7)} {--refresh= : Comma-separated category slugs to re-attach matched photos}')]
#[Description('Fill every leaf category with AdSense-ready products, original copy and matched photos')]
class SeedCategoryCatalog extends Command
{
    public function handle(ImageService $images): int
    {
        $per = max(5, min(7, (int) $this->option('per')));
        Storage::disk('public')->makeDirectory('products');

        $refresh = collect(explode(',', (string) $this->option('refresh')))
            ->map(fn (string $s) => trim($s))
            ->filter()
            ->values()
            ->all();

        if ($refresh !== []) {
            return $this->refreshCategoryPhotos($images, $refresh);
        }

        $brands = Brand::query()->orderBy('id')->pluck('id')->all();
        if ($brands === []) {
            $this->error('No brands found. Seed the database first.');

            return self::FAILURE;
        }

        $skuSeq = (int) Product::query()->max('id') + 1;

        foreach ($this->catalog() as $categorySlug => $items) {
            $category = Category::query()->where('slug', $categorySlug)->first();
            if (! $category) {
                $this->warn('Missing category: '.$categorySlug);

                continue;
            }

            $parent = $category->parent_id
                ? Category::query()->find($category->parent_id)
                : $category;
            $sub = $category->parent_id ? $category : null;

            $existing = Product::query()
                ->when($sub, fn ($q) => $q->where('sub_category_id', $sub->id))
                ->when(! $sub, fn ($q) => $q->where('category_id', $parent->id)->whereNull('sub_category_id'))
                ->count();

            $needed = max(0, $per - $existing);
            $this->info($category->name.': have '.$existing.', adding '.$needed);

            $created = 0;
            foreach ($items as $item) {
                if ($created >= $needed) {
                    break;
                }

                $slug = Str::slug($item['name']);
                if (Product::query()->where('slug', $slug)->exists()) {
                    continue;
                }

                $copy = $this->enrichedCopy($slug, $item);

                $product = Product::query()->create([
                    'category_id' => $parent->id,
                    'sub_category_id' => $sub?->id,
                    'brand_id' => $brands[($skuSeq + $created) % count($brands)],
                    'name' => $item['name'],
                    'slug' => $slug,
                    'sku' => 'KS'.str_pad((string) ($skuSeq + $created), 4, '0', STR_PAD_LEFT),
                    'short_description' => $copy['short'],
                    'description' => $copy['description'],
                    'specifications' => [
                        ['label' => 'Brand', 'value' => 'Black Rossy'],
                        ['label' => 'Care', 'value' => $item['care'] ?? 'Follow care note on the product page'],
                        ['label' => 'Origin', 'value' => 'India'],
                    ],
                    'shipping_info' => 'Dispatched in 24–48 hours. Delivered in 2–5 days on most pin codes.',
                    'return_info' => '7-day return on unused products with original tags (beauty once opened is final sale).',
                    'mrp' => $item['mrp'],
                    'selling_price' => $item['price'],
                    'stock_quantity' => 48,
                    'min_order_qty' => 1,
                    'max_order_qty' => 5,
                    'status' => ProductStatus::Active,
                    'is_featured' => $created === 0,
                    'is_best_seller' => $created === 1,
                    'is_new_arrival' => true,
                    'seo_title' => $item['name'].' | Buy Online | Black Rossy',
                    'seo_description' => Str::limit($copy['short'], 155),
                    'seo_keywords' => $item['tags'],
                ]);
                $product->syncTagsFromString($item['tags']);

                $this->attachPhotos($images, $product, $item['photos'], $categorySlug);
                $created++;
                $this->line('  ✓ '.$product->name);
            }

            $skuSeq += $created;
        }

        $this->info('Done. Total products: '.Product::query()->count());

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $categorySlugs
     */
    private function refreshCategoryPhotos(ImageService $images, array $categorySlugs): int
    {
        $catalog = $this->catalog();
        $legacy = $this->legacyPhotoMap();

        foreach ($categorySlugs as $categorySlug) {
            $category = Category::query()->where('slug', $categorySlug)->first();
            if (! $category) {
                $this->warn('Missing category: '.$categorySlug);

                continue;
            }

            $bySlug = [];
            foreach ($catalog[$categorySlug] ?? [] as $item) {
                $bySlug[Str::slug($item['name'])] = $item;
            }

            $products = Product::query()
                ->where(function ($q) use ($category) {
                    $q->where('sub_category_id', $category->id)
                        ->orWhere(function ($inner) use ($category) {
                            $inner->where('category_id', $category->id)->whereNull('sub_category_id');
                        });
                })
                ->get();

            $this->info('Refreshing photos for '.$category->name.' ('.$products->count().')…');

            foreach ($products as $product) {
                $item = $bySlug[$product->slug] ?? null;
                $photos = $item['photos'] ?? ($legacy[$product->slug] ?? null);
                if (! $photos) {
                    $this->warn('  skip '.$product->slug.' (no photo map)');

                    continue;
                }

                if (is_array($item)) {
                    $copy = $this->enrichedCopy($product->slug, $item);
                    $product->update([
                        'name' => $item['name'],
                        'short_description' => $copy['short'],
                        'description' => $copy['description'],
                        'seo_title' => $item['name'].' | Buy Online | Black Rossy',
                        'seo_description' => Str::limit($copy['short'], 155),
                    ]);
                }

                $this->attachPhotos($images, $product->fresh(['images']), $photos, $categorySlug);
                $this->line('  ✓ '.$product->fresh()->name);
            }
        }

        $this->info('Photo refresh complete.');

        return self::SUCCESS;
    }

    /**
     * Legacy product slugs that predate the leaf catalog names.
     *
     * @return array<string, list<array{0: string, 1: string}>>
     */
    private function legacyPhotoMap(): array
    {
        return [
            'kids-festive-set' => [['pexels', '1620760'], ['pexels', '3933032']],
            'linen-mens-shirt' => [['pexels', '769733'], ['unsplash', '1598033129183']],
            'black-designer-kurti' => [['pexels', '29111909'], ['pexels', '19556879']],
            'ivory-embroidered-kurti' => [['pexels', '985635'], ['unsplash', '1515372039744-b8f02a3ae446']],
            'silk-scarf' => [['unsplash', '1483985988355-763728e1935b'], ['unsplash', '1469334031218-e382a71b716b']],
            'gold-plated-ring' => [['unsplash', '1605100804763-247f67b3557e'], ['pexels', '265906']],
            'pearl-drop-earrings' => [['unsplash', '1758995115445-c91788f5aa24'], ['pexels', '1413420']],
            'layered-necklace' => [['unsplash', '1758995116142-c626a962a682'], ['unsplash', '1599643478518-a784e5dc4c8f']],
            'delicate-bracelet' => [['unsplash', '1637868796504-32f45a96d5a0'], ['unsplash', '1611591437281-460bfbe1220a']],
            'everyday-sneakers' => [['unsplash', '1511556820780-d912e42b4980'], ['unsplash', '1460353581641-37baddab0fa2']],
            'leather-kolhapuri' => [['pexels', '267301'], ['pexels', '267320']],
            'structured-handbag' => [['unsplash', '1585488433921-f2ce041b2d64'], ['unsplash', '1584917865442-de89df76afd3']],
            'travel-backpack' => [['pexels', '2905240'], ['unsplash', '1553062407-98eeb64c6a62']],
            'compact-wallet' => [['unsplash', '1560472355-536de3962603'], ['unsplash', '1627123424574-724758594e93']],
            'satin-lip-colour' => [['unsplash', '1487412947147-5cebf100ffc2'], ['pexels', '2533266']],
            'botanical-face-cream' => [['unsplash', '1571781926291-c477ebfd024b'], ['pexels', '3762879']],
            'linen-cushion-cover' => [['unsplash', '1586023492125-27b2c045efd7'], ['unsplash', '1616486338812-3dadae4b4ace']],
            'scented-candle-set' => [['pexels', '278665'], ['pexels', '278664']],
            'festive-gift-box' => [['unsplash', '1549465220-1a8b9238cd48'], ['pexels', '264787']],
            'jewellery-care-kit' => [['unsplash', '1763256614634-7feb3ff79ff3'], ['pexels', '248077']],
        ];
    }

    /**
     * @param  array{0: array{0: string, 1: string}, 1?: array{0: string, 1: string}}  $photos
     */
    private function attachPhotos(ImageService $images, Product $product, array $photos, string $fallbackSlug): void
    {
        foreach ($product->images as $image) {
            $images->deleteMany([$image->path_thumb, $image->path_medium, $image->path_large]);
            $image->delete();
        }
        Storage::disk('public')->deleteDirectory('products/'.$product->id);

        foreach (array_values($photos) as $i => $ref) {
            $temp = $this->downloadRef($ref, 1400, 1400)
                ?: $this->downloadRef($this->categoryFallback($fallbackSlug), 1400, 1400);
            if (! $temp) {
                continue;
            }
            $paths = $images->storeProductImageFromPath($temp, $product->id, $i === 0 ? 'main' : 'gallery'.$i);
            @unlink($temp);
            ProductImage::query()->create($paths + [
                'product_id' => $product->id,
                'alt' => $product->name.($i === 0 ? '' : ' detail'),
                'is_primary' => $i === 0,
                'display_order' => $i,
            ]);
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function categoryFallback(string $slug): array
    {
        return match ($slug) {
            'mens-clothing' => ['unsplash', '1602810318383-e386cc2a3ccf'],
            'womens-clothing' => ['pexels', '28512779'],
            'kids-clothing' => ['pexels', '1620760'],
            'rings' => ['pexels', '1232931'],
            'earrings' => ['pexels', '2735970'],
            'necklaces' => ['unsplash', '1599643478518-a784e5dc4c8f'],
            'bracelets' => ['unsplash', '1611591437281-460bfbe1220a'],
            'mens-footwear' => ['pexels', '267320'],
            'womens-footwear' => ['pexels', '2529148'],
            'handbags' => ['unsplash', '1584917865442-de89df76afd3'],
            'backpacks' => ['pexels', '2905238'],
            'wallets' => ['unsplash', '1627123424574-724758594e93'],
            'beauty-products' => ['pexels', '2533266'],
            'home-products' => ['unsplash', '1616486338812-3dadae4b4ace'],
            'gift-items' => ['pexels', '264787'],
            default => ['unsplash', '1483985988355-763728e1935b'],
        };
    }

    /**
     * Leaf-category catalog: original copy + matched photo refs for AdSense-ready listings.
     *
     * @return array<string, list<array{name: string, short: string, description: string, tags: string, mrp: int, price: int, care?: string, photos: list<array{0: string, 1: string}>}>>
     */
    private function catalog(): array
    {
        $ad = "\n\nWhy shop on Black Rossy: clear product photos, guest checkout, Cash on Delivery, and 7-day easy returns on eligible unused items with tags.";

        return [
            'mens-clothing' => [
                $this->p('Classic Oxford Shirt', 'Crisp oxford shirt for office and weekend wear.', 'A breathable oxford weave with a neat collar and clean button placket. Pair with chinos or jeans. Check chest and shoulder fit before COD.'.$ad, 'shirt, men, office', 1899, 1199, [['pexels', '297933'], ['unsplash', '1602810318383-e386cc2a3ccf']], 'Machine wash cold'),
                $this->p('Casual Polo Tee', 'Soft polo tee for everyday comfort.', 'Mid-weight polo with a ribbed collar. True colour photography helps you match with trousers. Size up for a relaxed fit.'.$ad, 'polo, men, casual', 1299, 849, [['pexels', '1232459'], ['pexels', '2379004']]),
                $this->p('Slim Fit Chinos', 'Everyday chinos with a clean taper.', 'Stretch-comfort chinos for office-casual days. Measure waist and inseam from a pair you already own.'.$ad, 'chinos, men, trousers', 2199, 1499, [['unsplash', '1473966968600-fa801b869a1a'], ['unsplash', '1624378439575-d8705ad7ae80']]),
                $this->p('Knit Crew Sweater', 'Layer-ready crew neck knit.', 'Soft knit for cooler evenings. Avoid high heat drying. Style with a shirt underneath for smart-casual looks.'.$ad, 'sweater, men, knit', 2499, 1699, [['pexels', '45982'], ['pexels', '1656684']]),
                $this->p('Linen Resort Shirt', 'Airy linen-look shirt for summer days.', 'Lightweight resort shirt with an easy drape. Perfect for travel and festive daytime events.'.$ad, 'linen, shirt, summer', 1999, 1299, [['pexels', '7671168'], ['pexels', '298863']]),
                $this->p('Everyday Denim Jacket', 'Classic denim jacket for layering.', 'Medium-wash denim jacket that works over tees and shirts. Check sleeve length in product photos against your usual fit.'.$ad, 'denim, jacket, men', 2999, 1999, [['pexels', '1040945'], ['pexels', '6764040']]),
            ],
            'womens-clothing' => [
                $this->p('Floral Day Dress', 'Light floral dress for brunch and outings.', 'Flowy day dress with a flattering waist. Check length in photos and size chart before ordering COD.'.$ad, 'dress, women, floral', 2499, 1599, [['unsplash', '1496747611176-843222e1e57c'], ['unsplash', '1515372039744-b8f02a3ae446']]),
                $this->p('Maroon Evening Top', 'Rich maroon top for dinners and festivals.', 'Statement colour top that pairs with gold jewellery. Soft finish — keep away from sharp jewellery clasps while dressing.'.$ad, 'top, women, maroon', 1799, 1199, [['pexels', '19281310'], ['pexels', '29111909']]),
                $this->p('Cotton Straight Kurti', 'Everyday cotton kurti with clean lines.', 'Breathable straight kurti for work-from-home and errands. Measure bust and length using our kurti size guide.'.$ad, 'kurti, cotton, women', 1599, 999, [['pexels', '19556879'], ['pexels', '28512779']]),
                $this->p('Ivory Embroidered Tunic', 'Soft ivory tunic with delicate embroidery.', 'Light embroidery around the neckline. Pair with leggings or palazzo pants. Dry clean or gentle wash as labelled.'.$ad, 'tunic, embroidery, women', 2299, 1499, [['pexels', '28512779'], ['pexels', '19281310']]),
                $this->p('Wide Leg Lounge Pants', 'Comfortable wide-leg pants for travel days.', 'Relaxed wide-leg silhouette. Check waistband style in photos. Great with fitted tops.'.$ad, 'pants, women, lounge', 1699, 1099, [['unsplash', '1515886657613-9f3515b0c78f'], ['unsplash', '1469334031218-e382a71b716b']]),
                $this->p('Wrap Midi Dress', 'Wrap-style midi for easy occasion wear.', 'Adjustable wrap fit. Ideal for guests and evening plans. Style with studs or a slim bracelet.'.$ad, 'dress, wrap, women', 2699, 1799, [['unsplash', '1490481651871-ab68de25d43d'], ['unsplash', '1515372039744-b8f02a3ae446']]),
            ],
            'kids-clothing' => [
                $this->p('Kids Soft Cotton Tee', 'Soft cotton tee for everyday play.', 'Gentle fabric for active kids. Check age/size guidance on the listing. Machine wash friendly.'.$ad, 'kids, tee, cotton', 799, 499, [['pexels', '7869222'], ['pexels', '3771640']]),
                $this->p('Girls Party Frock', 'Cheerful frock for birthdays and festivals.', 'Easy zip or button access. Keep tags on until you confirm fit after delivery.'.$ad, 'kids, frock, girls', 1499, 999, [['pexels', '5693889'], ['pexels', '4473796']]),
                $this->p('Boys Casual Set', 'Two-piece casual set for outings.', 'Coordinated top and bottom for easy dressing. Soft seams for comfort.'.$ad, 'kids, boys, set', 1299, 849, [['pexels', '8499574'], ['pexels', '5560019']]),
                $this->p('Kids Festive Kurta Set', 'Festive kids outfit for celebrations.', 'Bright festive set for family functions and parties. Choose size by height and chest comfort.'.$ad, 'kids, festive, kurta', 1699, 1099, [['pexels', '1619697'], ['pexels', '35188']]),
                $this->p('Toddler Soft Joggers', 'Stretch joggers for crawlers and walkers.', 'Elastic waist and soft cuffs. Perfect for playdates. Wash inside out.'.$ad, 'kids, joggers, toddler', 899, 599, [['pexels', '1648377'], ['pexels', '3662667']]),
                $this->p('Kids Hooded Sweatshirt', 'Light hoodie for cooler evenings.', 'Soft fleece-feel hoodie. Avoid tumble high heat. Pair with joggers or jeans.'.$ad, 'kids, hoodie, sweatshirt', 1199, 799, [['pexels', '5559986'], ['pexels', '3933032']]),
            ],
            'rings' => [
                $this->p('Minimal Band Ring', 'Simple band for everyday stacking.', 'Smooth band profile. Compare with a ring you own for size 6–9. Keep dry after wash.'.$ad, 'ring, band, jewellery', 999, 649, [['pexels', '265906'], ['pexels', '1232931']], 'Wipe dry after wear'),
                $this->p('Solitaire Style Ring', 'Clear centre stone with a clean setting.', 'Fashion solitaire look for gifting and occasions. Avoid perfume sprays on the stone.'.$ad, 'ring, solitaire, gift', 1899, 1199, [['pexels', '1232931'], ['pexels', '248077']]),
                $this->p('Twisted Rope Ring', 'Textured twisted band with soft shine.', 'Adds interest to simple outfits. Store separately to avoid scratches.'.$ad, 'ring, twisted, jewellery', 1299, 849, [['pexels', '248077'], ['pexels', '265906']]),
                $this->p('Stackable Midi Ring', 'Slim midi ring for layered looks.', 'Wear alone or stacked. Check finger comfort for all-day wear.'.$ad, 'ring, midi, stack', 799, 499, [['pexels', '691046'], ['unsplash', '1605100804763-247f67b3557e']]),
                $this->p('Floral Motif Ring', 'Floral face ring for festive styling.', 'Statement face with a feminine motif. Pair with embroidered kurtis.'.$ad, 'ring, floral, festive', 1499, 999, [['pexels', '5370645'], ['unsplash', '1605100804763-247f67b3557e']]),
                $this->p('Couple Band Pair', 'Matching pair of polished bands.', 'Gift-ready pair. Confirm both sizes before COD. Comes ready for gift wrap.'.$ad, 'ring, couple, gift', 2199, 1499, [['unsplash', '1689777238091-59591cdf13e7'], ['pexels', '248077']]),
            ],
            'earrings' => [
                $this->p('Everyday Stud Pair', 'Lightweight studs for daily wear.', 'Secure posts for comfort. Wipe after wear. Great starter gift.'.$ad, 'earrings, studs, daily', 699, 449, [['pexels', '2735970'], ['pexels', '9428787']], 'Wipe dry'),
                $this->p('Hoop Everyday Earrings', 'Medium hoops with a polished finish.', 'Classic hoops that work with open hair and buns. Check diameter in photos.'.$ad, 'earrings, hoops', 999, 649, [['unsplash', '1617038260897-41a1f14a8ca0'], ['pexels', '2735970']]),
                $this->p('Drop Pearl Earrings', 'Soft pearl-look drops for occasions.', 'Elegant drop length for festive photos. Store flat in a pouch.'.$ad, 'earrings, pearl, festive', 1299, 849, [['pexels', '1413420'], ['unsplash', '1515562141207-7a88fb7ce338']]),
                $this->p('Jhumka Style Earrings', 'Traditional jhumka silhouette for ethnic wear.', 'Pairs with kurtis and festive sets. Avoid heavy perfume on metal.'.$ad, 'earrings, jhumka, ethnic', 1599, 999, [['unsplash', '1758995115682-1452a1a9e35b'], ['pexels', '1413420']]),
                $this->p('Crystal Cluster Studs', 'Sparkle cluster studs for evenings.', 'Catch light in evening outfits. Keep away from water.'.$ad, 'earrings, crystal, evening', 1199, 799, [['pexels', '9428787'], ['pexels', '2735970']]),
                $this->p('Thread Tassel Earrings', 'Colourful tassel earrings for festive fun.', 'Lightweight tassels. Hang to store so threads stay neat.'.$ad, 'earrings, tassel, colour', 899, 599, [['pexels', '10983783'], ['pexels', '1413420']]),
            ],
            'necklaces' => [
                $this->p('Delicate Chain Necklace', 'Fine chain for everyday layering.', 'Adjustable length where listed. Avoid tugging when wearing knits.'.$ad, 'necklace, chain, daily', 999, 649, [['unsplash', '1599643478518-a784e5dc4c8f'], ['pexels', '1454171']]),
                $this->p('Pendant Necklace', 'Simple pendant on a fine chain.', 'Centre pendant that sits cleanly on round and V necklines.'.$ad, 'necklace, pendant', 1299, 849, [['pexels', '5370704'], ['unsplash', '1599643478518-a784e5dc4c8f']]),
                $this->p('Layered Chain Set', 'Two-tone layered chains in one set.', 'Pre-styled layers. Ideal gift with a care note.'.$ad, 'necklace, layered, gift', 1799, 1199, [['pexels', '1454171'], ['unsplash', '1599643478518-a784e5dc4c8f']]),
                $this->p('Pearl Strand Necklace', 'Classic pearl-look strand for occasions.', 'Timeless festive accessory. Store flat. Wipe with a dry cloth.'.$ad, 'necklace, pearl, festive', 2199, 1499, [['unsplash', '1515562141207-7a88fb7ce338'], ['pexels', '248077']]),
                $this->p('Choker Collar Necklace', 'Short choker for modern outfits.', 'Sits high on the neck. Check comfort if you prefer longer chains.'.$ad, 'necklace, choker', 1499, 999, [['pexels', '1454172'], ['pexels', '5370704']]),
                $this->p('Statement Collar Piece', 'Bolder collar for festive evenings.', 'Wear with plain necklines. Keep clasp dry and secure.'.$ad, 'necklace, statement, festive', 2499, 1699, [['unsplash', '1745270143445-63ccdffbfbca'], ['pexels', '1454171']]),
            ],
            'bracelets' => [
                $this->p('Slim Chain Bracelet', 'Everyday slim bracelet.', 'Light on the wrist. Easy gift add-on with earrings.'.$ad, 'bracelet, chain, daily', 799, 499, [['unsplash', '1611591437281-460bfbe1220a'], ['pexels', '1191531']]),
                $this->p('Cuff Bangle', 'Open cuff bangle with polish.', 'Slide-on cuff. Check wrist comfort in photos.'.$ad, 'bracelet, cuff, bangle', 1299, 849, [['pexels', '1191531'], ['unsplash', '1611591437281-460bfbe1220a']]),
                $this->p('Beaded Charm Bracelet', 'Charm bracelet with playful beads.', 'Fun casual accessory. Avoid water to protect beads.'.$ad, 'bracelet, charm, beads', 999, 649, [['unsplash', '1721808085307-919cf89fe3fa'], ['pexels', '264787']]),
                $this->p('Sparkle Tennis Style', 'Line of clear stones for evenings.', 'Evening sparkle. Wipe after wear and store dry.'.$ad, 'bracelet, sparkle, evening', 1899, 1299, [['pexels', '3641056'], ['pexels', '1232931']]),
                $this->p('Stackable Bangle Set', 'Set of thin stackable bangles.', 'Wear as a stack or split across outfits.'.$ad, 'bracelet, stack, bangles', 1499, 999, [['pexels', '1191532'], ['unsplash', '1611591437281-460bfbe1220a']]),
                $this->p('Leather Cord Bracelet', 'Casual cord bracelet with metal accent.', 'Casual everyday wrist piece. Keep leather dry.'.$ad, 'bracelet, leather, casual', 899, 599, [['unsplash', '1637169797848-12431f1d355c'], ['unsplash', '1627123424574-724758594e93']]),
            ],
            'mens-footwear' => [
                $this->p('Men Canvas Sneakers', 'Everyday canvas sneakers.', 'Breathable casual sneakers. Check size chart; size up if between sizes.'.$ad, 'sneakers, men, canvas', 1999, 1299, [['pexels', '1478442'], ['unsplash', '1460353581641-37baddab0fa2']]),
                $this->p('Leather Look Loafers', 'Smart loafers for office-casual.', 'Slip-on loafers with a clean bit detail. Wipe after wear.'.$ad, 'loafers, men, office', 2499, 1699, [['pexels', '267320'], ['unsplash', '1560769629-975ec94e6a86']]),
                $this->p('Sports Running Shoes', 'Cushioned trainers for walking days.', 'Supportive sole for errands and light runs. Air dry if damp.'.$ad, 'shoes, running, men', 2999, 1999, [['unsplash', '1542291026-7eec264c27ff'], ['pexels', '2529148']]),
                $this->p('Casual Slip Ons', 'Easy slip-ons for travel.', 'No-lace convenience. Ideal for short trips and airport days.'.$ad, 'slipon, men, travel', 1799, 1199, [['pexels', '292999'], ['pexels', '267320']]),
                $this->p('Formal Derby Shoes', 'Derby-style shoes for occasions.', 'Polishable look for ceremonies and meetings. Use a shoe bag when travelling.'.$ad, 'formal, derby, men', 3499, 2299, [['unsplash', '1560769629-975ec94e6a86'], ['pexels', '267320']]),
                $this->p('Chunky Sole Sneakers', 'Street-style chunky sneakers.', 'Bold sole profile. Match with jeans or joggers.'.$ad, 'sneakers, chunky, men', 2799, 1899, [['pexels', '1464625'], ['unsplash', '1542291026-7eec264c27ff']]),
            ],
            'womens-footwear' => [
                $this->p('Women Everyday Sneakers', 'Comfort sneakers for all-day wear.', 'Cushioned insole for walking. True product colours in photos.'.$ad, 'sneakers, women, daily', 2299, 1499, [['pexels', '2529148'], ['unsplash', '1460353581641-37baddab0fa2']]),
                $this->p('Block Heel Sandals', 'Stable block heels for occasions.', 'Easier than stilettos for longer events. Check heel height in photos.'.$ad, 'sandals, heel, women', 1999, 1299, [['pexels', '336372'], ['unsplash', '1543163521-1bf539c55dd2']]),
                $this->p('Ballet Flats', 'Soft ballet flats for work days.', 'Slip-on comfort with a neat toe shape.'.$ad, 'flats, ballet, women', 1499, 999, [['unsplash', '1543163521-1bf539c55dd2'], ['pexels', '336372']]),
                $this->p('Ankle Strap Heels', 'Ankle strap heels for evenings.', 'Secure strap for dancing and dinners. Break in gently indoors.'.$ad, 'heels, ankle, women', 2499, 1699, [['unsplash', '1606107557195-0e29a4b5b4aa'], ['pexels', '336372']]),
                $this->p('Slide Sandals', 'Easy slides for casual outings.', 'Quick on-and-off comfort. Wipe sole after outdoor use.'.$ad, 'slides, sandals, women', 1299, 849, [['pexels', '378278'], ['unsplash', '1543163521-1bf539c55dd2']]),
                $this->p('White Court Sneakers', 'Clean white sneakers for outfits.', 'Pairs with dresses and jeans. Spot clean the upper.'.$ad, 'sneakers, white, women', 2199, 1499, [['unsplash', '1460353581641-37baddab0fa2'], ['pexels', '2529148']]),
            ],
            'handbags' => [
                $this->p('Mini Crossbody Bag', 'Compact crossbody for essentials.', 'Fits phone, cards and keys. Adjust strap for comfort.'.$ad, 'bag, crossbody, mini', 1999, 1299, [['pexels', '904350'], ['unsplash', '1584917865442-de89df76afd3']]),
                $this->p('Structured Tote', 'Roomy tote for work days.', 'Carries a light laptop sleeve and pouch. Wipe exterior dry.'.$ad, 'tote, bag, office', 2799, 1899, [['unsplash', '1590874103328-eac38a683ce7'], ['unsplash', '1584917865442-de89df76afd3']]),
                $this->p('Flap Shoulder Bag', 'Classic flap bag with chain option.', 'Dress-up ready. Keep away from rain when possible.'.$ad, 'handbag, flap, shoulder', 2499, 1699, [['unsplash', '1584917865442-de89df76afd3'], ['pexels', '904350']]),
                $this->p('Evening Clutch', 'Slim clutch for festive nights.', 'Holds cards and lipstick. Pair with ethnic or western outfits.'.$ad, 'clutch, evening, festive', 1499, 999, [['unsplash', '1548036328-c9fa89d128fa'], ['pexels', '904350']]),
                $this->p('Hobo Soft Bag', 'Slouchy hobo for casual days.', 'Soft structure that sits comfortably on the shoulder.'.$ad, 'hobo, bag, casual', 2299, 1499, [['pexels', '1152077'], ['unsplash', '1590874103328-eac38a683ce7']]),
                $this->p('Box Bag Mini', 'Boxy mini bag with metal clasp.', 'Structured mini for brunch and parties.'.$ad, 'boxbag, mini, party', 1899, 1299, [['unsplash', '1569484221992-2a453658fff3'], ['pexels', '904350']]),
            ],
            'backpacks' => [
                $this->p('Daily Commute Backpack', 'Padded backpack for daily travel.', 'Laptop sleeve friendly. Multiple pockets for chargers and bottles.'.$ad, 'backpack, commute, travel', 2499, 1699, [['pexels', '2905238'], ['unsplash', '1491637639811-60e2756cc1c7']]),
                $this->p('Canvas Weekend Pack', 'Canvas pack for short trips.', 'Rugged look with easy zip access. Spot clean only.'.$ad, 'backpack, canvas, weekend', 2199, 1499, [['unsplash', '1553062407-98eeb64c6a62'], ['pexels', '2905238']]),
                $this->p('Slim City Backpack', 'Slim profile for urban days.', 'Less bulk, still fits daily essentials.'.$ad, 'backpack, slim, city', 1999, 1299, [['unsplash', '1491637639811-60e2756cc1c7'], ['unsplash', '1553062407-98eeb64c6a62']]),
                $this->p('Laptop Office Backpack', 'Organised backpack for office commute.', 'Padded compartment and quick-access pocket.'.$ad, 'backpack, laptop, office', 2999, 1999, [['unsplash', '1547949003-9792a18a2601'], ['pexels', '2905238']]),
                $this->p('Kids School Backpack', 'Lightweight pack for school days.', 'Comfortable straps for younger shoulders. Check volume in photos.'.$ad, 'backpack, kids, school', 1499, 999, [['unsplash', '1642375352634-ad952121fdb3'], ['pexels', '1620760']]),
                $this->p('Travel Duffel Backpack', 'Hybrid duffel-backpack for overnight trips.', 'Carry as backpack or grab handle. Ideal for weekend getaways.'.$ad, 'backpack, duffel, travel', 2799, 1899, [['unsplash', '1761599934469-999a86c52830'], ['unsplash', '1553062407-98eeb64c6a62']]),
            ],
            'wallets' => [
                $this->p('Bifold Leather Wallet', 'Classic bifold with card slots.', 'Slim enough for front pockets. Avoid overstuffing.'.$ad, 'wallet, bifold, leather', 1299, 799, [['unsplash', '1627123424574-724758594e93'], ['unsplash', '1601592996763-f05c9c80a7f1']], 'Wipe dry'),
                $this->p('Card Holder Mini', 'Minimal card holder for light carry.', 'Holds essential cards. Pair with a phone pouch.'.$ad, 'wallet, cardholder', 799, 499, [['unsplash', '1614330316567-11d8e572db16'], ['pexels', '915915']]),
                $this->p('Zip Around Wallet', 'Zip-around security for travel.', 'Full zip keeps coins and cards secure while travelling.'.$ad, 'wallet, zip, travel', 1499, 999, [['unsplash', '1601592996763-f05c9c80a7f1'], ['unsplash', '1627123424574-724758594e93']]),
                $this->p('Long Clutch Wallet', 'Long wallet that doubles as a mini clutch.', 'Space for notes and cards. Handy for evenings.'.$ad, 'wallet, long, clutch', 1699, 1099, [['unsplash', '1624538000860-24716b9050f2'], ['pexels', '4452510']]),
                $this->p('Money Clip Wallet', 'Slim clip wallet for minimalists.', 'Fewer cards, cleaner pocket line.'.$ad, 'wallet, clip, slim', 999, 649, [['unsplash', '1579014134953-1580d7f123f3'], ['unsplash', '1614330316567-11d8e572db16']]),
                $this->p('Passport Travel Wallet', 'Travel wallet for tickets and ID.', 'Keeps travel documents together. Gift-ready for frequent flyers.'.$ad, 'wallet, passport, travel', 1899, 1299, [['unsplash', '1612023395494-1c4050b68647'], ['unsplash', '1620109176813-e91290f6c795']]),
            ],
            'beauty-products' => [
                $this->p('Matte Lip Crayon', 'Buildable matte lip colour.', 'Swatch on wrist first. Close cap tightly. Opened beauty is final sale.'.$ad, 'beauty, lipstick, matte', 699, 449, [['pexels', '2533266'], ['unsplash', '1596462502278-27bfdc403348']], 'Beauty — non-returnable once opened'),
                $this->p('Hydrating Face Serum', 'Lightweight serum for daily glow.', 'Use on clean skin. Patch test if sensitive.'.$ad, 'serum, skincare, face', 1299, 899, [['pexels', '3762879'], ['unsplash', '1556228720-195a672e8a03']]),
                $this->p('Gentle Cream Cleanser', 'Cream cleanser for everyday faces.', 'Massage, rinse, pat dry. Avoid eye contact.'.$ad, 'cleanser, skincare', 999, 699, [['unsplash', '1556228578-0d85b1a4d571'], ['pexels', '3762879']]),
                $this->p('Blush Compact Duo', 'Soft blush compact for cheeks.', 'Build colour slowly. Use clean brush.'.$ad, 'blush, makeup', 899, 599, [['unsplash', '1512496015851-a90fb38ba796'], ['unsplash', '1596462502278-27bfdc403348']]),
                $this->p('Hand Cream Tube', 'Nourishing hand cream for dry days.', 'Apply after washing hands. Travel-friendly tube.'.$ad, 'handcream, care', 599, 399, [['pexels', '4202325'], ['unsplash', '1556228720-195a672e8a03']]),
                $this->p('Sheet Mask Set', 'Hydrating sheet masks for self-care nights.', 'Use as directed on pack. Single-use masks.'.$ad, 'mask, skincare, set', 799, 549, [['pexels', '4041392'], ['pexels', '3762879']]),
            ],
            'home-products' => [
                $this->p('Cotton Cushion Cover', 'Soft cushion cover for sofas.', 'Zip cover — insert not always included (see listing). Shake and air regularly.'.$ad, 'cushion, home, decor', 899, 549, [['unsplash', '1616486338812-3dadae4b4ace'], ['unsplash', '1586023492125-27b2c045efd7']]),
                $this->p('Soy Jar Candle', 'Soy-wax jar candle for calm evenings.', 'Trim wick, burn on heat-safe surface, never leave unattended.'.$ad, 'candle, home, scent', 1299, 849, [['pexels', '278664'], ['unsplash', '1760804876380-9a98aabb8ed4']]),
                $this->p('Table Photo Frame', 'Simple frame for desk or shelf.', 'Fits standard print sizes listed on page. Wipe glass gently.'.$ad, 'frame, home, gift', 999, 649, [['pexels', '1453005'], ['unsplash', '1586023492125-27b2c045efd7']]),
                $this->p('Cotton Throw Blanket', 'Light throw for couches and naps.', 'Soft neutral tone. Follow wash label.'.$ad, 'throw, blanket, home', 1999, 1299, [['pexels', '1571460'], ['unsplash', '1616486338812-3dadae4b4ace']]),
                $this->p('Ceramic Planter Pot', 'Minimal planter for indoor plants.', 'Drainage details on listing. Wipe with damp cloth.'.$ad, 'planter, home, ceramic', 1199, 799, [['unsplash', '1485955900006-10f4d324d411'], ['pexels', '1598505']]),
                $this->p('Scented Candle Trio', 'Set of three small scented candles.', 'Gift-ready set. Follow burn safety on each jar.'.$ad, 'candle, set, gift', 1799, 1199, [['unsplash', '1760804876380-9a98aabb8ed4'], ['pexels', '278664']]),
            ],
            'gift-items' => [
                $this->p('Curated Festival Box', 'Ready-to-gift festival box.', 'Fill with jewellery or beauty favourites. Ships protected.'.$ad, 'gift, festival, box', 2499, 1799, [['pexels', '264787'], ['unsplash', '1549465220-1a8b9238cd48']]),
                $this->p('Jewellery Pouch Set', 'Soft pouches for storing jewellery.', 'Travel-friendly pouches to prevent tangles.'.$ad, 'gift, jewellery, pouch', 799, 499, [['unsplash', '1628483211662-9bcc692c46dc'], ['pexels', '248077']]),
                $this->p('Wellness Self Care Kit', 'Self-care kit for relaxed evenings.', 'Thoughtful mix for birthdays and thank-you gifts.'.$ad, 'gift, wellness, kit', 1999, 1399, [['unsplash', '1596462502278-27bfdc403348'], ['pexels', '3762879']]),
                $this->p('Luxury Gift Wrap Pack', 'Ribbons and wrap essentials.', 'Elevate any product into a presentable gift.'.$ad, 'gift, wrap, ribbon', 599, 399, [['pexels', '1303081'], ['pexels', '264787']]),
                $this->p('Couple Gift Combo', 'Shared gift idea for couples.', 'Pair rings or wallets from our catalogue for a complete gesture.'.$ad, 'gift, couple, combo', 2999, 2199, [['pexels', '1666065'], ['pexels', '264787']]),
                $this->p('Kids Surprise Bundle', 'Fun gift bundle for little ones.', 'Age-appropriate soft goods. Keep tags until fit is confirmed.'.$ad, 'gift, kids, bundle', 1699, 1199, [['pexels', '264771'], ['pexels', '1620760']]),
            ],
        ];
    }

    /**
     * Prefer unique AdSense-ready copy from database/data/product_copy.php when present.
     *
     * @param  array{name: string, short: string, description: string}  $item
     * @return array{short: string, description: string}
     */
    private function enrichedCopy(string $slug, array $item): array
    {
        static $copy = null;

        if ($copy === null) {
            $path = database_path('data/product_copy.php');
            $copy = is_file($path) ? require $path : [];
        }

        $row = $copy[$slug] ?? null;

        return [
            'short' => trim((string) ($row['short'] ?? $item['short'])),
            'description' => trim((string) ($row['description'] ?? $item['description'])),
        ];
    }

    private function p(string $name, string $short, string $description, string $tags, int $mrp, int $price, array $photos, ?string $care = null): array
    {
        return array_filter([
            'name' => $name,
            'short' => $short,
            'description' => $description,
            'tags' => $tags,
            'mrp' => $mrp,
            'price' => $price,
            'photos' => $photos,
            'care' => $care,
        ], fn ($v) => $v !== null);
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
                $response = Http::timeout(40)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (compatible; BlackRossyCatalog/1.0)',
                        'Accept' => 'image/*',
                    ])
                    ->withOptions(['allow_redirects' => true])
                    ->get($url);

                if (! $response->successful() || strlen($response->body()) < 2000) {
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
}
