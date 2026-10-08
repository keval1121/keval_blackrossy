<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Placements next to buttons, search boxes or other ads (or not rendered anywhere) are removed.
     */
    public function up(): void
    {
        DB::table('ads')
            ->whereIn('position', ['home_top', 'category_middle', 'category_bottom', 'product_middle', 'search_middle', 'blog_top'])
            ->delete();

        foreach ([
            'home_middle' => 'Homepage — between Trending and Best sellers',
            'home_bottom' => 'Homepage — after the shipping & returns strip',
            'category_top' => 'Category — above the products (desktop only)',
            'listing_middle' => 'Category & search — after the 6th product',
            'listing_bottom' => 'Category — below the category text',
            'product_bottom' => 'Product — after the description',
            'blog_middle' => 'Blog — middle of the article',
            'blog_bottom' => 'Blog — end of the article',
        ] as $position => $name) {
            DB::table('ads')->where('position', $position)->update(['name' => $name, 'updated_at' => now()]);
        }

        Cache::forget('shop.ads');
    }

    public function down(): void
    {
        //
    }
};
