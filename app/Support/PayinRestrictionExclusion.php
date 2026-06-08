<?php

namespace App\Support;

use App\Services\PhoneVerificationService;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Central bypass rules for payin phone cooldown / restriction checks.
 */
class PayinRestrictionExclusion
{
    /**
     * True when client_email or phone is listed in config/throttle_phone.php exclusions.
     */
    public static function shouldBypass(Request $request): bool
    {
        return self::isExcludedEmail($request->input('client_email'))
            || self::isExcludedPhone($request->input('phone'));
    }

    public static function isExcludedEmail(mixed $email): bool
    {
        if (! is_string($email) || trim($email) === '') {
            return false;
        }

        $normalized = strtolower(trim($email));
        $excluded = self::normalizedEmails();

        return $excluded !== [] && in_array($normalized, $excluded, true);
    }

    public static function isExcludedPhone(mixed $phone): bool
    {
        if (! is_string($phone) || trim($phone) === '') {
            return false;
        }

        $normalized = self::normalizePhoneForComparison($phone);
        if ($normalized === null) {
            return false;
        }

        $excluded = self::normalizedPhones();

        return $excluded !== [] && in_array($normalized, $excluded, true);
    }

    /**
     * Normalize to local 03XXXXXXXXX for consistent matching.
     */
    public static function normalizePhoneForComparison(string $phone): ?string
    {
        try {
            return app(PhoneVerificationService::class)->normalizePhone($phone);
        } catch (InvalidArgumentException) {
            $digitsOnly = preg_replace('/\D+/', '', $phone) ?? '';

            if ($digitsOnly === '') {
                return null;
            }

            if (str_starts_with($digitsOnly, '92')) {
                $digitsOnly = '0' . substr($digitsOnly, 2);
            }

            return preg_match('/^03\d{9}$/', $digitsOnly) ? $digitsOnly : null;
        }
    }

    /**
     * @return list<string>
     */
    private static function normalizedEmails(): array
    {
        return array_values(array_filter(array_map(
            static fn ($email): string => strtolower(trim((string) $email)),
            config('throttle_phone.excluded_emails', [])
        )));
    }

    /**
     * @return list<string>
     */
    private static function normalizedPhones(): array
    {
        $phones = [];

        foreach (config('throttle_phone.excluded_phones', []) as $phone) {
            $normalized = self::normalizePhoneForComparison((string) $phone);
            if ($normalized !== null) {
                $phones[] = $normalized;
            }
        }

        return array_values(array_unique($phones));
    }
}
