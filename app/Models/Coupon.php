<?php

namespace App\Models;

use App\Enums\CouponType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code', 'type', 'value', 'min_order', 'max_discount',
    'starts_at', 'ends_at', 'usage_limit', 'used_count', 'is_active',
])]
class Coupon extends Model
{
    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'value' => 'float',
            'min_order' => 'float',
            'max_discount' => 'float',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function isValidFor(float $subtotal): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return $subtotal >= (float) $this->min_order;
    }

    public function discountFor(float $subtotal): float
    {
        if (! $this->isValidFor($subtotal)) {
            return 0;
        }

        $discount = $this->type === CouponType::Percentage
            ? ($subtotal * $this->value) / 100
            : $this->value;

        if ($this->max_discount) {
            $discount = min($discount, (float) $this->max_discount);
        }

        return round(min($discount, $subtotal), 2);
    }
}
