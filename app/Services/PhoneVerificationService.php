<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Phone Verification Module Service (reusable, concurrency-safe).
 *
 * - Normalizes all phones to local format: 03XXXXXXXXX
 * - Uses Laravel Cache (no direct Redis usage)
 * - Uses insertOrIgnore for DB writes
 */
final class PhoneVerificationService
{
    private const VERIFIED_CACHE_PREFIX = 'verified_phone:';

    /**
     * Normalize a phone number to local format: 03XXXXXXXXX
     *
     * Rules:
     * - remove all non-digits
     * - if starts with '92' → convert to '0' + rest
     * - ensure final format is 03XXXXXXXXX
     *
     * @throws InvalidArgumentException when phone cannot be normalized
     */
    public function normalizePhone(string $phone): string
    {
        $digitsOnly = preg_replace('/\D+/', '', $phone) ?? '';
        $digitsOnly = trim($digitsOnly);

        if ($digitsOnly === '') {
            throw new InvalidArgumentException('Phone number is empty.');
        }

        if (str_starts_with($digitsOnly, '92')) {
            $digitsOnly = '0' . substr($digitsOnly, 2);
        }

        if (!preg_match('/^03\d{9}$/', $digitsOnly)) {
            throw new InvalidArgumentException('Phone number must be in local format 03XXXXXXXXX after normalization.');
        }

        return $digitsOnly;
    }

    /**
     * Check if the phone number is verified.
     */
    public function isVerified(string $phone): bool
    {
        $normalizedPhone = $this->normalizePhone($phone);
        $cacheKey = self::VERIFIED_CACHE_PREFIX . $normalizedPhone;

        $cached = Cache::get($cacheKey);
        if ($cached === true) {
            return true;
        }

        $exists = DB::table('verified_numbers')
            ->where('phone_number', $normalizedPhone)
            ->exists();

        if (!$exists) {
            return false;
        }

        $ttlHours = (int) config('phone_verification.cache_ttl_hours', 2);
        Cache::put($cacheKey, true, now()->addHours(max(1, $ttlHours)));

        return true;
    }

    /**
     * Mark a phone number as verified (idempotent).
     */
    public function markVerified(string $phone): void
    {
        $normalizedPhone = $this->normalizePhone($phone);

        DB::table('verified_numbers')->insertOrIgnore([
            'phone_number' => $normalizedPhone,
            'verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ttlHours = (int) config('phone_verification.cache_ttl_hours', 2);
        Cache::put(self::VERIFIED_CACHE_PREFIX . $normalizedPhone, true, now()->addHours(max(1, $ttlHours)));
    }
}

