<?php

namespace App\Http\Controllers\Api;

use App\Jobs\SendPayinCallbackJob;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Product,Client,Transaction,User};
use App\Service\PaymentService;
use App\Support\PayinRestrictionExclusion;
use App\Traits\HighValueTransactionRestriction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Services\Payin\InstrumentedEasypaisaPayinClient;
use App\Services\PhoneVerificationService;
use App\Services\Dashboard\PayinCheckoutMetricsRecorder;
use App\Helpers\GatewayMetricHelper;
use App\Support\PayinAmountRules;

class PayinController extends Controller
{
    use HighValueTransactionRestriction;
    
    public $service;
    protected $logger;
    protected array $monthlyLimits = [
        //'jazzcash' => [
            // 'user_id' => monthly_limit_amount
        //    2 => 0,
        //],
        'easypaisa' => [
            // 'user_id' => monthly_limit_amount
            2 => 930000000,
        ],
    ];

    public function __construct(
        PaymentService $service,
        private readonly PayinCheckoutMetricsRecorder $checkoutMetrics
    ) {
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
     * Check daily limit for JazzCash payments for specific users
     * 
     * @param Request $request
     * @param User $user
     * @param string $requestId
     * @param float $startTime
     * @return array|null Returns restriction response array or null if no restriction
     */
    private function checkDailyLimit(Request $request, User $user, string $requestId, float $startTime)
    {
        // Check daily limit for JazzCash payments for user ID 2
        //if ($request->payment_method == "jazzcash" && $user->id == 2 && $user->jc_payin_limit > 0) {
        if ($request->payment_method == "easypaisa" && $user->id == 2 && $user->ep_payin_limit > 0) {
            $todayStart = now()->startOfDay();
            $todayEnd = now()->endOfDay();
            
            $todayTransactionsSum = Transaction::where('user_id', $user->id)
                ->whereBetween('created_at', [$todayStart, $todayEnd])
                ->where('txn_type', "jazzcash")
                ->where('status', 'success')
                ->sum('amount');
            
            // $dailyLimit = 60000000; // 60 million
            $dailyLimit = $user->jc_payin_limit;    
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
                return [
                    'status' => 'error',
                    'message' => 'Daily transaction limit exceeded. Please try again tomorrow.',
                    'code' => 400
                ];
            }
        }
        
        return null; // No restriction applied
    }

    /**
     * Check monthly limit across active, archive, and backup transactions
     *
     * @param Request $request
     * @param User $user
     * @param string $requestId
     * @param float $startTime
     * @return array|null Returns restriction response array or null if no restriction
     */
    private function checkMonthlyLimit(Request $request, User $user, string $requestId, float $startTime)
    {
        $paymentMethod = $request->payment_method;

        if ($paymentMethod == "easypaisa" && $user->id == 2) {

            return [
                'status' => 'error',
                'message' => 'Monthly limit has been breached.',
                'code' => 400
            ];
        }
        return null;

        if (!isset($this->monthlyLimits[$paymentMethod][$user->id])) {
            return null;
        }

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $tables = ['transactions', 'archeive_transactions', 'backup_transactions'];
        $monthlySum = 0;

        foreach ($tables as $table) {
            $monthlySum += DB::table($table)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->where('status', 'success')
                ->where('txn_type', $paymentMethod)
                ->sum('amount');
        }

        $monthlyLimit = $this->monthlyLimits[$paymentMethod][$user->id];

        $this->logger->info('Monthly limit check', [
            'request_id' => $requestId,
            'user_id' => $user->id,
            'payment_method' => $paymentMethod,
            'monthly_transactions_sum' => $monthlySum,
            'current_transaction_amount' => $request->amount,
            'monthly_limit' => $monthlyLimit,
            'would_exceed_limit' => ($monthlySum + $request->amount) > $monthlyLimit
        ]);

        if (($monthlySum + $request->amount) > $monthlyLimit) {
            $this->logger->warning('Monthly limit exceeded', [
                'request_id' => $requestId,
                'user_id' => $user->id,
                'payment_method' => $paymentMethod,
                'monthly_transactions_sum' => $monthlySum,
                'current_transaction_amount' => $request->amount,
                'monthly_limit' => $monthlyLimit,
                'execution_time' => microtime(true) - $startTime
            ]);

            return [
                'status' => 'error',
                'message' => 'Monthly limit has been breached.',
                'code' => 400
            ];
        }

        return null; // No restriction applied
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
        if (PayinRestrictionExclusion::shouldBypass($request)) {
            $this->logger->info('Recent transaction restriction skipped (excluded client)', [
                'request_id' => $requestId,
                'client_email' => $request->input('client_email'),
                'phone' => $request->input('phone'),
            ]);

            return null;
        }

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
        $gateway = $this->resolveGateway($request);
        $this->logger->info('Starting checkout process', [
            'request_id' => $requestId,
            'request_data' => $request->all(),
            'timestamp' => now()->toDateTimeString()
        ]);

        $user = $request->user_model ?? User::where('email', $request->client_email)->first();

        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'regex:/^03[0-9]{9}$/'],
            'email' => 'required|email',
            'client_email' => 'required|email',
            'payment_method' => 'required|in:jazzcash,easypaisa',
            'amount' => PayinAmountRules::forPaymentMethod(
                (string) $request->payment_method,
                $user
            ),
            'orderId'=> 'required|unique:transactions,orderId',
        ]);
    
        if ($validator->fails()) {
            $this->logger->warning('Validation failed', [
                'request_id' => $requestId,
                 'request_input'  => $request->all(), // logs all request data
                'errors' => $validator->errors()->toArray(),
                'execution_time' => microtime(true) - $startTime
            ]);
            $this->recordApplicationCheckoutFailure(
                $request,
                $gateway,
                $startTime,
                GatewayMetricHelper::APPLICATION_ERROR_VALIDATION
            );

            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check for recent transaction restrictions
        $recentTransactionCheck = $this->checkRecentTransactionRestriction($request, $requestId, $startTime);
        if ($recentTransactionCheck) {
            $this->recordApplicationCheckoutFailure(
                $request,
                $gateway,
                $startTime,
                GatewayMetricHelper::APPLICATION_ERROR_RULE_VIOLATION
            );

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
            $this->recordApplicationCheckoutFailure(
                $request,
                $gateway,
                $startTime,
                GatewayMetricHelper::APPLICATION_ERROR_RULE_VIOLATION
            );

            return response()->json($restrictionCheck, $restrictionCheck['code']);
        }

        if(($request->payment_method == "jazzcash" && $user->jc_api == 0) || ($request->payment_method == "easypaisa" && $user->ep_api == 0)){
            $this->logger->warning('API suspended', [
                'request_id' => $requestId,
                'user_email' => $request->client_email,
                'payment_method' => $request->payment_method,
                'execution_time' => microtime(true) - $startTime
            ]);
            $this->recordApplicationCheckoutFailure(
                $request,
                $gateway,
                $startTime,
                GatewayMetricHelper::APPLICATION_ERROR_MERCHANT_DISABLED
            );

            return response()->json([
                'status' => 'error',
                'message' => 'Error: Limit exceeded.',
            ], 400);
        }
        else{
            // Check daily limit for JazzCash payments
            $dailyLimitCheck = $this->checkDailyLimit($request, $user, $requestId, $startTime);
            if ($dailyLimitCheck) {
                $this->recordApplicationCheckoutFailure(
                    $request,
                    $gateway,
                    $startTime,
                    GatewayMetricHelper::APPLICATION_ERROR_RULE_VIOLATION
                );

                return response()->json($dailyLimitCheck, $dailyLimitCheck['code']);
            }

            // Check monthly limit across all transaction tables
            //$monthlyLimitCheck = $this->checkMonthlyLimit($request, $user, $requestId, $startTime);
            //if ($monthlyLimitCheck) {
            //    return response()->json($monthlyLimitCheck, $monthlyLimitCheck['code']);
            //}


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
                    $easypaisaNetworkDiagnostics = null;
                    $easypaisaDiagnosticsLevel = 'info';

                    try {
                        $easypaisaStartTime = microtime(true);
                        $payinResult = app(InstrumentedEasypaisaPayinClient::class)->sendRequest(
                            $post_data,
                            $requestId,
                            $post_data['orderId'] ?? null,
                            $request->orderId
                        );
                        $easypaisaNetworkDiagnostics = $payinResult['diagnostics'] ?? null;
                        $easypaisaDiagnosticsLevel = $payinResult['diagnostics_level'] ?? 'info';
                        $response = $payinResult['response'];
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
                                    $this->queuePayinCallback(
                                        $transaction,
                                        $this->payinCallbackPayload($transaction, $transaction->status),
                                        $requestId,
                                        'easypaisa_success'
                                    );

                                    $this->logger->info('Payment checkout completed successfully', [
                                        'request_id' => $requestId,
                                        'transaction_id' => $transaction->txn_ref_no,
                                        'total_execution_time' => microtime(true) - $startTime
                                    ]);
                                    $this->recordGatewayCheckoutSuccess($request, $gateway, $startTime);
                                    try {
                                        app(PhoneVerificationService::class)->markVerified((string) $request->phone);
                                    } catch (\Throwable $e) {
                                        $this->logger->info('Failed to mark phone verified after success', [
                                            'request_id' => $requestId,
                                            'payment_method' => $paymentMethod,
                                            'phone' => (string) $request->phone,
                                            'error' => $e->getMessage(),
                                        ]);
                                    }
                                    return response()->json([
                                        'status' => $transaction->status,
                                        'transaction_id' => $transaction->txn_ref_no,
                                        'message' => 'Payment checkout initiated successfully.',
                                    ], 200);
                                }
                            } elseif ($responseCode == '0001') {
                                $transaction = $this->service->orderFinalProcess($response, $response['orderId'], 'easypaisa');
                                if ($transaction) {
                                    $this->queuePayinCallback(
                                        $transaction,
                                        $this->payinCallbackPayload($transaction, 'pending'),
                                        $requestId,
                                        'easypaisa_pending'
                                    );
                                }

                                $this->logger->info('Easypaisa payment pending', [
                                    'request_id' => $requestId,
                                    'response_code' => $responseCode,
                                    'response_desc' => $responseDesc,
                                    'execution_time' => microtime(true) - $startTime
                                ]);
                                $this->recordGatewayCheckoutPending($request, $gateway, $startTime);

                                return response()->json([
                                    'status' => 'pending',
                                    'transaction_id' => $transaction ? $transaction->txn_ref_no : $response['orderId'],
                                    'message' => 'Payment is pending.',
                                ], 200);
                            }
                            
                            $transaction = $this->service->orderFinalProcess($response, $response['orderId'], 'easypaisa');
                            if ($transaction) {
                                $this->queuePayinCallback(
                                    $transaction,
                                    $this->payinCallbackPayload($transaction, 'failed'),
                                    $requestId,
                                    'easypaisa_failed'
                                );
                            }
                            $this->logger->warning($type .' Payment checkout failed', [
                                'request_id' => $requestId,
                                'response_code' => $responseCode,
                                'response_desc' => $responseDesc,
                                'execution_time' => microtime(true) - $startTime
                            ]);
                            $this->recordGatewayCheckoutFailure(
                                $request,
                                $gateway,
                                $startTime,
                                $responseCode,
                                $responseDesc
                            );

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

                        if ($easypaisaDiagnosticsLevel === 'timeout') {
                            $this->checkoutMetrics->recordTimeoutFailure($request, $gateway, $startTime);
                        } elseif (isset($response['message']) && is_string($response['message'])) {
                            $classification = GatewayMetricHelper::classifyConnectionExceptionMessage($response['message']);

                            if ($classification['category'] === GatewayMetricHelper::CATEGORY_INFRASTRUCTURE
                                && $classification['error_type'] !== GatewayMetricHelper::INFRASTRUCTURE_ERROR_CONNECTION
                            ) {
                                $this->checkoutMetrics->recordClassifiedCheckoutFailure(
                                    $request,
                                    $gateway,
                                    $startTime,
                                    $classification['category'],
                                    $classification['error_type']
                                );
                            } else {
                                $this->checkoutMetrics->recordApplicationCheckoutFailure(
                                    $request,
                                    $gateway,
                                    $startTime,
                                    GatewayMetricHelper::APPLICATION_ERROR_VALIDATION
                                );
                            }
                        } else {
                            $this->checkoutMetrics->recordInfrastructureCheckoutFailure(
                                $request,
                                $gateway,
                                $startTime,
                                GatewayMetricHelper::INFRASTRUCTURE_ERROR_HTTP_500
                            );
                        }

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
                        $classification = GatewayMetricHelper::classifyConnectionExceptionMessage($e->getMessage());
                        $this->recordClassifiedCheckoutFailure(
                            $request,
                            $gateway,
                            $startTime,
                            $classification['category'],
                            $classification['error_type']
                        );

                        return response()->json([
                            'status' => 'error',
                            'message' => 'An error occurred while processing the payment.',
                        ], 500);
                    } finally {
                        if (is_array($easypaisaNetworkDiagnostics)) {
                            $this->logger->log(
                                $easypaisaDiagnosticsLevel,
                                'Easypaisa network diagnostics',
                                $easypaisaNetworkDiagnostics
                            );
                        }
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
                            $this->queuePayinCallback(
                                $transaction,
                                $this->payinCallbackPayload($transaction, $transaction->status),
                                $requestId,
                                'jazzcash_success'
                            );

                            $this->logger->info('Payment checkout completed successfully', [
                                'request_id' => $requestId,
                                'transaction_id' => $transaction->txn_ref_no,
                                'total_execution_time' => microtime(true) - $startTime
                            ]);
                            $this->recordGatewayCheckoutSuccess($request, $gateway, $startTime);

                            return response()->json([
                                'status' => $transaction->status,
                                'transaction_id' => $transaction->txn_ref_no,
                                'message' => 'Payment checkout initiated successfully.',
                            ], 200);
                        }

                        $this->logger->warning($type .' Payment checkout failed', [
                            'request_id' => $requestId,
                            'response_code' => $result->pp_ResponseCode,
                            'execution_time' => microtime(true) - $startTime
                        ]);
                        $this->recordGatewayCheckoutFailure(
                            $request,
                            $gateway,
                            $startTime,
                            (string) ($result->pp_ResponseCode ?? ''),
                            (string) ($result->pp_ResponseMessage ?? '')
                        );

                        return response()->json([
                            'status' => 'error',
                            'message' => 'Payment checkout cannot be processed, please try again.',
                        ], 400);
                    } elseif (isset($result->pp_ResponseCode) && $result->pp_ResponseCode == '157') {
                        $transaction = $this->service->orderFinalProcess($result, $result->pp_TxnRefNo, 'jazzcash');
                        if ($transaction) {
                            $this->queuePayinCallback(
                                $transaction,
                                $this->payinCallbackPayload($transaction, 'pending'),
                                $requestId,
                                'jazzcash_pending'
                            );
                        }

                        $this->logger->info('JazzCash payment pending', [
                            'request_id' => $requestId,
                            'response_code' => $result->pp_ResponseCode,
                            'response_message' => $result->pp_ResponseMessage ?? null,
                            'execution_time' => microtime(true) - $startTime
                        ]);
                        $this->recordGatewayCheckoutPending($request, $gateway, $startTime);

                        return response()->json([
                            'status' => 'pending',
                            'transaction_id' => $transaction ? $transaction->txn_ref_no : $result->pp_TxnRefNo,
                            'message' => 'Payment is pending.',
                        ], 200);
                    } else {
                        $transaction = $this->service->orderFinalProcess($result, $result->pp_TxnRefNo, 'jazzcash');
                        if ($transaction) {
                            $this->queuePayinCallback(
                                $transaction,
                                $this->payinCallbackPayload($transaction, 'failed'),
                                $requestId,
                                'jazzcash_failed'
                            );
                        }
                        $this->logger->warning($type .' Payment checkout failed', [
                            'request_id' => $requestId,
                            'response_code' => $result->pp_ResponseCode ?? 'unknown',
                            'execution_time' => microtime(true) - $startTime
                        ]);
                        $this->recordGatewayCheckoutFailure(
                            $request,
                            $gateway,
                            $startTime,
                            (string) ($result->pp_ResponseCode ?? ''),
                            (string) ($result->pp_ResponseMessage ?? '')
                        );

                        return response()->json([
                            'status' => 'error',
                            'message' => 'Payment checkout cannot be processed, please try again.',
                        ], 400);
                    }
                }
        
            } catch (\Exception $e) {
                
                $this->logger->error('General error during transaction', [
                    'request_id' => $requestId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'execution_time' => microtime(true) - $startTime
                ]);
                $classification = GatewayMetricHelper::classifyConnectionExceptionMessage($e->getMessage());
                $this->recordClassifiedCheckoutFailure(
                    $request,
                    $gateway,
                    $startTime,
                    $classification['category'],
                    $classification['error_type']
                );
                // dd($e->getMessage());
                return response()->json([
                    'status' => 'error',
                    'message' => 'An error occurred during the transaction. Please try again.',
                ], 500);
            }
        }
    }

    private function payinCallbackPayload(Transaction $transaction, string $status): array
    {
        return [
            'orderId' => $transaction->orderId,
            'tid' => $transaction->transactionId,
            'amount' => $transaction->amount,
            'status' => $status,
        ];
    }

    private function queuePayinCallback(
        ?Transaction $transaction,
        array $payload,
        string $requestId,
        string $context
    ): void {
        if (!$transaction || empty($transaction->url)) {
            $this->logger->info('Payin callback skipped', [
                'request_id' => $requestId,
                'context' => $context,
                'reason' => !$transaction ? 'no_transaction' : 'empty_callback_url',
            ]);
            return;
        }

        $job = new SendPayinCallbackJob($transaction->url, $payload, $requestId, $context);
        $jobId = Queue::connection('database')->pushOn('default', $job);

        $this->logger->info('Payin callback queued', [
            'request_id' => $requestId,
            'context' => $context,
            'callback_url' => $transaction->url,
            'callback_data' => $payload,
            'queue_connection' => 'database',
            'queue_default' => config('queue.default'),
            'queue_job_id' => $jobId,
        ]);
    }

    private function resolveGateway(Request $request): string
    {
        return (string) $request->input('payment_method', '');
    }

    private function recordGatewayCheckoutSuccess(Request $request, string $gateway, float $startTime): void
    {
        $this->checkoutMetrics->recordGatewayCheckoutSuccess($request, $gateway, $startTime);
    }

    private function recordGatewayCheckoutPending(Request $request, string $gateway, float $startTime): void
    {
        $this->checkoutMetrics->recordGatewayCheckoutPending($request, $gateway, $startTime);
    }

    private function recordApplicationCheckoutFailure(
        Request $request,
        string $gateway,
        float $startTime,
        string $applicationErrorType
    ): void {
        $this->checkoutMetrics->recordApplicationCheckoutFailure(
            $request,
            $gateway,
            $startTime,
            $applicationErrorType
        );
    }

    private function recordInfrastructureCheckoutFailure(
        Request $request,
        string $gateway,
        float $startTime,
        string $infrastructureErrorType
    ): void {
        $this->checkoutMetrics->recordInfrastructureCheckoutFailure(
            $request,
            $gateway,
            $startTime,
            $infrastructureErrorType
        );
    }

    private function recordGatewayCheckoutFailure(
        Request $request,
        string $gateway,
        float $startTime,
        ?string $responseCode,
        ?string $responseDescription
    ): void {
        $this->checkoutMetrics->recordGatewayCheckoutFailure(
            $request,
            $gateway,
            $startTime,
            $responseCode,
            $responseDescription
        );
    }

    private function recordClassifiedCheckoutFailure(
        Request $request,
        string $gateway,
        float $startTime,
        string $category,
        string $errorType
    ): void {
        $this->checkoutMetrics->recordClassifiedCheckoutFailure(
            $request,
            $gateway,
            $startTime,
            $category,
            $errorType
        );
    }
}