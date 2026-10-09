<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The public journal is retired so AdSense and search engines do not see an empty blog.
     */
    public function up(): void
    {
        DB::table('ads')->whereIn('position', ['blog_middle', 'blog_bottom'])->delete();
        DB::table('homepage_sections')->where('key', 'blog')->delete();
        DB::table('blogs')->update([
            'is_published' => false,
            'updated_at' => now(),
        ]);

        Cache::forget('shop.ads');
    }

    public function down(): void
    {
        //
    }
};
