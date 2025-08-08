<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Models\{User, Transaction};
use Illuminate\Validation\Rule;

class PaymentValidationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
   
		
		$messages = [
			'phone.regex' => 'Invalid phone number, must be 11 digit.',
			'callback_url.starts_with' => 'Callback URL must start with https:// for security.',
			'amount.max' => 'Amount exceeds the maximum allowed value.',
			'amount.min' => 'Amount is less then allowed value.',
			'orderId.unique' => 'This order ID has already been used.'
		];
		
		
		$rules = [
			'client_email' => 'required|email:rfc,dns',
			'payment_method' => 'required|in:jazzcash,easypaisa',
			'phone' => [
				'required',
				'string',
				'regex:/^03[0-9]{9}$/'
			],
			'callback_url' => 'required|url|starts_with:https://',
			'orderId' => [
				'required',
				'string',
				'max:50',
				Rule::unique('transactions')
			]
		];

		// Set amount max based on payment_method
		$paymentMethod = $request->input('payment_method');

		if ($paymentMethod === 'easypaisa') {
			$rules['amount'] = 'required|numeric|min:1|max:100000';
		} elseif ($paymentMethod === 'jazzcash') {
			$rules['amount'] = 'required|numeric|min:1|max:50000';
		} else {
			$rules['amount'] = 'required|numeric|min:1'; // Fallback
		}

		$validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            Log::channel('payout')->warning('Payment validation failed', [
                'ip' => $request->ip(),
                'client_email' => $request->client_email ?? 'unknown',
                'errors' => $validator->errors()->toArray()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        
        // Find user and eager load settings
        $user = User::where('email', $request->client_email)
                    ->with('setting')
                    ->first();
                    
        if (!$user) {
            Log::channel('payout')->warning('User not found', [
                'ip' => $request->ip(),
                'client_email' => $request->client_email
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.',
            ], 404);
        }
        
        // Add user to request for downstream use
        $request->merge(['user_model' => $user]);

        return $next($request);
    }
} 