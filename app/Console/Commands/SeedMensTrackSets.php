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

#[Signature('shop:seed-mens-track-sets {--force : Recreate products even if slugs already exist}')]
#[Description('Add the mens track-set listings (Fashion > Mens Clothing) with photos and offer price ₹149')]
class SeedMensTrackSets extends Command
{
    public function handle(ImageService $images): int
    {
        $fashion = Category::query()->where('slug', 'fashion')->first();
        $mens = Category::query()->where('slug', 'mens-clothing')->first();

        if (! $fashion || ! $mens) {
            $this->error('Fashion / Men\'s Clothing categories missing. Run db:seed first.');

            return self::FAILURE;
        }

        $adidas = Brand::query()->firstOrCreate(
            ['slug' => 'adidas'],
            ['name' => 'Adidas', 'is_active' => true],
        );
        $nike = Brand::query()->firstOrCreate(
            ['slug' => 'nike'],
            ['name' => 'Nike', 'is_active' => true],
        );

        $sizeValues = $this->ensureSizeValues();

        Product::query()
            ->where('sub_category_id', $mens->id)
            ->whereNotIn('slug', collect($this->items($adidas, $nike))->pluck('slug'))
            ->update(['is_featured' => false]);

        Storage::disk('public')->makeDirectory('products');

        $dataDir = database_path('data/mens-track-sets');
        $created = 0;
        $updated = 0;

        foreach ($this->items($adidas, $nike) as $index => $row) {
            $imagePath = $dataDir.'/'.$row['image'];
            if (! is_file($imagePath)) {
                $this->error('Missing image: '.$imagePath);

                return self::FAILURE;
            }

            $existing = Product::query()->where('slug', $row['slug'])->first();

            if ($existing && ! $this->option('force')) {
                $existing->update([
                    'brand_id' => $row['brand']->id,
                    'mrp' => $row['mrp'],
                    'selling_price' => 149,
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
                'sub_category_id' => $mens->id,
                'brand_id' => $row['brand']->id,
                'name' => $row['name'],
                'slug' => $row['slug'],
                'sku' => 'MS'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'short_description' => $row['short'],
                'description' => $row['desc'],
                'specifications' => $row['specs'],
                'shipping_info' => 'Dispatched in 24-48 hours. Delivered in 2-5 days.',
                'return_info' => '7-day return on unused products with original tags.',
                'mrp' => $row['mrp'],
                'selling_price' => 149,
                'stock_quantity' => 50,
                'min_order_qty' => 1,
                'max_order_qty' => 5,
                'status' => ProductStatus::Active,
                'is_featured' => true,
                'is_best_seller' => true,
                'is_new_arrival' => true,
                'seo_title' => $row['name'].' | Mens Clothing',
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

            $this->info('Created: '.$product->name.' ('.$row['brand']->name.') ₹149 | sizes S–XXXL');
            $created++;
        }

        $this->newLine();
        $this->info("Done. Created {$created}, updated {$updated}.");
        $this->comment('Open: /fashion/mens-clothing');

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
     *     brand: Brand,
     *     image: string,
     *     mrp: int,
     *     short: string,
     *     desc: string,
     *     specs: list<array{label: string, value: string}>,
     *     tags: string,
     *     sold: int
     * }>
     */
    private function items(Brand $adidas, Brand $nike): array
    {
        return [
            [
                'name' => 'Mens Black Graphic Tee & Grey Track Pant Set',
                'slug' => 'mens-black-graphic-tee-grey-track-pant-set',
                'brand' => $adidas,
                'image' => '01-black-grey.jpg',
                'mrp' => 2499,
                'short' => 'Solid black crew tee with a light grey geometric print, paired with heather grey joggers. Easy everyday set for gym, travel or casual outings.',
                'desc' => 'Got this combo in because it works for both workout days and lazy weekends. Soft black short-sleeve tee with a big grey geometric design on the chest — looks clean without being loud. Pants are light grey with an elastic waist and drawstring, side pockets, and a cream side stripe down each leg. Fabric feels light and dries quick. Size shown in photos is M. Wash separately first time, mild detergent, hang dry.',
                'specs' => [
                    ['label' => 'Set includes', 'value' => '1 T-shirt + 1 track pant'],
                    ['label' => 'Fit', 'value' => 'Regular / comfort'],
                    ['label' => 'Neck', 'value' => 'Crew neck'],
                    ['label' => 'Care', 'value' => 'Machine wash cold, hang dry'],
                ],
                'tags' => 'mens set, track pant, graphic tee, athleisure',
                'sold' => 86,
            ],
            [
                'name' => 'Mens Red Graphic Tee & Black Track Pant Set',
                'slug' => 'mens-red-graphic-tee-black-track-pant-set',
                'brand' => $adidas,
                'image' => '02-red-black.jpg',
                'mrp' => 2499,
                'short' => 'Bright red crew tee with a geometric chest print, matched with black track pants and a white side stripe. Sporty look that still works for daily wear.',
                'desc' => 'Red and black always looks sharp — that is why we put this set up. Tee is short sleeve, crew neck, with a large light-blue outlined geometric print across the front. Pants are solid black, elastic waist with drawstring, side pockets, and a white vertical stripe on the outer leg. Comfortable straight athletic fit. Good for morning walks, gym, or just hanging out. Size in photo is M. Do not use bleach; reverse wash recommended for the print.',
                'specs' => [
                    ['label' => 'Set includes', 'value' => '1 T-shirt + 1 track pant'],
                    ['label' => 'Fit', 'value' => 'Athletic / regular'],
                    ['label' => 'Neck', 'value' => 'Crew neck'],
                    ['label' => 'Care', 'value' => 'Cold wash, reverse side for print'],
                ],
                'tags' => 'mens set, red tee, black track pant, sportswear',
                'sold' => 79,
            ],
            [
                'name' => 'Mens Black Tee & Beige Track Pant Set',
                'slug' => 'mens-black-tee-beige-track-pant-set',
                'brand' => $nike,
                'image' => '03-black-beige.jpg',
                'mrp' => 2699,
                'short' => 'Black moisture-wicking crew tee with a textured chest print, plus sand beige joggers with zip pockets. Clean street-ready combo.',
                'desc' => 'Simple black and beige pairing that looks expensive in photos. Tee is soft performance fabric, crew neck, with a large cream-and-black textured print on the chest. Pants are beige/sand colour, elastic waist with black drawstring, zippered side pockets, and a dark side stripe with lettering. Comfort fit on the tee, adjustable fit on the pants. Ideal for travel days and casual evenings. Size tagged M. Wash in cold water; zip pockets before wash.',
                'specs' => [
                    ['label' => 'Set includes', 'value' => '1 T-shirt + 1 track pant'],
                    ['label' => 'Fabric feel', 'value' => 'Lightweight performance'],
                    ['label' => 'Pockets', 'value' => 'Zippered side pockets'],
                    ['label' => 'Care', 'value' => 'Machine wash cold'],
                ],
                'tags' => 'mens set, beige jogger, black tee, casual wear',
                'sold' => 74,
            ],
            [
                'name' => 'Mens Red Performance Tee & Off-White Pant Set',
                'slug' => 'mens-red-performance-tee-off-white-pant-set',
                'brand' => $nike,
                'image' => '04-red-offwhite.jpg',
                'mrp' => 2599,
                'short' => 'Vibrant red crew tee with a small chest mark, paired with off-white relaxed track pants. Fresh contrast set for summer and gym.',
                'desc' => 'Liked this one for the colour contrast — bright red top with soft off-white pants. Tee is breathable performance fabric, short sleeve, crew neck, small white mark on the left chest. Pants have elastic waist, drawstring, side pockets, and a relaxed wide-ish fit that sits well with sneakers. Comes with detail shots so you can see neck, fabric, waistband and pockets clearly. Size M as shown. Mild wash, avoid high heat iron on the print.',
                'specs' => [
                    ['label' => 'Set includes', 'value' => '1 T-shirt + 1 track pant'],
                    ['label' => 'Fit', 'value' => 'Comfort tee / relaxed pant'],
                    ['label' => 'Neck', 'value' => 'Crew neck'],
                    ['label' => 'Care', 'value' => 'Gentle wash, low iron'],
                ],
                'tags' => 'mens set, red tee, off white pant, summer wear',
                'sold' => 68,
            ],
            [
                'name' => 'Mens Lime Green Tee & Black Camo Stripe Pant Set',
                'slug' => 'mens-lime-green-tee-black-camo-stripe-pant-set',
                'brand' => $nike,
                'image' => '05-lime-black.jpg',
                'mrp' => 2599,
                'short' => 'Lime green crew tee with a bold white chest print, matched with black pants featuring a beige camo-marble side stripe.',
                'desc' => 'Standout colour without looking weird. Tee is light lime/sage green, short sleeve, big white print across the chest, moisture-wicking feel. Pants are black with drawstring waist, thigh mark, and a beige-cream camo style stripe running down the outer leg — that stripe is what makes the set look different from basic tracksuits. Straight athletic fit. Good pickup if you want something you will actually wear outside the house. Size M. Wash colours separately first 2 washes.',
                'specs' => [
                    ['label' => 'Set includes', 'value' => '1 T-shirt + 1 track pant'],
                    ['label' => 'Fit', 'value' => 'Athletic'],
                    ['label' => 'Highlight', 'value' => 'Camo marble side stripe'],
                    ['label' => 'Care', 'value' => 'Wash separately first times'],
                ],
                'tags' => 'mens set, lime tee, black pant, streetwear',
                'sold' => 71,
            ],
            [
                'name' => 'Mens White Geometric Tee & Black Three Stripe Pant Set',
                'slug' => 'mens-white-geometric-tee-black-three-stripe-pant-set',
                'brand' => $adidas,
                'image' => '06-white-black.jpg',
                'mrp' => 2499,
                'short' => 'Crisp white crew tee with a neon-outlined geometric chest print, plus classic black track pants with white side stripes.',
                'desc' => 'Clean white and black set — always easy to style. Tee has short sleeves, crew neck, and a slanted grey geometric panel print with thin neon lime outlines. Pants are black track style with elastic waist, drawstring, side pocket, white three-stripe detail on the legs and a small mark on the thigh. Smooth track fabric, classic athletic look. Wear for jog, college, or evening walks. Size in image is M. Prefer reverse wash so the white tee stays bright longer.',
                'specs' => [
                    ['label' => 'Set includes', 'value' => '1 T-shirt + 1 track pant'],
                    ['label' => 'Fit', 'value' => 'Regular athletic'],
                    ['label' => 'Neck', 'value' => 'Crew neck'],
                    ['label' => 'Care', 'value' => 'Reverse wash, hang dry'],
                ],
                'tags' => 'mens set, white tee, black track pant, geometric print',
                'sold' => 82,
            ],
        ];
    }
}
