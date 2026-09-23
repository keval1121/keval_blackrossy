<?php

use App\Models\Ad;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        $settings = Cache::remember('shop.settings', 3600, function () {
            try {
                return Setting::query()->pluck('value', 'key')->all();
            } catch (Throwable) {
                return [];
            }
        });

        if (! array_key_exists($key, $settings)) {
            return $default;
        }

        $value = $settings[$key];

        if (is_string($value) && in_array($value, ['0', '1'], true) && in_array($key, [
            'cod_enabled', 'otp_enabled', 'maintenance_mode', 'coupons_enabled',
            'reviews_enabled', 'adsense_enabled', 'restore_stock_on_cancel',
        ], true)) {
            return $value === '1';
        }

        return $value;
    }
}

if (! function_exists('money')) {
    function money(float|int|string|null $amount): string
    {
        $amount = (float) $amount;
        $symbol = setting('currency_symbol', '₹');

        return $symbol.number_format($amount, $amount == floor($amount) ? 0 : 2);
    }
}

if (! function_exists('discount_percent')) {
    function discount_percent(float|int|null $mrp, float|int|null $price): int
    {
        $mrp = (float) $mrp;
        $price = (float) $price;

        if ($mrp <= 0 || $price >= $mrp) {
            return 0;
        }

        return (int) round((($mrp - $price) / $mrp) * 100);
    }
}

if (! function_exists('ad_code')) {
    function ad_code(string $position): ?string
    {
        // Never render placement ads until explicitly enabled (keeps review-safe).
        if (! setting('adsense_enabled', false)) {
            return null;
        }

        /** @var array<string, string|null> $ads */
        $ads = Cache::remember('shop.ads', 3600, function () {
            try {
                return Ad::query()
                    ->where('is_active', true)
                    ->pluck('code', 'position')
                    ->all();
            } catch (Throwable) {
                return [];
            }
        });

        if (! is_array($ads)) {
            Cache::forget('shop.ads');

            return null;
        }

        $code = $ads[$position] ?? null;
        if (! is_string($code) || trim($code) === '') {
            return null;
        }

        // Reject incomplete snippets (loader-only) — those belong in <head>, not placements.
        if (! str_contains($code, 'data-ad-slot')) {
            return null;
        }

        return $code;
    }
}

if (! function_exists('adsense_client_id')) {
    /**
     * Normalized AdSense client id, e.g. ca-pub-1983284873156439.
     */
    function adsense_client_id(): ?string
    {
        $raw = trim((string) setting('adsense_client_id', ''));
        if ($raw === '') {
            return null;
        }

        if (preg_match('/ca-pub-\d+/i', $raw, $m)) {
            return strtolower($m[0]);
        }

        if (preg_match('/pub-(\d+)/i', $raw, $m)) {
            return 'ca-pub-'.$m[1];
        }

        return null;
    }
}

if (! function_exists('adsense_publisher_id')) {
    /**
     * Publisher id for ads.txt, e.g. pub-1983284873156439.
     */
    function adsense_publisher_id(): ?string
    {
        $client = adsense_client_id();
        if ($client === null) {
            return null;
        }

        return str_replace('ca-', '', $client);
    }
}

if (! function_exists('store_name')) {
    function store_name(): string
    {
        return (string) setting('website_name', config('app.name', 'Black Rossy'));
    }
}

if (! function_exists('mask_mobile')) {
    function mask_mobile(?string $mobile): string
    {
        $mobile = preg_replace('/\D+/', '', (string) $mobile) ?: '';

        if (strlen($mobile) < 4) {
            return '****';
        }

        return str_repeat('*', max(strlen($mobile) - 4, 0)).substr($mobile, -4);
    }
}

if (! function_exists('indian_states')) {
    function indian_states(): array
    {
        return [
            'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh',
            'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka',
            'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram',
            'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu',
            'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal',
            'Andaman and Nicobar Islands', 'Chandigarh', 'Dadra and Nagar Haveli and Daman and Diu',
            'Delhi', 'Jammu and Kashmir', 'Ladakh', 'Lakshadweep', 'Puducherry',
        ];
    }
}
