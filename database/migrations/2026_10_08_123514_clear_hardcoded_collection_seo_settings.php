<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Default SEO copy now follows the active categories, so stored text naming specific collections is cleared.
     */
    public function up(): void
    {
        DB::table('settings')
            ->whereIn('key', ['seo_title', 'seo_description'])
            ->where(fn ($query) => $query->where('value', 'like', '%Lustre%')->orWhere('value', 'like', '%Stride%'))
            ->update(['value' => null, 'updated_at' => now()]);

        Cache::forget('shop.settings');
    }

    public function down(): void
    {
        //
    }
};
