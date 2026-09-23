<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'order_number', 'status', 'payment_method', 'payment_status', 'subtotal',
    'discount', 'delivery_charge', 'total', 'coupon_id', 'coupon_code',
    'customer_name', 'mobile', 'email', 'notes', 'ip_address', 'user_agent',
    'otp_verified_at', 'is_suspicious', 'suspicious_reason', 'cancelled_at',
    'cancel_reason', 'delivered_at',
])]
class Order extends Model
{
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'subtotal' => 'float',
            'discount' => 'float',
            'delivery_charge' => 'float',
            'total' => 'float',
            'otp_verified_at' => 'datetime',
            'is_suspicious' => 'boolean',
            'cancelled_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function address(): HasOne
    {
        return $this->hasOne(OrderAddress::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderStatusLog::class)->latest();
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function paymentLabel(): string
    {
        return $this->payment_method->label();
    }
}
