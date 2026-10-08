<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Category pages no longer show text below the products, so this slot would sit right under the pagination buttons.
     */
    public function up(): void
    {
        DB::table('ads')->where('position', 'listing_bottom')->delete();

        Cache::forget('shop.ads');
    }

    public function down(): void
    {
        //
    }
};
