<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\PhoneVerificationService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Optional route middleware: ensures phone is verified.
 *
 * How to attach middleware to a route:
 * - Route::post('/checkout', ...)->middleware('phone.verified');
 * - Route::middleware(['phone.verified'])->group(function () { ... });
 */
final class EnsurePhoneIsVerified
{
    private const PHONE_LOCK_PREFIX = 'phone_lock:';
    /**
     * Emails that should bypass phone verification completely.
     *
     * Add lowercase emails here to exclude them from the verification gate.
     */
    private const EXCLUDED_EMAILS = [
       'piqpay@novaconnect.com'
    ];

    public function __construct(
        private readonly PhoneVerificationService $phoneVerificationService
    ) {}

    /**
     * @param Request $request
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ((string) $request->input('payment_method', '') !== 'easypaisa') {
            return $next($request);
        }

        // Optional bypass: allow specific users to skip phone verification.
        $userModel = $request->user_model;
        $email = $userModel ? (string) $userModel->email : (string) $request->input('client_email', '');
        $normalizedEmail = strtolower(trim($email));

        $excluded = array_map(static fn (string $e): string => strtolower(trim($e)), self::EXCLUDED_EMAILS);
        if ($normalizedEmail !== '' && in_array($normalizedEmail, $excluded, true)) {
            return $next($request);
        }

        $inputKey = (string) config('phone_verification.phone_input_key', 'phone');
        $rawPhone = (string) $request->input($inputKey, '');

        try {
            $normalizedPhone = $this->phoneVerificationService->normalizePhone($rawPhone);
        } catch (InvalidArgumentException $e) {
            return new JsonResponse(
                ['message' => $e->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $lockSeconds = (int) config('phone_verification.lock_seconds', 5);
        $lock = Cache::lock(self::PHONE_LOCK_PREFIX . $normalizedPhone, max(1, $lockSeconds));

        if (!$lock->get()) {
            return new JsonResponse(
                ['message' => 'Request already processing'],
                Response::HTTP_TOO_MANY_REQUESTS
            );
        }

        try {
            if ($this->phoneVerificationService->isVerified($normalizedPhone )) {
                return $next($request); 
            }
            else if ($request->amount <= 100){
                return $next($request);
            }
            else if ($request->amount > 100){
                return new JsonResponse(['status' => 'new_user'], Response::HTTP_OK);
            }
            return new JsonResponse(['status' => 'new_user'], Response::HTTP_OK);
        } finally {
            optional($lock)->release();
        }
    }
}

