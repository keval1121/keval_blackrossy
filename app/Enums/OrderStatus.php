<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Packed = 'packed';
    case Shipped = 'shipped';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Packed => 'Packed',
            self::Shipped => 'Shipped',
            self::OutForDelivery => 'Out for Delivery',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
            self::Returned => 'Returned',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Confirmed => 'sky',
            self::Packed => 'indigo',
            self::Shipped => 'violet',
            self::OutForDelivery => 'blue',
            self::Delivered => 'emerald',
            self::Cancelled => 'rose',
            self::Returned => 'orange',
        };
    }

    public function step(): int
    {
        return match ($this) {
            self::Pending => 1,
            self::Confirmed => 2,
            self::Packed => 3,
            self::Shipped => 4,
            self::OutForDelivery => 5,
            self::Delivered => 6,
            self::Cancelled, self::Returned => 0,
        };
    }

    public static function flow(): array
    {
        return [
            self::Pending,
            self::Confirmed,
            self::Packed,
            self::Shipped,
            self::OutForDelivery,
            self::Delivered,
        ];
    }

    public function restoresStock(): bool
    {
        return in_array($this, [self::Cancelled, self::Returned], true);
    }
}
