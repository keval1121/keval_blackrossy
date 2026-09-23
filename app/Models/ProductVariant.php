<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['product_id', 'sku', 'price', 'mrp', 'stock', 'image', 'is_active'])]
class ProductVariant extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'float',
            'mrp' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProductVariantValue::class);
    }

    public function sellingPrice(): float
    {
        return (float) ($this->price ?? $this->product->selling_price);
    }

    public function listPrice(): float
    {
        return (float) ($this->mrp ?? $this->product->mrp);
    }

    public function label(): string
    {
        return $this->values
            ->map(fn (ProductVariantValue $value) => $value->attributeValue?->value)
            ->filter()
            ->implode(' / ');
    }

    public function attributeMap(): array
    {
        return $this->values
            ->mapWithKeys(fn (ProductVariantValue $value) => [
                $value->attribute_id => $value->attribute_value_id,
            ])
            ->all();
    }
}
