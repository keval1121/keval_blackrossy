<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['coupon_id', 'order_id', 'mobile', 'discount_amount'])]
class CouponUsage extends Model
{
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
