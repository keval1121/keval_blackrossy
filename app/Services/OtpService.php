<?php

namespace App\Services;

use App\Models\BlockedMobile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OtpService
{
    public function enabled(): bool
    {
        return (bool) setting('otp_enabled', false);
    }

    public function send(string $mobile): string
    {
        $mobile = $this->normalize($mobile);
        $key = $this->key($mobile);
        $lockKey = 'otp.lock.'.$mobile;

        if (Cache::has($lockKey)) {
            throw new \RuntimeException('Please wait a minute before requesting another OTP.');
        }

        $code = (string) random_int(100000, 999999);
        Cache::put($key, $code, now()->addMinutes(config('shop.otp_ttl_minutes')));
        Cache::put($lockKey, 1, now()->addMinute());

        Log::info('Checkout OTP generated', ['mobile_suffix' => substr($mobile, -4)]);

        return $code;
    }

    public function verify(string $mobile, string $code): bool
    {
        $mobile = $this->normalize($mobile);
        $cached = Cache::get($this->key($mobile));

        if (! $cached || ! hash_equals((string) $cached, trim($code))) {
            return false;
        }

        Cache::forget($this->key($mobile));
        Cache::put($this->verifiedKey($mobile), true, now()->addMinutes(30));

        return true;
    }

    public function isVerified(string $mobile): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        return (bool) Cache::get($this->verifiedKey($this->normalize($mobile)));
    }

    public function normalize(string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', $mobile) ?: '';

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }

    public function assertNotBlocked(string $mobile): void
    {
        if (BlockedMobile::query()->where('mobile', $this->normalize($mobile))->exists()) {
            throw new \RuntimeException('Orders cannot be placed with this mobile number. Please contact support.');
        }
    }

    private function key(string $mobile): string
    {
        return 'otp.code.'.$mobile;
    }

    private function verifiedKey(string $mobile): string
    {
        return 'otp.ok.'.$mobile;
    }
}
