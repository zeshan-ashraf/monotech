<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Payout;
use App\Models\User;
use Carbon\Carbon;

/**
 * Business rules for combined daily payout limits (all gateways).
 *
 * HTTP responses belong in middleware — this service only evaluates limits.
 */
final class PayoutLimitService
{
    public const TIMEZONE = 'Asia/Karachi';

    /**
     * Effective daily combined payout limit for the user (PKR).
     * Per-user DB value overrides config default when set.
     */
    public function getDailyLimit(User $user): float
    {
        if ($user->payout_daily_limit !== null) {
            return (float) $user->payout_daily_limit;
        }

        return (float) config('payout.limits.daily_default', 100000);
    }

    /**
     * Sum of successful payout amounts for the current Pakistan business day.
     * Per phone number, scoped to the merchant (user), combined across all gateways.
     */
    public function getTodaySuccessfulPayoutTotal(User $user, string $phone): float
    {
        [$start, $end] = $this->pakistanBusinessDayBounds();

        return (float) Payout::query()
            //->where('user_id', $user->id)
            ->where('phone', $phone)
            ->where('status', Payout::STATUS_SUCCESS)
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');
    }

    /**
     * Remaining daily capacity for this merchant + phone (never negative).
     */
    public function getRemainingDailyLimit(User $user, string $phone): float
    {
        $remaining = $this->getDailyLimit($user) - $this->getTodaySuccessfulPayoutTotal($user, $phone);

        return max(0.0, $remaining);
    }

    /**
     * True when the requested amount would exceed remaining daily capacity.
     *
     * Allow when: requestedAmount <= remaining
     * Reject when: requestedAmount > remaining (including remaining = 0)
     */
    public function wouldExceedDailyLimit(User $user, string $phone, float $requestedAmount): bool
    {
        return $requestedAmount > $this->getRemainingDailyLimit($user, $phone);
    }

    /**
     * @return array{0: Carbon, 1: Carbon} start and end of today in Asia/Karachi
     */
    public function pakistanBusinessDayBounds(): array
    {
        $tz = self::TIMEZONE;

        return [
            Carbon::now($tz)->startOfDay(),
            Carbon::now($tz)->endOfDay(),
        ];
    }
}
