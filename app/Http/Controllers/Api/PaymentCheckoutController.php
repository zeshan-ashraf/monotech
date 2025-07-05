<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Zfhassaan\Easypaisa\Easypaisa;
use App\Service\PaymentServiceV1;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use App\Models\{Transaction, Payout, User, BlockedNumber};
use DateTime;
use DateTimeZone;

class PaymentCheckoutController extends Controller
{
    public $service;
    //public $accountDeostNotExitJC = 'JazzCash Mobile Account does not exist against the provided MSISDN.';
    //public $accountInsufficientBalanceJC = 'The balance in account of provided JazzCash Account is insufficient for the transaction.';
    
    public $accountInsufficientBalanceJC = 'CPS - Insufficient balance.';
    public $accountDeostNotExitJC = 'xxCPSxx - xxEither xxWalletxx doesxx not xxexistxx';

    public $accountDeostNotExitEP = 'ACCOUNT DOES NOT EXIST';
    public $manualCancellationJC = 'A confirmer sends the short message "N" to cancel a transaction.';
    public $manualCancellationJCAlt = 'A confirmer sends the short message \"N\" to cancel a transaction.';
    public $manualCancellationJCDoubleEscaped = 'A confirmer sends the short message \\"N\\" to cancel a transaction.';

	public function __construct(PaymentServiceV1 $service)
	{
		$this->service = $service;
	}
	//Log::channel('payin')->info('Payout started for user', ['email' => $email]);
    public function checkoutProceed(Request $request)
    {
        $startTime = microtime(true);
        $requestId = uniqid();
        
        // Log all incoming request parameters
        Log::channel('payin')->info('************** Payment checkout initiated', [
            'request_id' => $requestId,
            'request_params' => $request->all(),
            'client_ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent')
        ]);

        // Get user from request (added by middleware)
        $user = $request->user_model;
        
        // Validate if API access is enabled for the payment method
        if (!$this->validateApiAccess($request, $user, $requestId)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Api suspended by administrator.',
            ], 400);
        }

        try {
            // Add requestId to request for consistent logging
            $request->merge(['request_id' => $requestId]);
            
            // Process payment based on payment method
            return $this->processPayment($request, $user, $startTime, $requestId);
        } catch (\Exception $e) {
            return $this->handleProcessingError($e, $requestId, $request);
        }
    }

    /**
     * Validate if user has API access for the selected payment method
     */
    private function validateApiAccess(Request $request, User $user, string $requestId): bool
    {
        if(($request->payment_method == "jazzcash" && $user->jc_api == 0) || 
           ($request->payment_method == "easypaisa" && $user->ep_api == 0)) {
            
            /*Log::channel('payin')->error('API access suspended', [
                'request_id' => $requestId,
                'client_email' => $request->client_email,
                'payment_method' => $request->payment_method,
                'request_params' => $request->all()
            ]);*/
            
            return false;
        }
        
        return true;
    }

    /**
     * Check if a phone number is blocked
     */
    private function isNumberBlocked(string $phone, string $paymentMethod): bool
    {
        return BlockedNumber::where('phone_number', $phone)
            ->where('payment_method', $paymentMethod)
            ->exists();
    }

    /**
     * Log a failed number to blocked numbers table
     */
    private function logBlockedNumber(string $phone, string $paymentMethod, string $responseCode, string $responseDesc, ?int $userId = null): void
    {
        Log::channel('payin')->info('### Saving blocked number', [
            'phone' => $phone,
            'payment_method' => $paymentMethod,
            'response_code' => $responseCode,
            'response_desc' => $responseDesc,
            'user_id' => $userId,
            'timestamp' => now()
        ]);

        BlockedNumber::updateOrCreateBlocked($phone, $paymentMethod, $responseCode, $responseDesc, $userId);
    }

    /**
     * Process payment based on selected payment method
     */
    private function processPayment(Request $request, User $user, float $startTime, string $requestId): JsonResponse
    {
        /*Log::channel('payin')->info('Processing payment request', [
            'request_id' => $requestId,
            'client_email' => $request->client_email,
            'payment_method' => $request->payment_method,
            'request_params' => $request->all()
        ]);*/
        
        // Get payment method details
        list($post_data, $type, $url) = $this->service->process($request);

        // Get the transaction from the request if it exists
        $transaction = $request->transaction_model;
        
        if ($type == "easypaisa") {
            return $this->processEasypaisaPayment($post_data, $url, $request, $transaction, $user, $startTime, $requestId);
        } else {
            return $this->processJazzcashPayment($post_data, $url, $request, $transaction, $user, $startTime, $requestId);
        }
    }

    /**
     * Process Easypaisa payment
     */
    private function processEasypaisaPayment(array $post_data, string $url, Request $request, 
                                            ?Transaction $transaction, User $user, 
                                            float $startTime, string $requestId): JsonResponse
    {
        try {
            Log::channel('payin')->info('Initiating Easypaisa payment', [
                'request_id' => $requestId,
                'client_email' => $request->client_email,
                'request_params' => $request->all(),
                'post_data' => json_encode($post_data),
                'api_url' => $url
            ]);

            $easypaisa = new Easypaisa;
            $response = $easypaisa->sendRequest($post_data);
    
            // Decode the response into an array if needed
            if ($response instanceof \Illuminate\Http\JsonResponse) {
                $response = $response->getData(true);
            }
    
            // Validate the structure of the response
            if (isset($response['responseCode'], $response['responseDesc'], $response['orderId'])) {
                //Log::channel('payin')->info('Easypaisa response received', [
                //    'request_id' => $requestId,
                //    'order_id' => $response['orderId'],
                //    'response_code' => $response['responseCode'],
                //    'response_desc' => $response['responseDesc'],
                //    'request_params' => $request->all(),
                //    'api_request' => json_encode($post_data)
                //]);

                $responseCode = $response['responseCode'];
                $responseDesc = $response['responseDesc'];
                
                // Process successful response
                if ($responseCode == '0000') {
                    return $this->handleSuccessfulPayment($response, $response['orderId'], 'easypaisa', 
                                                        $user, $transaction, $startTime, $requestId);
                }
                
                // Log failed number if account doesn't exist
                if ($responseCode == '0014' && stripos($responseDesc, $this->accountDeostNotExitEP) !== false) {
                    $this->logBlockedNumber(
                        $request->phone,
                        'easypaisa',
                        $responseCode,
                        $responseDesc,
                        $user->id
                    );
                }

                // Process failed response
                Log::channel('payin')->warning('Easypaisa payment failed', [
                    'request_id' => $requestId,
                    'execution_time' => microtime(true) - $startTime,
                    'response_code' => $responseCode,
                    'response_desc' => $responseDesc,
                    //'request_params' => $request->all(),
                    //'api_request' => json_encode($post_data),
                    'complete_response' => json_encode($response),
                    'timestamp' => now(),
                ]);

                // Update transaction status
                $this->service->orderFinalProcess($response, $response['orderId'], 'easypaisa', $user, $transaction);
                
                return $this->getErrorResponse();
            }
    
            Log::channel('payin')->error('Invalid Easypaisa response structure', [
                'request_id' => $requestId,
                'response' => json_encode($response),
                'request_params' => $request->all(),
                'api_request' => json_encode($post_data)
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid response from Easypaisa.',
            ], 500);
        } catch (\Exception $e) {
            Log::channel('error')->error('Easypaisa payment processing error', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_params' => $request->all(),
                'api_request' => json_encode($post_data ?? []),
                'api_url' => $url,
                'timestamp' => now()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while processing the payment.',
            ], 500);
        }
    }

    /**
     * Process JazzCash payment
     */
    private function processJazzcashPayment(array $post_data, string $url, Request $request, 
                                           ?Transaction $transaction, User $user, 
                                           float $startTime, string $requestId): JsonResponse
    {
        Log::channel('payin')->info('Initiating JazzCash payment', [
            'request_id' => $requestId,
            'client_email' => $request->client_email,
            'request_params' => $request->all(),
            'post_data' => json_encode($post_data),
            'api_url' => $url
        ]);

        $encode_data = json_encode($post_data, false);
        
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
        ]);

        $response = curl_exec($curl);
        
        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);
            
            Log::channel('error')->error('JazzCash CURL error', [
                'request_id' => $requestId,
                'error' => $error,
                'url' => $url,
                'request_params' => $request->all(),
                'api_request' => json_encode($post_data),
                'curl_error_no' => curl_errno($curl),
                'timestamp' => now()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Connection error while processing payment.',
            ], 500);
        }

        curl_close($curl);

        $result = json_decode($response, false);
        
        // Validate response structure
        if (!$result || !isset($result->pp_ResponseCode)) {
            Log::channel('error')->error('Invalid JazzCash response', [
                'request_id' => $requestId,
                'raw_response' => $response,
                'request_params' => $request->all(),
                'api_request' => json_encode($post_data),
                'timestamp' => now()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid response from JazzCash.',
            ], 500);
        }

        //Log::channel('payin')->info('JazzCash response received', [
        //    'request_id' => $requestId,
        //    'response_code' => $result->pp_ResponseCode ?? 'unknown',
        //    'transaction_ref' => $result->pp_TxnRefNo ?? 'unknown',
        //    'request_params' => $request->all(),
        //    'api_request' => json_encode($post_data)
        //]);

        if (isset($result->pp_ResponseCode) && $result->pp_ResponseCode == '000') {
            return $this->handleSuccessfulPayment($result, $result->pp_TxnRefNo, 'jazzcash', 
                                                $user, $transaction, $startTime, $requestId);
        }
        
        // Log failed number if account doesn't exist
        if ($result->pp_ResponseCode == '999' && 
            stripos($result->pp_ResponseMessage, $this->accountDeostNotExitJC) !== false) {
            $this->logBlockedNumber(
                $request->phone,
                'jazzcash',
                $result->pp_ResponseCode,
                $result->pp_ResponseMessage,
                $user->id
            );
        }
        
        // Handle insufficient balance
        if ($result->pp_ResponseCode == '999' && 
            stripos($result->pp_ResponseMessage, $this->accountInsufficientBalanceJC) !== false) {
            BlockedNumber::handleInsufficientBalance(
                $request->phone,
                $result->pp_ResponseCode,
                $result->pp_ResponseMessage,
                $user->id
            );
        }

        // Handle manual cancellation or late mpin input
        if ($result->pp_ResponseCode == '999' && 
            $this->isManualCancellation($result->pp_ResponseMessage)) {
            BlockedNumber::handleManualCancellation(
                $request->phone,
                $result->pp_ResponseCode,
                $result->pp_ResponseMessage,
                $user->id
            );
        }
        
        Log::channel('payin')->warning('JazzCash payment failed', [
            'request_id' => $requestId,
            'execution_time' => microtime(true) - $startTime,
            'response_code' => $result->pp_ResponseCode ?? 'unknown',
            'response_message' => $result->pp_ResponseMessage ?? 'unknown',
            'transaction_ref' => $result->pp_TxnRefNo ?? 'unknown',
            
            //'request_params' => $request->all(),
            //'api_request' => json_encode($post_data),
            'complete_response' => json_encode($result),
            //'raw_response' => $response,
            'timestamp' => now(),
        ]);

        // Update transaction status
        $this->service->orderFinalProcess($result, $result->pp_TxnRefNo, 'jazzcash', $user, $transaction);
        
        return $this->getErrorResponse();
    }

    /**
     * Check if the response message indicates a manual cancellation
     */
    private function isManualCancellation(string $responseMessage): bool
    {
        return stripos($responseMessage, $this->manualCancellationJC) !== false ||
               stripos($responseMessage, $this->manualCancellationJCAlt) !== false ||
               stripos($responseMessage, $this->manualCancellationJCDoubleEscaped) !== false;
    }

    /**
     * Handle successful payment for both payment methods
     */
    private function handleSuccessfulPayment($response, $txnRefNo, $paymentMethod, 
                                            User $user, ?Transaction $transaction, 
                                            float $startTime, string $requestId): JsonResponse
    {
        $updatedTransaction = $this->service->orderFinalProcess($response, $txnRefNo, $paymentMethod, $user, $transaction);
        
        if ($updatedTransaction) {
            $executionTime = microtime(true) - $startTime;
            
            Log::channel('payin')->info("$paymentMethod payment completed successfully", [
                'request_id' => $requestId,
                'execution_time' => $executionTime,
                'transaction_id' => $updatedTransaction->txn_ref_no,
                'original_request' => request()->all()
            ]);

            return response()->json([
                'status' => $updatedTransaction->status,
                'transaction_id' => $updatedTransaction->txn_ref_no,
                'message' => 'Payment checkout initiated successfully.',
            ], 200);
        }
        
        return $this->getErrorResponse();
    }

    /**
     * Get standard error response for failed payments
     */
    private function getErrorResponse(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Payment checkout cannot be processed, please try again.',
        ], 400);
    }

    /**
     * Handle general processing errors
     */
    private function handleProcessingError(\Exception $e, string $requestId, Request $request): JsonResponse
    {
        Log::channel('error')->error('Payment checkout general error', [
            'request_id' => $requestId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'client_email' => $request->client_email,
            'payment_method' => $request->payment_method,
            'request_params' => $request->all()
        ]);
        
        return response()->json([
            'status' => 'error',
            'message' => 'An error occurred during the transaction. Please try again.',
        ], 500);
    }
} 