<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\PayoutLimitService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks payout requests that would exceed the merchant+phone remaining daily limit.
 */
class CheckPayoutDailyLimit
{
    public function __construct(
        private readonly PayoutLimitService $payoutLimitService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->resolveClientUser($request);
        if ($user === null) {
            return $next($request);
        }

        $phone = trim((string) $request->input('phone', ''));
        if ($phone === '') {
            return $next($request);
        }

        $amount = $request->input('amount');
        if (!is_numeric($amount)) {
            return $next($request);
        }

        if ($this->payoutLimitService->wouldExceedDailyLimit($user, $phone, (float) $amount)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Daily payout limit exceeded.',
            ], 429);
        }

        return $next($request);
    }

    private function resolveClientUser(Request $request): ?User
    {
        $user = $request->user_model ?? $request->user;
        if (!$user instanceof User && $request->filled('client_email')) {
            $email = strtolower(trim((string) $request->input('client_email')));
            if ($email !== '') {
                $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
            }
        }

        return $user instanceof User ? $user : null;
    }
}
