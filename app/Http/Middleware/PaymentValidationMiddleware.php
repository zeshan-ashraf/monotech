<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Helpers\GatewayMetricHelper;
use App\Services\Dashboard\PayinCheckoutMetricsRecorder;
use App\Support\PayinAmountRules;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PaymentValidationMiddleware
{
    public function __construct(
        private readonly PayinCheckoutMetricsRecorder $checkoutMetrics
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        $messages = [
            'phone.regex' => 'Invalid phone number, must be 11 digit.',
            'callback_url.starts_with' => 'Callback URL must start with https:// for security.',
            'amount.max' => 'Amount exceeds the maximum allowed value.',
            'amount.min' => 'Amount is less then allowed value.',
            'orderId.unique' => 'This order ID has already been used.',
        ];

        $paymentMethod = $request->input('payment_method');

        $preValidator = Validator::make($request->all(), [
            'client_email' => 'required|email:rfc,dns',
            'payment_method' => 'required|in:jazzcash,easypaisa',
        ]);

        if ($preValidator->fails()) {
            return $this->validationErrorResponse($request, $preValidator->errors()->toArray());
        }

        $user = User::query()
            ->where('email', $request->client_email)
            ->with('setting')
            ->first();

        if (!$user) {
            Log::channel('payout')->warning('User not found', [
                'ip' => $request->ip(),
                'client_email' => $request->client_email,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'User not found.',
            ], 404);
        }

        $rules = [
            'client_email' => 'required|email:rfc,dns',
            'payment_method' => 'required|in:jazzcash,easypaisa',
            'phone' => [
                'required',
                'string',
                'regex:/^03[0-9]{9}$/',
            ],
            'callback_url' => 'required|url|starts_with:https://',
            'orderId' => [
                'required',
                'string',
                'max:50',
                Rule::unique('transactions'),
            ],
            'amount' => PayinAmountRules::forPaymentMethod((string) $paymentMethod, $user),
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return $this->validationErrorResponse($request, $validator->errors()->toArray());
        }

        $request->merge(['user_model' => $user]);

        if (GatewayMetricHelper::isPayinCheckoutRequest($request)) {
            $this->checkoutMetrics->recordValidatedRequest(
                $request,
                (string) $paymentMethod
            );
        }

        return $next($request);
    }

    private function validationErrorResponse(Request $request, array $errors)
    {
        Log::channel('payout')->warning('Payment validation failed', [
            'ip' => $request->ip(),
            'client_email' => $request->client_email ?? 'unknown',
            'errors' => $errors,
        ]);

        if (GatewayMetricHelper::isPayinCheckoutRequest($request)) {
            $gateway = (string) $request->input('payment_method', '');
            $startTime = (float) ($request->attributes->get(GatewayMetricHelper::REQUEST_ATTR_START_TIME) ?? microtime(true));

            $this->checkoutMetrics->recordApplicationCheckoutFailure(
                $request,
                $gateway,
                $startTime,
                GatewayMetricHelper::APPLICATION_ERROR_VALIDATION
            );
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $errors,
        ], 422);
    }
}
