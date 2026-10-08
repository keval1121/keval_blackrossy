<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Shipping is free on every order, so the threshold settings and the copy that mentions them are retired.
     */
    public function up(): void
    {
        DB::table('settings')->whereIn('key', ['default_delivery_charge', 'free_shipping_amount'])->delete();

        $replacements = [
            'banners' => [
                'subtitle' => [
                    'On orders above ₹999. Real products, packed with care.' => 'On every order across India. Real products, packed with care.',
                ],
            ],
            'blogs' => [
                'content' => [
                    'Look at selling price and MRP together, and check free-shipping thresholds at checkout so the total stays predictable with COD.' => 'Look at selling price and MRP together — shipping is free on every order, so the total stays predictable with COD.',
                    'Free shipping. Orders above the free-shipping amount shown at checkout save the delivery fee — plan your cart accordingly.' => 'Free shipping. Every order ships free across India — no minimum cart value and no delivery fee at checkout.',
                ],
            ],
            'pages' => [
                'content' => [
                    'A delivery charge may apply if your order is below the free-shipping threshold shown at checkout (store setting: free shipping amount). The exact charge is shown before you confirm the order.' => 'Shipping is free on every order. There is no minimum order value and no delivery charge is added at checkout.',
                    'Delivery charges may be non-refundable for change-of-mind returns.' => 'Shipping is free, so no delivery charge is deducted.',
                    'Free shipping applies on eligible orders above the amount shown on the store (currently orders above ₹999, unless a different amount is published in Settings).' => 'Yes. Shipping is free on every order across India, with no minimum order value.',
                ],
            ],
        ];

        foreach ($replacements as $table => $columns) {
            foreach ($columns as $column => $pairs) {
                foreach (DB::table($table)->get(['id', $column]) as $row) {
                    $updated = strtr((string) $row->{$column}, $pairs);

                    if ($updated !== (string) $row->{$column}) {
                        DB::table($table)->where('id', $row->id)->update([$column => $updated, 'updated_at' => now()]);
                    }
                }
            }
        }

        Cache::forget('shop.settings');
    }

    public function down(): void
    {
        //
    }
};
