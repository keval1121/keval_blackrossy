<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_id', 'variant_id', 'path_thumb', 'path_medium', 'path_large',
    'alt', 'is_primary', 'display_order',
])]
class ProductImage extends Model
{
    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function url(string $size = 'medium'): string
    {
        $path = match ($size) {
            'thumb' => $this->path_thumb,
            'large' => $this->path_large,
            default => $this->path_medium,
        };

        if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return asset('storage/'.$path);
        }

        return asset('images/product-placeholder.svg');
    }
}
