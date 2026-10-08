<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'category_id', 'sub_category_id', 'brand_id', 'name', 'slug', 'sku',
    'short_description', 'description', 'specifications', 'shipping_info',
    'return_info', 'mrp', 'selling_price', 'stock_quantity', 'min_order_qty',
    'max_order_qty', 'status', 'is_featured', 'is_best_seller', 'is_new_arrival',
    'video_url', 'seo_title', 'seo_description', 'seo_keywords', 'sold_count',
    'avg_rating', 'reviews_count',
])]
class Product extends Model
{
    use HasTags;

    protected function casts(): array
    {
        return [
            'mrp' => 'float',
            'selling_price' => 'float',
            'specifications' => 'array',
            'is_featured' => 'boolean',
            'is_best_seller' => 'boolean',
            'is_new_arrival' => 'boolean',
            'status' => ProductStatus::class,
            'avg_rating' => 'float',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'sub_category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('display_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function activeVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->where('is_active', true);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_approved', true)->latest();
    }

    /**
     * Products are only visible on the storefront while their category (and sub-category, if any) is active.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Active)
            ->whereHas('category', fn (Builder $category) => $category->active())
            ->where(fn (Builder $visible) => $visible
                ->whereNull('sub_category_id')
                ->orWhereHas('subCategory', fn (Builder $subCategory) => $subCategory->active()));
    }

    public function isVisible(): bool
    {
        return $this->status === ProductStatus::Active
            && $this->category?->is_active
            && (! $this->sub_category_id || $this->subCategory?->is_active);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function url(): string
    {
        return route('product.show', $this);
    }

    public function discountPercent(): int
    {
        return discount_percent($this->mrp, $this->selling_price);
    }

    public function inStock(): bool
    {
        if ($this->activeVariants()->exists()) {
            return $this->activeVariants()->sum('stock') > 0;
        }

        return $this->stock_quantity > 0;
    }

    public function availableStock(?int $variantId = null): int
    {
        if ($variantId) {
            return (int) $this->variants()->where('id', $variantId)->value('stock');
        }

        if ($this->relationLoaded('activeVariants') ? $this->activeVariants->isNotEmpty() : $this->activeVariants()->exists()) {
            return (int) $this->activeVariants->sum('stock');
        }

        return (int) $this->stock_quantity;
    }

    /**
     * When variants exist, product stock_quantity mirrors their combined stock.
     */
    public function recalculateStockFromVariants(): void
    {
        if (! $this->variants()->exists()) {
            return;
        }

        $this->forceFill([
            'stock_quantity' => (int) $this->variants()->sum('stock'),
        ])->save();
    }

    public function displayImage(): ?ProductImage
    {
        return $this->primaryImage ?: $this->images->first();
    }

    public function thumbUrl(): string
    {
        $image = $this->displayImage();

        return $image ? $image->url('thumb') : asset('images/product-placeholder.svg');
    }

    public function leafCategory(): ?Category
    {
        return $this->subCategory ?: $this->category;
    }
}
