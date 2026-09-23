<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

#[Fillable([
    'parent_id', 'name', 'slug', 'image', 'description', 'seo_content',
    'seo_title', 'seo_description', 'seo_keywords', 'is_active',
    'show_on_homepage', 'display_order',
])]
class Category extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_on_homepage' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('shop.nav.category_ids'));
        static::deleted(fn () => Cache::forget('shop.nav.category_ids'));
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('display_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function subCategoryProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'sub_category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeParents(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function url(): string
    {
        if ($this->parent) {
            return url('/'.$this->parent->slug.'/'.$this->slug);
        }

        return url('/'.$this->slug);
    }

    public function imageUrl(): string
    {
        if ($this->image && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->image)) {
            return asset('storage/'.$this->image);
        }

        return asset('images/category-placeholder.svg');
    }
}
