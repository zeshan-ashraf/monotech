<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckPayoutDailyLimit
{
    /**
     * Maximum total successful payout amount allowed per calendar day.
     */
    private const DAILY_LIMIT = 9950000;

    /**
     * Cache key for today's aggregated successful payout total.
     */
    private const CACHE_KEY_PREFIX = 'payout_daily_total_today';

    /**
     * Cache lifetime in seconds (3 minutes); expires automatically and refreshes from DB.
     */
    private const CACHE_TTL_SECONDS = 180;

    /**
     * Aggregates today's successful payouts from live and archive tables.
     */
    private const TODAY_TOTAL_SQL = <<<'SQL'
SELECT SUM(total_amount) AS grand_total_today
FROM (
    SELECT SUM(CAST(amount AS DECIMAL(15,2))) AS total_amount
    FROM payouts
    WHERE status = 'success'
    AND transaction_type = 'easypaisa'
    AND DATE(created_at) = CURDATE()

    UNION ALL

    SELECT SUM(CAST(amount AS DECIMAL(15,2))) AS total_amount
    FROM archeive_payouts
    WHERE status = 'success'
    AND transaction_type = 'easypaisa'
    AND DATE(created_at) = CURDATE()
) AS combined_totals;
SQL;

    /**
     * Block payout requests when the daily successful payout total reaches the configured limit.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $totalToday = $this->resolveTodaySuccessfulPayoutTotal();
        } catch (Throwable $exception) {
            Log::channel('payout')->error('Payout daily limit check failed', [
                'message' => $exception->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => false,
                'status_code' => 503,
                'message' => 'Unable to verify payout limit at this time.',
                'description' => 'Please try again shortly.',
            ], 503);
        }

        if ($totalToday >= self::DAILY_LIMIT) {
            Log::channel('payout')->warning('Payout daily limit exceeded', [
                'total_today' => $totalToday,
                'daily_limit' => self::DAILY_LIMIT,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => false,
                'status_code' => 429,
                'message' => "PAYOUT_DAILY_LIMIT_BREACHED.",
                'description' => 'Please try after 12:00am tonight',
            ], 429);
        }

        return $next($request);
    }

    /**
     * Return cached today's total, refreshing from the database every 3 minutes.
     */
    private function resolveTodaySuccessfulPayoutTotal(): float
    {
        $total = Cache::remember(
            self::CACHE_KEY_PREFIX . ':' . now()->toDateString(),
            self::CACHE_TTL_SECONDS,
            fn (): float => $this->fetchTodaySuccessfulPayoutTotalFromDatabase()
        );

        return (float) $total;
    }

    /**
     * Execute the combined payouts + archive query for today's successful amounts.
     */
    private function fetchTodaySuccessfulPayoutTotalFromDatabase(): float
    {
        $grandTotal = DB::scalar(self::TODAY_TOTAL_SQL);

        if ($grandTotal === null) {
            return 0.0;
        }

        return (float) $grandTotal;
    }
}
