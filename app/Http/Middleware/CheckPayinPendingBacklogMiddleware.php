<?php

namespace App\Http\Middleware;

use App\Models\Transaction;
use App\Support\PayinRestrictionExclusion;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckPayinPendingBacklogMiddleware
{
    /** Reject new payins when pending count reaches this level. */
    private int $stopPendingCount = 500;

    /** Resume accepting payins when pending count falls to this level or below. */
    private int $startPendingCount = 120;

    private string $outageMessage = 'system outage';

    private const OUTAGE_CACHE_PREFIX = 'payin:pending_backlog_outage:';

    public function handle(Request $request, Closure $next): Response
    {
        if (PayinRestrictionExclusion::shouldBypass($request)) {
            return $next($request);
        }

        $paymentMethod = $request->input('payment_method');
        if (! in_array($paymentMethod, ['jazzcash', 'easypaisa'], true)) {
            return $next($request);
        }

        $pendingCount = Transaction::query()
            ->where('status', 'pending')
            ->where('txn_type', $paymentMethod)
            ->count();

        $cacheKey = self::OUTAGE_CACHE_PREFIX . $paymentMethod;
        $outageActive = (bool) Cache::get($cacheKey, false);

        if ($outageActive) {
            if ($pendingCount <= $this->startPendingCount) {
                Cache::forget($cacheKey);

                Log::channel('payin')->info('Payin pending backlog outage cleared', [
                    'payment_method' => $paymentMethod,
                    'pending_count' => $pendingCount,
                    'start_pending_count' => $this->startPendingCount,
                ]);
            } else {
                return $this->reject($request, $paymentMethod, $pendingCount, $outageActive);
            }
        } elseif ($pendingCount >= $this->stopPendingCount) {
            Cache::put($cacheKey, true, now()->addDays(7));

            return $this->reject($request, $paymentMethod, $pendingCount, true);
        }

        return $next($request);
    }

    private function reject(
        Request $request,
        string $paymentMethod,
        int $pendingCount,
        bool $outageActive
    ): Response {
        Log::channel('payin')->warning('Payin rejected: pending backlog outage', [
            'payment_method' => $paymentMethod,
            'pending_count' => $pendingCount,
            'stop_pending_count' => $this->stopPendingCount,
            'start_pending_count' => $this->startPendingCount,
            'outage_active' => $outageActive,
            'client_email' => $request->input('client_email'),
            'order_id' => $request->input('orderId'),
        ]);

        return response()->json([
            'status' => 'error',
            'message' => $this->outageMessage,
        ], 503);
    }
}
