<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\BlockedNumber;
use App\Models\User;
use App\Service\PaymentService;
use Illuminate\Support\Facades\Log;

class CheckedBlockedNumbersMiddleware
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function handle(Request $request, Closure $next)
    {
        $phone = $request->phone;
        $paymentMethod = $request->payment_method;

        if ($phone && $paymentMethod) {
            $blocked = BlockedNumber::where('phone_number', $phone)
                ->where('payment_method', $paymentMethod)
                ->first();

            if ($blocked) {
                // If number is currently blocked
                if ($blocked->is_permanent || ($blocked->block_until && $blocked->block_until > now())) {
                    try {
                        Log::channel('payout')->warning('Blocked number attempt', [
                            'phone' => $phone,
                            'payment_method' => $paymentMethod,
                            'client_email' => $request->client_email,
                            'attempt_count' => $blocked->attempt_count,
                            'blocked_attempt_count' => $blocked->blocked_attempt_count + 1,
                            'block_until' => $blocked->block_until,
                            'is_permanent' => $blocked->is_permanent,
                            'request_params' => $request->all(),
                            'timestamp' => now()
                        ]);

                        // Try to get user model from request or fetch from database
                        if (!$request->user_model && $request->client_email) {
                            try {
                                $user = User::where('email', $request->client_email)->first();
                                if ($user) {
                                    $request->merge(['user_model' => $user]);
                                } else {
                                    Log::channel('error')->warning('User not found for email', [
                                        'email' => $request->client_email,
                                        'phone' => $phone,
                                        'payment_method' => $paymentMethod
                                    ]);
                                }
                            } catch (\Exception $e) {
                                Log::channel('error')->error('Error fetching user by email', [
                                    'email' => $request->client_email,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }

                        // Create transaction for blocked number if we have a valid user
                        if ($request->user_model) {
                            $this->paymentService->createBlockedTransaction($request, $paymentMethod);
                            
                            // Update blocked number with user ID if not set
                            if (!$blocked->user_id && $request->user_model->id) {
                                $blocked->update(['user_id' => $request->user_model->id]);
                            }
                        } else {
                            Log::channel('error')->error('Failed to create blocked transaction - no user model', [
                                'phone' => $phone,
                                'payment_method' => $paymentMethod,
                                'request_params' => $request->all()
                            ]);
                        }

                        // Increment attempt count and update block duration
                        $attemptCount = $blocked->attempt_count + 1;
                        
                        if (!$blocked->is_permanent) {
                            $duration = BlockedNumber::getBlockDuration($attemptCount);
                            $blocked->update([
                                'attempt_count' => $attemptCount,
                                'block_until' => $duration === PHP_INT_MAX ? null : now()->addHours($duration),
                                'is_permanent' => $duration === PHP_INT_MAX,
                                'blocked_attempt_count' => $blocked->blocked_attempt_count + 1
                            ]);
                        } else {
                            // If permanently blocked, just increment the attempt count
                            $blocked->update([
                                'attempt_count' => $attemptCount,
                                'blocked_attempt_count' => $blocked->blocked_attempt_count + 1
                            ]);
                        }

                        return response()->json([
                            'status' => 'error',
                            'message' => 'This number is currently blocked. Please try again later.',
                        ], 400);
                    } catch (\Exception $e) {
                        Log::channel('error')->error('Error processing blocked number', [
                            'phone' => $phone,
                            'payment_method' => $paymentMethod,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);

                        return response()->json([
                            'status' => 'error',
                            'message' => 'An error occurred while processing your request.',
                        ], 500);
                    }
                }
            }
        }

        return $next($request);
    }
} 