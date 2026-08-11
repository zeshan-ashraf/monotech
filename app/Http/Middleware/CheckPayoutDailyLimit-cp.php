<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckPayoutDailyLimit
{
    /**
     * Maximum total successful payout amount allowed per calendar day.
     */
    private const DAILY_LIMIT = 99950000;

    /**
     * Cache key for today's aggregated successful payout total.
     */
    private const CACHE_KEY_PREFIX = 'payout_daily_total_today';

    /**
     * Cache lifetime in seconds; daily total does not need per-request freshness.
     */
    private const CACHE_TTL_SECONDS = 600;

    /**
     * Long-lived fallback when refresh is slow or contended.
     */
    private const STALE_CACHE_TTL_SECONDS = 86400;

    /**
     * Lock lifetime while one worker refreshes the total from DB.
     */
    private const LOCK_SECONDS = 120;

    /**
     * Max seconds other requests wait for the refresh lock holder.
     */
    private const LOCK_WAIT_SECONDS = 30;

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
     * Return cached today's total; only one request refreshes from DB at a time.
     */
    private function resolveTodaySuccessfulPayoutTotal(): float
    {
        $cacheKey = $this->cacheKeyForToday();

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return (float) $cached;
        }

        try {
            return Cache::lock($cacheKey . ':lock', self::LOCK_SECONDS)->block(
                self::LOCK_WAIT_SECONDS,
                function () use ($cacheKey): float {
                    $cached = Cache::get($cacheKey);
                    if ($cached !== null) {
                        return (float) $cached;
                    }

                    $total = $this->fetchTodaySuccessfulPayoutTotalFromDatabase();
                    $this->storeTotalsInCache($cacheKey, $total);

                    return $total;
                }
            );
        } catch (LockTimeoutException) {
            $stale = Cache::get($cacheKey . ':stale');
            if ($stale !== null) {
                Log::channel('payout')->warning('Payout daily limit using stale cache after lock timeout');

                return (float) $stale;
            }

            throw new \RuntimeException('Unable to acquire payout daily total lock.');
        }
    }

    private function cacheKeyForToday(): string
    {
        return self::CACHE_KEY_PREFIX . ':' . now()->toDateString();
    }

    private function storeTotalsInCache(string $cacheKey, float $total): void
    {
        Cache::put($cacheKey, $total, self::CACHE_TTL_SECONDS);
        Cache::put($cacheKey . ':stale', $total, self::STALE_CACHE_TTL_SECONDS);
    }

    /**
     * Sum today's successful EasyPaisa payouts from live + archive tables.
     * Uses a created_at range so MySQL can use indexes (avoids DATE(created_at)).
     */
    private function fetchTodaySuccessfulPayoutTotalFromDatabase(): float
    {
        $start = Carbon::today();
        $end = Carbon::tomorrow();

        $liveTotal = DB::table('payouts')
            ->where('status', 'success')
            ->where('transaction_type', 'easypaisa')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->sum(DB::raw('CAST(amount AS DECIMAL(15,2))'));

        $archiveTotal = DB::table('archeive_payouts')
            ->where('status', 'success')
            ->where('transaction_type', 'easypaisa')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->sum(DB::raw('CAST(amount AS DECIMAL(15,2))'));

        return (float) ($liveTotal + $archiveTotal);
    }
}
