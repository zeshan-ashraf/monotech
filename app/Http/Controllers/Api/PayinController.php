<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Product,Client,Transaction,User};
use App\Service\PaymentService;
use Illuminate\Support\Facades\Log;
use Zfhassaan\Easypaisa\Easypaisa;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use DB;
use Ramsey\Uuid\Uuid;

class PayinController extends Controller
{
    public $service;
    protected $logger;

    public function __construct(PaymentService $service)
    {
        $this->service = $service;
        $this->logger = Log::channel('payin');
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