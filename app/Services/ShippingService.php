<?php

namespace App\Services;

class ShippingService
{
    public function charge(float $payableSubtotal): float
    {
        $freeFrom = (float) setting('free_shipping_amount', 999);
        $default = (float) setting('default_delivery_charge', 49);

        if ($freeFrom > 0 && $payableSubtotal >= $freeFrom) {
            return 0;
        }

        return max(0, $default);
    }
}
