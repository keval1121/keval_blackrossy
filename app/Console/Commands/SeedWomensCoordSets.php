<?php

namespace App\Console\Commands;

use App\Enums\ProductStatus;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ImageService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Signature('shop:seed-womens-coord-sets {--force : Recreate products even if slugs already exist}')]
#[Description('Add the womens co-ord set listings (Fashion > Women\'s Clothing) with photos, sizes and brand blackrosy')]
class SeedWomensCoordSets extends Command
{
    public function handle(ImageService $images): int
    {
        $fashion = Category::query()->where('slug', 'fashion')->first();
        $womens = Category::query()->where('slug', 'womens-clothing')->first();

        if (! $fashion || ! $womens) {
            $this->error('Fashion / Women\'s Clothing categories missing. Run db:seed first.');

            return self::FAILURE;
        }

        $brand = Brand::query()->firstOrCreate(
            ['slug' => 'blackrosy'],
            ['name' => 'blackrosy', 'is_active' => true],
        );

        $sizeValues = $this->ensureSizeValues();

        Product::query()
            ->where('sub_category_id', $womens->id)
            ->whereNotIn('slug', collect($this->items())->pluck('slug'))
            ->update(['is_featured' => false]);

        Storage::disk('public')->makeDirectory('products');

        $dataDir = database_path('data/womens-coord-sets');
        $created = 0;
        $updated = 0;

        foreach ($this->items() as $index => $row) {
            $imagePath = $dataDir.'/'.$row['image'];
            if (! is_file($imagePath)) {
                $this->error('Missing image: '.$imagePath);

                return self::FAILURE;
            }

            $existing = Product::query()->where('slug', $row['slug'])->first();

            if ($existing && ! $this->option('force')) {
                $existing->update([
                    'brand_id' => $brand->id,
                    'mrp' => $row['mrp'],
                    'selling_price' => 400,
                    'is_featured' => true,
                    'is_best_seller' => true,
                    'is_new_arrival' => true,
                    'status' => ProductStatus::Active,
                    'sold_count' => $row['sold'],
                ]);
                $this->syncSizeVariants($existing, $sizeValues);
                $this->line('Updated + sizes S–XXXL: '.$existing->name);
                $updated++;

                continue;
            }

            if ($existing) {
                $oldId = $existing->id;
                foreach ($existing->images as $image) {
                    $images->deleteMany([
                        $image->path_thumb,
                        $image->path_medium,
                        $image->path_large,
                    ]);
                }
                $existing->images()->delete();
                $existing->variants()->delete();
                $existing->delete();
                Storage::disk('public')->deleteDirectory('products/'.$oldId);
            }

            $product = Product::query()->create([
                'category_id' => $fashion->id,
                'sub_category_id' => $womens->id,
                'brand_id' => $brand->id,
                'name' => $row['name'],
                'slug' => $row['slug'],
                'sku' => 'WS'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'short_description' => $row['short'],
                'description' => $row['desc'],
                'specifications' => $row['specs'],
                'shipping_info' => 'Dispatched in 24-48 hours. Delivered in 2-5 days.',
                'return_info' => '7-day return on unused products with original tags.',
                'mrp' => $row['mrp'],
                'selling_price' => 400,
                'stock_quantity' => 50,
                'min_order_qty' => 1,
                'max_order_qty' => 5,
                'status' => ProductStatus::Active,
                'is_featured' => true,
                'is_best_seller' => true,
                'is_new_arrival' => true,
                'seo_title' => $row['name'].' | Women\'s Clothing',
                'seo_description' => $row['short'],
                'seo_keywords' => $row['tags'],
                'sold_count' => $row['sold'],
            ]);

            $product->syncTagsFromString($row['tags']);

            $paths = $images->storeProductImageFromPath($imagePath, $product->id, 'main');
            $product->images()->create($paths + [
                'alt' => $row['name'],
                'is_primary' => true,
                'display_order' => 0,
            ]);

            $this->syncSizeVariants($product, $sizeValues);

            $this->info('Created: '.$product->name.' (blackrosy) ₹400 | sizes S–XXXL');
            $created++;
        }

        $this->newLine();
        $this->info("Done. Created {$created}, updated {$updated}.");
        $this->comment('Open: /fashion/womens-clothing');

        return self::SUCCESS;
    }

    /**
     * @return list<AttributeValue>
     */
    private function ensureSizeValues(): array
    {
        $size = Attribute::query()->firstOrCreate(
            ['slug' => 'size'],
            ['name' => 'Size', 'type' => 'select', 'is_filterable' => true, 'display_order' => 1],
        );

        $sizes = ['S', 'M', 'L', 'XL', 'XXL', 'XXXL'];
        $values = [];

        foreach ($sizes as $order => $label) {
            $values[] = $size->values()->firstOrCreate(
                ['slug' => Str::slug($label)],
                ['value' => $label, 'display_order' => $order],
            );
        }

        return $values;
    }

    /**
     * @param  list<AttributeValue>  $sizeValues
     */
    private function syncSizeVariants(Product $product, array $sizeValues): void
    {
        $product->variants()->delete();

        $stockPerSize = 10;

        foreach ($sizeValues as $index => $value) {
            $variant = $product->variants()->create([
                'sku' => $product->sku.'-'.$value->value,
                'stock' => $stockPerSize,
                'is_active' => true,
            ]);

            $variant->values()->create([
                'attribute_id' => $value->attribute_id,
                'attribute_value_id' => $value->id,
            ]);
        }

        $product->update([
            'stock_quantity' => count($sizeValues) * $stockPerSize,
        ]);
    }

    /**
     * @return list<array{
     *     name: string,
     *     slug: string,
     *     image: string,
     *     mrp: int,
     *     short: string,
     *     desc: string,
     *     specs: list<array{label: string, value: string}>,
     *     tags: string,
     *     sold: int
     * }>
     */
    private function items(): array
    {
        return [
            [
                'name' => 'Womens White Teal Paisley Print Shirt & Pant Set',
                'slug' => 'womens-white-teal-paisley-shirt-pant-set',
                'image' => '01-teal-paisley.jpg',
                'mrp' => 1499,
                'short' => 'Soft white co-ord with teal paisley print on the shirt, paired with plain white drawstring pants. Light everyday ethnic-fusion set.',
                'desc' => 'Picked this one because the teal print on white looks fresh without being heavy. Shirt is a relaxed mandarin-collar style with a short button placket, paisley motifs across the chest, and a denser border on the hem and cuffs. Pants are solid white, elastic waist with drawstring — easy all-day fit. Fabric feels light cotton-linen, good for summer afternoons and casual outings. Size in photo is M. Wash separately first time, mild detergent, hang dry so the print stays sharp.',
                'specs' => [
                    ['label' => 'Set includes', 'value' => '1 shirt + 1 pant'],
                    ['label' => 'Fit', 'value' => 'Relaxed / comfort'],
                    ['label' => 'Neck', 'value' => 'Mandarin collar'],
                    ['label' => 'Care', 'value' => 'Machine wash cold, hang dry'],
                ],
                'tags' => 'womens set, co-ord, paisley print, ethnic wear',
                'sold' => 64,
            ],
            [
                'name' => 'Womens Off-White Elephant Print Shirt & White Pant Set',
                'slug' => 'womens-offwhite-elephant-print-shirt-pant-set',
                'image' => '02-elephant-print.jpg',
                'mrp' => 1499,
                'short' => 'Off-white mandarin shirt with nature-inspired elephant and leaf prints, matched with soft white drawstring trousers.',
                'desc' => 'Liked this for the artwork — falling leaves on the shoulder, a small elephant near the pocket, and a bigger elephant family scene near the hem. Shirt is cream/off-white, long sleeve (shown rolled), mandarin collar with partial buttons, left chest pocket. Pants are plain white with elastic waist and drawstring. Soft breathable fabric, relaxed fit. Works for casual days, travel, or a light festive look. Size tagged M. Prefer reverse wash so the print lasts longer; avoid bleach.',
                'specs' => [
                    ['label' => 'Set includes', 'value' => '1 shirt + 1 pant'],
                    ['label' => 'Fit', 'value' => 'Relaxed'],
                    ['label' => 'Highlight', 'value' => 'Elephant & leaf artwork'],
                    ['label' => 'Care', 'value' => 'Cold wash, reverse side for print'],
                ],
                'tags' => 'womens set, elephant print, co-ord, casual ethnic',
                'sold' => 58,
            ],
            [
                'name' => 'Womens Cream Floral Border Shirt & White Pant Set',
                'slug' => 'womens-cream-floral-border-shirt-pant-set',
                'image' => '03-floral-border.jpg',
                'mrp' => 1499,
                'short' => 'Cream tunic shirt with a colourful floral border on hem and cuffs, paired with white elastic drawstring pants.',
                'desc' => 'Clean cream base with a wide floral border in yellow, orange and soft pinks — that border is the whole look. Shirt has mandarin collar, short button placket, long sleeves rolled in the photo. Pants are solid white, gathered elastic waist with white drawstring, straight relaxed leg. Light fabric, easy to wear for brunches, college or home gatherings. Size shown is M. Gentle wash recommended; hang dry to keep the border colours bright.',
                'specs' => [
                    ['label' => 'Set includes', 'value' => '1 shirt + 1 pant'],
                    ['label' => 'Fit', 'value' => 'Comfort / relaxed'],
                    ['label' => 'Neck', 'value' => 'Mandarin collar'],
                    ['label' => 'Care', 'value' => 'Gentle wash, hang dry'],
                ],
                'tags' => 'womens set, floral border, tunic set, summer wear',
                'sold' => 71,
            ],
            [
                'name' => 'Womens Pink Peacock Embroidered Shirt & Pant Set',
                'slug' => 'womens-pink-peacock-embroidered-shirt-pant-set',
                'image' => '04-pink-peacock.jpg',
                'mrp' => 1599,
                'short' => 'Pastel pink shirt with peacock embroidery and tiny floral motifs, matched with crisp white drawstring pants.',
                'desc' => 'This one feels a bit dressier because of the embroidery. Soft pink base with white star-like florals scattered across, plus detailed peacock motifs near the hem and sleeve in blue-teal tones. Collar and placket have a light blue-white trim. Pants are plain white, elastic waist with drawstring. Nice for festive evenings, pooja visits or when you want something prettier than a basic kurta set. Size in image is M. Hand wash or gentle cycle; do not wring the embroidered areas.',
                'specs' => [
                    ['label' => 'Set includes', 'value' => '1 shirt + 1 pant'],
                    ['label' => 'Highlight', 'value' => 'Peacock embroidery'],
                    ['label' => 'Fit', 'value' => 'Relaxed'],
                    ['label' => 'Care', 'value' => 'Gentle wash, no wring on embroidery'],
                ],
                'tags' => 'womens set, peacock embroidery, pink co-ord, festive wear',
                'sold' => 69,
            ],
            [
                'name' => 'Womens Cream Arch Border Print Shirt & Pant Set',
                'slug' => 'womens-cream-arch-border-print-shirt-pant-set',
                'image' => '05-arch-border.jpg',
                'mrp' => 1499,
                'short' => 'Cream mandarin shirt with deep red, green and mustard arch border print, plus white drawstring trousers.',
                'desc' => 'Got this in for the heritage-style border — repeating arch motifs in red, forest green and mustard along the hem and cuffs. Shirt is cream base, mandarin collar, short button placket, long sleeves shown rolled. Pants are solid white with elastic waist and drawstring. Looks rich in photos but still easy to wear daily. Good pickup for office-casual Fridays or evening outings. Size tagged M. Wash colours separately for the first 2 washes; hang dry.',
                'specs' => [
                    ['label' => 'Set includes', 'value' => '1 shirt + 1 pant'],
                    ['label' => 'Fit', 'value' => 'Regular / comfort'],
                    ['label' => 'Neck', 'value' => 'Mandarin collar'],
                    ['label' => 'Care', 'value' => 'Wash separately first times'],
                ],
                'tags' => 'womens set, arch border, ethnic print, co-ord set',
                'sold' => 62,
            ],
            [
                'name' => 'Womens Off-White Umbrella Print Shirt & White Pant Set',
                'slug' => 'womens-offwhite-umbrella-print-shirt-pant-set',
                'image' => '06-umbrella-print.jpg',
                'mrp' => 1499,
                'short' => 'Off-white shirt with delicate ethnic figure and umbrella prints, paired with white elastic drawstring pants.',
                'desc' => 'Subtle print that looks nice up close — small figures in ethnic wear, decorative umbrellas and soft dotted line details on an off-white base. Shirt is long sleeve, mandarin-style opening, relaxed fit. Pants are bright white with elastic waist and drawstring. Light fabric, comfortable for all-day wear. Easy set if you want something different from heavy embroidery. Size in photo is M. Mild detergent, reverse wash preferred; avoid high heat iron on the print.',
                'specs' => [
                    ['label' => 'Set includes', 'value' => '1 shirt + 1 pant'],
                    ['label' => 'Fit', 'value' => 'Relaxed'],
                    ['label' => 'Neck', 'value' => 'Mandarin opening'],
                    ['label' => 'Care', 'value' => 'Mild wash, low iron'],
                ],
                'tags' => 'womens set, umbrella print, casual co-ord, ethnic casual',
                'sold' => 55,
            ],
        ];
    }
}
