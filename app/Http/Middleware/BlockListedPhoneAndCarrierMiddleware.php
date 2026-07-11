<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BlockListedPhoneAndCarrierMiddleware
{
    /**
     * Hard-blocked phone numbers.
     * Accepts 03XXXXXXXXX or 92XXXXXXXXXX — matching is normalized.
     *
     * @var array<int, string>
     */
    private array $blockedPhones = [
        "03200166410",
        "03335415336"
    ];

    /**
     * Hard-blocked carriers / payment methods.
     * Allowed values: jazzcash, easypaisa
     *
     * @var array<int, string>
     */
    private array $blockedCarriers = [
        //
    ];

    public function handle(Request $request, Closure $next)
    {
        $phone = (string) $request->input('phone', '');
        $carrier = strtolower((string) (
            $request->input('payment_method')
            ?? $request->input('payout_method')
            ?? ''
        ));

        if ($phone !== '' && $this->isPhoneBlocked($phone)) {
            $this->logBlockedRequest($request, 'phone', $phone, $carrier);

            return response()->json([
                'status' => 'error',
                'message' => 'This phone number is not eligible for payment processing.',
            ], 400);
        }

        if ($carrier !== '' && $this->isCarrierBlocked($carrier)) {
            $this->logBlockedRequest($request, 'carrier', $phone, $carrier);

            return response()->json([
                'status' => 'error',
                'message' => 'This payment method is temporarily unavailable.',
            ], 400);
        }

        return $next($request);
    }

    private function isPhoneBlocked(string $phone): bool
    {
        $normalizedRequestPhone = $this->normalizePhone($phone);

        if ($normalizedRequestPhone === '') {
            return false;
        }

        foreach ($this->blockedPhones as $blockedPhone) {
            if ($this->normalizePhone((string) $blockedPhone) === $normalizedRequestPhone) {
                return true;
            }
        }

        return false;
    }

    private function isCarrierBlocked(string $carrier): bool
    {
        $blocked = array_map('strtolower', $this->blockedCarriers);

        return in_array($carrier, $blocked, true);
    }

    /**
     * Normalize to last 10 digits so 03001234567 and 923001234567 match.
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) < 10) {
            return '';
        }

        return substr($digits, -10);
    }

    private function logBlockedRequest(Request $request, string $reason, string $phone, string $carrier): void
    {
        Log::channel('payout')->warning('Payment blocked by listed phone/carrier middleware', [
            'reason' => $reason,
            'phone' => $phone,
            'carrier' => $carrier !== '' ? $carrier : null,
            'client_email' => $request->input('client_email'),
            'order_id' => $request->input('orderId'),
            'amount' => $request->input('amount'),
            'route' => $request->path(),
            'client_ip' => $request->ip(),
            'request_params' => $request->all(),
        ]);
    }
}
