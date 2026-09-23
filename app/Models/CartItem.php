<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['cart_id', 'product_id', 'product_variant_id', 'quantity'])]
class CartItem extends Model
{
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function unitPrice(): float
    {
        return $this->variant?->sellingPrice() ?? (float) $this->product->selling_price;
    }

    public function lineTotal(): float
    {
        return round($this->unitPrice() * $this->quantity, 2);
    }

    public function availableStock(): int
    {
        return $this->variant?->stock ?? (int) $this->product->stock_quantity;
    }
}
