<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Product,Client,Transaction,User};
use App\Service\PaymentService;
use App\Traits\HighValueTransactionRestriction;
use Illuminate\Support\Facades\Log;
use Zfhassaan\Easypaisa\Easypaisa;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use DB;
use Ramsey\Uuid\Uuid;

class PayinController extends Controller
{
    use HighValueTransactionRestriction;
    
    public $service;
    protected $logger;

    public function __construct(PaymentService $service)
    {
        $this->service = $service;
        $this->logger = Log::channel('payin');
    }
    
    // Temporary test method to check if trait is working
    public function testTrait()
    {
        \Log::info('Testing trait method');
        
        // Test basic logging first
        \Log::info('Basic log test');
        
        // Test if the method exists
        \Log::info('Method exists check', [
            'method_exists' => method_exists($this, 'checkHighValueTransactionRestriction'),
            'class_methods' => get_class_methods($this)
        ]);
        
        try {
            $result = $this->checkHighValueTransactionRestriction(
                new \Illuminate\Http\Request(['phone' => '03123456789', 'amount' => 60000]), 
                'test_123', 
                microtime(true)
            );
            \Log::info('Trait test result', ['result' => $result]);
            return response()->json(['test_result' => $result, 'success' => true]);
        } catch (\Exception $e) {
            \Log::error('Trait test failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['test_result' => null, 'success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Check for recent transaction restrictions
     * Blocks transactions if there are recent failed (3 min) or successful (5 min) transactions
     * 
     * @param Request $request
     * @param string $requestId
     * @param float $startTime
     * @return array|null Returns restriction response array or null if no restriction
     */
    private function checkRecentTransactionRestriction(Request $request, string $requestId, float $startTime)
    {
        $threeMinutesAgo = now()->subMinutes(3);
        $fiveMinutesAgo = now()->subMinutes(5);
        
        $recentFailedTransaction = Transaction::where('phone', $request->phone)
            ->where('status', 'fail')
            ->where('created_at', '>=', $threeMinutesAgo)
            ->first();
            
        $recentSuccessfulTransaction = Transaction::where('phone', $request->phone)
            ->where('status', 'success')
            ->where('created_at', '>=', $fiveMinutesAgo)
            ->first();
        
        $this->logger->info('Recent transaction check', [
            'request_id' => $requestId,
            'phone' => $request->phone,
            'recent_failed_transaction' => $recentFailedTransaction ? $recentFailedTransaction->toArray() : null,
            'recent_successful_transaction' => $recentSuccessfulTransaction ? $recentSuccessfulTransaction->toArray() : null,
            'three_minutes_ago' => $threeMinutesAgo->toDateTimeString(),
            'five_minutes_ago' => $fiveMinutesAgo->toDateTimeString()
        ]);
        
        if ($recentFailedTransaction) {
            $this->logger->warning('Transaction blocked - recent failed transaction', [
                'request_id' => $requestId,
                'phone' => $request->phone,
                'failed_transaction_id' => $recentFailedTransaction->id,
                'failed_transaction_created_at' => $recentFailedTransaction->created_at->toDateTimeString(),
                'execution_time' => microtime(true) - $startTime
            ]);
            return [
                'status' => 'error',
                'message' => 'Transaction blocked due to recent failed transaction. Please wait 3 minutes before trying again.',
                'code' => 400
            ];
        }
        
        if ($recentSuccessfulTransaction) {
            $this->logger->warning('Transaction blocked - recent successful transaction', [
                'request_id' => $requestId,
                'phone' => $request->phone,
                'successful_transaction_id' => $recentSuccessfulTransaction->id,
                'successful_transaction_created_at' => $recentSuccessfulTransaction->created_at->toDateTimeString(),
                'execution_time' => microtime(true) - $startTime
            ]);
            return [
                'status' => 'error',
                'message' => 'Transaction blocked due to recent successful transaction. Please wait 5 minutes before trying again.',
                'code' => 400
            ];
        }
        
        return null; // No restriction applied
    }

    public function checkout(Request $request)
    {
        $requestId = uniqid('req_');
        $startTime = microtime(true);
        $this->logger->info('Starting checkout process', [
            'request_id' => $requestId,
            'request_data' => $request->all(),
            'timestamp' => now()->toDateTimeString()
        ]);

        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'regex:/^03[0-9]{9}$/'],
            'email' => 'required|email',
            'client_email' => 'required|email',
            'payment_method' => 'required|in:jazzcash,easypaisa',
            'amount' => 'required|numeric|min:1',
        ]);
    
        if ($validator->fails()) {
            $this->logger->warning('Validation failed', [
                'request_id' => $requestId,
                 'request_input'  => $request->all(), // logs all request data
                'errors' => $validator->errors()->toArray(),
                'execution_time' => microtime(true) - $startTime
            ]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check for recent transaction restrictions
        $recentTransactionCheck = $this->checkRecentTransactionRestriction($request, $requestId, $startTime);
        if ($recentTransactionCheck) {
            return response()->json($recentTransactionCheck, $recentTransactionCheck['code']);
        }

        // Check if high-value transaction restriction applies (50000+ transactions within 10 minutes)
        \Log::info('About to call restriction check', [
            'request_id' => $requestId,
            'phone' => $request->phone,
            'amount' => $request->amount,
            'amount_type' => gettype($request->amount)
        ]);
        
        // Check if the trait method exists
        \Log::info('Trait method check', [
            'method_exists' => method_exists($this, 'checkHighValueTransactionRestriction'),
            'available_methods' => get_class_methods($this)
        ]);
        
        $restrictionCheck = $this->checkHighValueTransactionRestriction($request, $requestId, $startTime);
        
        \Log::info('Restriction check completed', [
            'request_id' => $requestId,
            'result' => $restrictionCheck
        ]);
        
        if ($restrictionCheck) {
            \Log::info('Restriction triggered - returning error', [
                'request_id' => $requestId,
                'response' => $restrictionCheck
            ]);
            return response()->json($restrictionCheck, $restrictionCheck['code']);
        }

        $user = User::where('email', $request->client_email)->first();
        
        
        
        if(($request->payment_method == "jazzcash" && $user->jc_api == 0) || ($request->payment_method == "easypaisa" && $user->ep_api == 0)){
            $this->logger->warning('API suspended', [
                'request_id' => $requestId,
                'user_email' => $request->client_email,
                'payment_method' => $request->payment_method,
                'execution_time' => microtime(true) - $startTime
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Api suspended by administrator.',
            ], 400);
        }
        else{
            // Check daily limit for JazzCash payments for user ID 2
            if ($request->payment_method == "jazzcash" && $user->id == 2) {
                $todayStart = now()->startOfDay();
                $todayEnd = now()->endOfDay();
                
                $todayTransactionsSum = Transaction::where('user_id', $user->id)
                    ->whereBetween('created_at', [$todayStart, $todayEnd])
                    ->where('status', 'success')
                    ->sum('amount');
                
                $dailyLimit = 45000000; // 45 million
                
                $this->logger->info('Daily limit check for JazzCash', [
                    'request_id' => $requestId,
                    'user_id' => $user->id,
                    'payment_method' => $request->payment_method,
                    'today_transactions_sum' => $todayTransactionsSum,
                    'current_transaction_amount' => $request->amount,
                    'daily_limit' => $dailyLimit,
                    'would_exceed_limit' => ($todayTransactionsSum + $request->amount) > $dailyLimit
                ]);
                
                if (($todayTransactionsSum + $request->amount) > $dailyLimit) {
                    $this->logger->warning('Daily limit exceeded for JazzCash', [
                        'request_id' => $requestId,
                        'user_id' => $user->id,
                        'today_transactions_sum' => $todayTransactionsSum,
                        'current_transaction_amount' => $request->amount,
                        'daily_limit' => $dailyLimit,
                        'execution_time' => microtime(true) - $startTime
                    ]);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Daily transaction limit exceeded. Please try again tomorrow.',
                    ], 400);
                }
            }


            try {
                $this->logger->info('Processing payment', [
                    'request_id' => $requestId,
                    'payment_method' => $request->payment_method,
                    'amount' => $request->amount
                ]);
                    
                list($post_data, $type, $url) = $this->service->process($request);
                $this->logger->info('Payment service processed', [
                    'request_id' => $requestId,
                    'type' => $type,
                    'url' => $url,
                    'post_data' => $post_data
                ]);

                if ($type == "easypaisa") {
                    try {
                        $easypaisa = new Easypaisa;
                        $easypaisaStartTime = microtime(true);
                        $response = $easypaisa->sendRequest($post_data);
                        $this->logger->info('Easypaisa API response', [
                            'request_id' => $requestId,
                            'response' => $response,
                            'api_execution_time' => microtime(true) - $easypaisaStartTime
                        ]);
                
                        if ($response instanceof \Illuminate\Http\JsonResponse) {
                            $response = $response->getData(true);
                        }
                
                        if (isset($response['responseCode'], $response['responseDesc'], $response['orderId'])) {
                            $responseCode = $response['responseCode'];
                            $responseDesc = $response['responseDesc'];
                            if ($responseCode == '0000') {
                                $transaction = $this->service->orderFinalProcess($response, $response['orderId'], 'easypaisa');
                                if ($transaction) {
                                    $url = $transaction->url;
                                    $data = [
                                        'orderId' => $transaction->orderId,
                                        'tid' => $transaction->transactionId,
                                        'amount' => $transaction->amount,
                                        'status' => $transaction->status,
                                    ];
                
                                    try {
                                        $callbackStartTime = microtime(true);
                                        Http::timeout(120)->post($url, $data);
                                        $this->logger->info('Callback URL notification sent', [
                                            'request_id' => $requestId,
                                            'callback_url' => $url,
                                            'callback_data' => $data,
                                            'callback_execution_time' => microtime(true) - $callbackStartTime
                                        ]);
                
                                        $this->logger->info('Payment checkout completed successfully', [
                                            'request_id' => $requestId,
                                            'transaction_id' => $transaction->txn_ref_no,
                                            'total_execution_time' => microtime(true) - $startTime
                                        ]);
                                        return response()->json([
                                            'status' => $transaction->status,
                                            'transaction_id' => $transaction->txn_ref_no,
                                            'message' => 'Payment checkout initiated successfully.',
                                        ], 200);
                                    } catch (\Exception $e) {
                                        $this->logger->error('Callback URL notification failed', [
                                            'request_id' => $requestId,
                                            'error' => $e->getMessage(),
                                            'callback_url' => $url,
                                            'execution_time' => microtime(true) - $startTime
                                        ]);
                                        return response()->json([
                                            'status' => 'error',
                                            'message' => 'Error notifying the callback URL.',
                                        ], 500);
                                    }
                                }
                            }
                            
                            $this->service->orderFinalProcess($response, $response['orderId'], 'easypaisa');
                            $this->logger->warning($type .' Payment checkout failed', [
                                'request_id' => $requestId,
                                'response_code' => $responseCode,
                                'response_desc' => $responseDesc,
                                'execution_time' => microtime(true) - $startTime
                            ]);
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Payment checkout cannot be processed, please try again.',
                            ], 400);
                        }
                
                        $this->logger->error('Unexpected Easypaisa response', [
                            'request_id' => $requestId,
                            'response' => $response,
                            'execution_time' => microtime(true) - $startTime
                        ]);
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Invalid response from Easypaisa.',
                        ], 500);
                    } catch (\Exception $e) {
                        $this->logger->error('Easypaisa payment processing error', [
                            'request_id' => $requestId,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'execution_time' => microtime(true) - $startTime
                        ]);
                        return response()->json([
                            'status' => 'error',
                            'message' => 'An error occurred while processing the payment.',
                        ], 500);
                    }
                } else {
                    $encode_data = json_encode($post_data, false);
                    $this->logger->info('Initiating JazzCash payment', [
                        'request_id' => $requestId,
                        'url' => $url,
                        'post_data' => $post_data
                    ]);

                    $curl = curl_init();
                    curl_setopt_array($curl, [
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => $encode_data,
                        CURLOPT_HTTPHEADER => [
                            'Content-Type: application/json'
                        ],
                        // CURLOPT_SSL_VERIFYPEER => true,
                        // CURLOPT_CAINFO => public_path('jazz_public_key/new-cert.crt'),
                    ]);
        
                    $jazzcashStartTime = microtime(true);
                    $response = curl_exec($curl);
                    $this->logger->info('JazzCash API response', [
                        'request_id' => $requestId,
                        'response' => $response,
                        'api_execution_time' => microtime(true) - $jazzcashStartTime
                    ]);

                    if ($response === false) {
                        $error = curl_error($curl);
                        curl_close($curl);
                        $this->logger->error('JazzCash CURL error', [
                            'request_id' => $requestId,
                            'error' => $error,
                            'execution_time' => microtime(true) - $startTime
                        ]);
                        throw new \Exception('CURL Error: ' . $error);
                    }
        
                    curl_close($curl);
                    $result = json_decode($response, false);
                    if (isset($result->pp_ResponseCode) && $result->pp_ResponseCode == '000') {
                        $transaction = $this->service->orderFinalProcess($result, $result->pp_TxnRefNo, 'jazzcash');
                        if ($transaction) {
                            $url = $transaction->url;
                            $data = [
                                'orderId' => $transaction->orderId,
                                'tid' => $transaction->transactionId,
                                'amount' => $transaction->amount,
                                'status' => $transaction->status,
                            ];
        
                            try {
                                $callbackStartTime = microtime(true);
                                $response = Http::timeout(120)->post($url, $data);
                                $this->logger->info('Callback URL notification sent', [
                                    'request_id' => $requestId,
                                    'callback_url' => $url,
                                    'callback_data' => $data,
                                    'callback_execution_time' => microtime(true) - $callbackStartTime
                                ]);
                                
                                $this->logger->info('Payment checkout completed successfully', [
                                    'request_id' => $requestId,
                                    'transaction_id' => $transaction->txn_ref_no,
                                    'total_execution_time' => microtime(true) - $startTime
                                ]);
                                return response()->json([
                                    'status' => $transaction->status,
                                    'transaction_id' => $transaction->txn_ref_no,
                                    'message' => 'Payment checkout initiated successfully.',
                                ], 200);
                            } catch (\Exception $e) {
                                $this->logger->error('Callback URL notification failed', [
                                    'request_id' => $requestId,
                                    'error' => $e->getMessage(),
                                    'callback_url' => $url,
                                    'execution_time' => microtime(true) - $startTime
                                ]);
                            }
                        }
                    } else {
                        $transaction = $this->service->orderFinalProcess($result, $result->pp_TxnRefNo, 'jazzcash');
                        $this->logger->warning($type .' Payment checkout failed', [
                            'request_id' => $requestId,
                            'response_code' => $result->pp_ResponseCode ?? 'unknown',
                            'execution_time' => microtime(true) - $startTime
                        ]);
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Payment checkout cannot be processed, please try again.',
                        ], 400);
                    }
                    $this->logger->warning($type .' Payment checkout failed', [
                        'request_id' => $requestId,
                        'execution_time' => microtime(true) - $startTime
                    ]);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Payment checkout cannot be processed, please try again.',
                    ], 400);
                }
        
            } catch (\Exception $e) {
                
                $this->logger->error('General error during transaction', [
                    'request_id' => $requestId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'execution_time' => microtime(true) - $startTime
                ]);
                // dd($e->getMessage());
                return response()->json([
                    'status' => 'error',
                    'message' => 'An error occurred during the transaction. Please try again.',
                ], 500);
            }
        }
    }
}