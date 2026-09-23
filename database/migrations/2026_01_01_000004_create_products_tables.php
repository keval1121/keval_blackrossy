<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('sub_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable()->index();
            $table->string('short_description', 500)->nullable();
            $table->longText('description')->nullable();
            $table->json('specifications')->nullable();
            $table->text('shipping_info')->nullable();
            $table->text('return_info')->nullable();
            $table->decimal('mrp', 10, 2);
            $table->decimal('selling_price', 10, 2)->index();
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedInteger('min_order_qty')->default(1);
            $table->unsignedInteger('max_order_qty')->nullable();
            $table->string('status')->default('active')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_best_seller')->default(false)->index();
            $table->boolean('is_new_arrival')->default(false)->index();
            $table->string('video_url')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_keywords')->nullable();
            $table->unsignedInteger('sold_count')->default(0)->index();
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->timestamps();

            $table->index(['status', 'selling_price']);
            $table->index(['category_id', 'status']);
            $table->index(['sub_category_id', 'status']);
            $table->index('created_at');
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->index();
            $table->string('path_thumb');
            $table->string('path_medium');
            $table->string('path_large');
            $table->string('alt')->nullable();
            $table->boolean('is_primary')->default(false)->index();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->nullable()->index();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('mrp', 10, 2)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_variant_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained()->cascadeOnDelete();
            $table->unique(['product_variant_id', 'attribute_id'], 'variant_attribute_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_values');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
    }
};
