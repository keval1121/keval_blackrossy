<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('homepage_sections')->where('key', 'hero_products')->exists();

        if ($exists) {
            return;
        }

        DB::table('homepage_sections')->insert([
            'key' => 'hero_products',
            'title' => 'Hero products',
            'subtitle' => null,
            'button_text' => null,
            'button_url' => null,
            'image' => null,
            'is_enabled' => true,
            'display_order' => 0,
            'config' => json_encode(['product_ids' => []]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('homepage_sections')->where('key', 'hero_products')->delete();
    }
};
