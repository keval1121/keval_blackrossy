<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cod = 'cod';

    public function label(): string
    {
        return 'Cash on Delivery';
    }
}
