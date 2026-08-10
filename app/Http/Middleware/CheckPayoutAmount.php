<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates single-payout amount against configured min/max.
 * Independent of daily combined payout limits.
 */
class CheckPayoutAmount
{
    public function handle(Request $request, Closure $next): Response
    {
        $amount = $request->input('amount');

        if (!is_numeric($amount)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Amount is required and must be numeric.',
            ], 422);
        }

        $amountValue = (float) $amount;
        $min = (float) config('payout.limits.amount_min', 100);
        $max = (float) config('payout.limits.amount_max', 50000);

        if ($amountValue < $min || $amountValue > $max) {
            return response()->json([
                'status' => 'error',
                'message' => "Invalid payout amount. Allowed range: {$min} to {$max}.",
            ], 422);
        }

        return $next($request);
    }
}
