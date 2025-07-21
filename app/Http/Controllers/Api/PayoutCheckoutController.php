<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Zfhassaan\Easypaisa\Easypaisa;
use App\Service\PaymentService;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use App\Models\{Transaction, Payout, User, Setting, SurplusAmount};
use Illuminate\Support\Str;
use App\Service\JazzCashTokenService;
use App\Service\EasypaisaTimestampService;

class PayoutCheckoutController extends Controller
{
    public $service;
    private $logChannel = 'payout_checkout';
    private $requestId;
    private $tokenService;
    private $timestampService;

    /**
     * Cache duration constants for Easypaisa public key caching
     * These constants control how long the cached values are stored and their unique identifiers
     */
    private const PUBLIC_KEY_CACHE_DURATION = 3600; // 1 hour in seconds
    private const PUBLIC_KEY_CACHE_KEY = 'easypaisa_public_key';
    private const PUBLIC_KEY_RESOURCE_CACHE_KEY = 'easypaisa_public_key_resource';

	public function __construct(PaymentService $service) 
	{
		$this->service = $service;
        $this->requestId = Str::uuid()->toString();
        $this->tokenService = new JazzCashTokenService($this->requestId, $this->logChannel);
        $this->timestampService = new EasypaisaTimestampService($this->requestId, $this->logChannel);
    }

    private function log($level, $message, $context = [])
    {
        $context['request_id'] = $this->requestId;
        Log::channel($this->logChannel)->$level($message, $context);
    }

    public function payoutProceed(Request $request)
    {
        $startTime = microtime(true);
        $this->log('info', 'Payout request initiated', [
                'orderId' => $request->orderId,
                        'amount' => $request->amount,
            'payout_method' => $request->payout_method,
                        'phone' => $request->phone,
            'client_email' => $request->client_email,
            'callback_url' => $request->callback_url
        ]);

        // Add validation for critical inputs, may be need for the future, zeshan 10-april-2025
        //if (!filter_var($request->phone, FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/^[0-9]{10,12}$/']])) {
        //    return response()->json([
        //        'status' => 'error',
        //        'message' => 'Invalid phone number format'
        //    ], 400);
        //}

        try {
            // Get user and settings from middleware
            $user = $request->user_model;
            //$setting = $request->user_settings;

            // If user not found in request, try to fetch it using client_email
            if (!$user) {
                $this->log('warning', 'User not found in request, attempting to fetch using client_email', [
                    'client_email' => $request->client_email
                ]);
                
                $user = User::where('email', $request->client_email)->first();
                if(($request->payout_method == "jazzcash" && $user->payout_jc_api == 0) || ($request->payout_method == "easypaisa" && $user->payout_ep_api == 0)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Payout Api suspended by administrator.',
                    ], 400);
                }

                // If user found, set it in the request
                if ($user) {
                    $request->user_model = $user;
                    //$this->log('info', 'User successfully fetched and set in request', [
                    //    'user_id' => $user->id,
                    //    'email' => $user->email
                    //]);
                } else {
                    $this->log('error', 'Failed to fetch user with provided email', [
                        'client_email' => $request->client_email
                    ]);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'User not found with the provided email.',
                    ], 401);
                }
            }

            // Add validation for required environment variables, may be need for the future, zeshan 10-april-2025
            //$requiredEnvVars = [
            //    'EASYPAY_CLIENT_ID',
            //    'EASYPAY_CLIENT_SECRET',
            //    'EASYPAY_CHANNEL',
            //    'EASYPAY_MSISDN',
            //    'EASYPAY_MATOMA_TRANSFER_URL'
            //];

            //foreach ($requiredEnvVars as $var) {
            //    if (!env($var)) {
            //        throw new \RuntimeException("Missing required environment variable: $var");
            //    }
            //}

            if ($request->payout_method == "easypaisa") {
                return $this->handleEasypaisaPayout($request, $user, $request->callback_url);
            } else {
                // Similar comprehensive logging for JazzCash flow
                $this->log('info', 'JazzCash payout ready to sent', [
                        'orderId' => $request->orderId,
                        'amount' => $request->amount,
                    'phone' => $request->phone
                ]);

                $processStart = microtime(true);
                
                $tokenStart = microtime(true);
                $token = $this->getToken();
                //$this->log('debug', 'JazzCash token generated', [
                //    'token_generation_time' => microtime(true) - $tokenStart
                //]);

                $encryptionStart = microtime(true);
                $encryptionData = $this->encryptionFunc($request->all());
                //$this->log('debug', 'JazzCash data encrypted', [
                //    'encryption_time' => microtime(true) - $encryptionStart
                //]);

                $transactionUrl = env('JAZZCASH_MATOMA_URL');
                
                // Add retry logic for token expiration
                $maxRetries = 2;
                $retryCount = 0;
                $success = false;
                
                while (!$success && $retryCount <= $maxRetries) {
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => $transactionUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => json_encode([
                        "data" => $encryptionData,
                    ]),
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json',
                        'Content-Type: application/json',
                        "Authorization: Bearer $token",
                    ],
                ]);
                
                    $apiStart = microtime(true);
                    $response = curl_exec($curl);
                    $curlError = curl_error($curl);
                    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    $apiTime = microtime(true) - $apiStart;
                    
                    curl_close($curl);
                    
                    $this->log('info', 'JazzCash API response received', [
                        'http_code' => $httpCode,
                        'curl_error' => $curlError,
                        'api_time' => $apiTime,
                        'response' => $response,
                        'retry_count' => $retryCount
                    ]);

                    // Handle rate limit error (429)
                    if ($httpCode === 429) {
                        return $this->handleRateLimitError($user, $request, $response, $request->callback_url);
                    }

                    // Check for token expiration
                    if ($httpCode === 401) {
                        $responseData = json_decode($response, true);
                        if (isset($responseData['fault']['code']) && $responseData['fault']['code'] === 900901) {
                            $this->log('warning', 'Token expired, attempting to refresh', [
                                'retry_count' => $retryCount
                            ]);
                            
                            // Force token refresh
                            $token = $this->tokenService->forceRefreshToken($this->getClientId());
                            $retryCount++;
                            continue;
                        }
                    }
                    
                    $success = true;
                }
                
                if (!$success) {
                    $this->log('error', 'JazzCash API failed after retries', [
                        'retry_count' => $retryCount,
                        'total_time' => microtime(true) - $startTime
                    ]);
                    
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Unable to process payout after multiple attempts. Please try again.',
                    ], 500);
                }

                $decodeData = json_decode($response, true);
                $decrptionData = $this->decrytionFunc($decodeData['data']);
                $data = json_decode($decrptionData, true);
                
                $values = [
                    'user_id' => $user->id,
                    'code' => $data['responseCode'],
                    'message' => $data['responseDescription'],
                    'transaction_reference' => $data['referenceID'] ?? "",
                    'amount' => $request->amount,
                    'orderId' => $request->orderId,
                    // 'fee' => $data['Fee'] ?? "",
                    'phone' => $request->phone,
                    'transaction_type' => $request->payout_method,
                    'transaction_id' => $data['transactionID'] ?? "",
                    'status' => $data['responseCode'] === 'G2P-T-0' ? 'success' : 'failed',
                    'url' => $request->callback_url,
                ];
                
                $payoutStart = microtime(true);
                $transaction = Payout::create($values);
                $this->log('info', 'Payout record created', [
                    'payout_id' => $transaction->id,
                    'creation_time' => microtime(true) - $payoutStart
                ]);
                
                if ($data['responseCode'] === 'G2P-T-0') {
                    $this->log('info', 'JazzCash payout successful', [
                        'orderId' => $request->orderId,
                        'transaction_reference' => $values['transaction_reference']
                    ]);
                    
                    $call_url = $request->callback_url;
                    $call_data = [
                        'orderId' => $request->orderId,
                        'TID' => $transaction->transaction_reference,
                        'amount' => $transaction->amount,
                        'status' => 'success',
                    ];
                    
                    $this->updateUserBalance($user, $request->amount, $request->payout_method);
                    
                    $callbackStart = microtime(true);
                    $response = Http::timeout(60)->post($call_url, $call_data);
                    $this->log('info', 'Success callback sent', [
                        'callback_url' => $call_url,
                        'callback_response' => $response->json(),
                        'callback_time' => microtime(true) - $callbackStart
                    ]);
                    
                    $this->log('info', 'Payout request completed successfully', [
                        'total_time' => microtime(true) - $startTime,
                        'status' => 'success'
                    ]);
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Payout processed successfully.',
                        'transaction_id' => $values['transaction_reference'],
                    ], 200);
                } else {
                    $this->log('error', 'JazzCash payout failed', [
                        'orderId' => $request->orderId,
                        'response_code' => $data['responseCode'],
                        'response_message' => $data['responseDescription'],
                        'total_time' => microtime(true) - $startTime
                    ]);
                    
                    $url = $request->callback_url;
                    $call_data = [
                        'orderId' => $request->orderId,
                        'TID' => $transaction->transaction_reference,
                        'message' => 'Your payout cannot be processed due to '. $data['responseDescription']. ' , please try again.',
                        'status' => 'failed',
                    ];

                    $callbackStart = microtime(true);
                    $response = Http::timeout(60)->post($url, $call_data);
                    $this->log('info', 'Failure callback sent', [
                        'callback_url' => $url,
                        'callback_response' => $response->json(),
                        'callback_time' => microtime(true) - $callbackStart
                    ]);

                    return response()->json([
                        'status' => 'error',
                        'message' => 'Your payout cannot be processed due to '. $data['responseDescription']. ' , please try again.',
                    ], 400);
                }
            }
        } catch (\Exception $e) {
            $this->log('error', 'Unexpected error in payout process', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'total_time' => microtime(true) - $startTime
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred while processing your payout.',
            ], 500);
        }
    }

    /**
     * Handle Easypaisa payout processing
     *
     * @param Request $request
     * @param User $user
     * @param string $callback_url
     * @return JsonResponse
     */
    private function handleEasypaisaPayout($request, $user, $callback_url)
    {
        $startTime = microtime(true);
        $this->log('info', 'Processing Easypaisa payout', [
            'orderId' => $request->orderId,
            'amount' => $request->amount,
            'phone' => $request->phone
        ]);

        $processStart = microtime(true);
        
        $clientId = env('EASYPAY_CLIENT_ID');
        $clientSecret = env('EASYPAY_CLIENT_SECRET');
        $channel = env('EASYPAY_CHANNEL');
        
        $timeStampStart = microtime(true);
        $timeStamp = $this->timestampService->getTimestamp($clientId, $clientSecret, $channel);
        $this->log('debug', 'Easypaisa timestamp generated', [
            'timestamp' => $timeStamp,
            'generation_time' => microtime(true) - $timeStampStart
        ]);

        $hashStart = microtime(true);
        $xHashValue = $this->getXHashValue($timeStamp);
        //$this->log('debug', 'Easypaisa hash generated', [
        //    'hash_generation_time' => microtime(true) - $hashStart
        //]);
        
        $msisdn = env('EASYPAY_MSISDN');
        $transfer_url = env('EASYPAY_MATOMA_TRANSFER_URL');
        
        $maxRetries = 2;
        $retryCount = 0;
        $success = false;
        
        while (!$success && $retryCount <= $maxRetries) {
            $curl = curl_init();
            $payload = [
                "Amount" => (float) $request->amount,
                "MSISDN" => $msisdn,
                "ReceiverMSISDN" => $request->phone,
            ];
            
            $this->log('debug', 'Easypaisa API request prepared to sent', [
                'payload' => $payload,
                'url' => $transfer_url
            ]);

            curl_setopt_array($curl, [
                CURLOPT_URL => $transfer_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    "X-Hash-Value: $xHashValue",
                    "X-IBM-Client-Id: $clientId",
                    "X-IBM-Client-Secret: $clientSecret",
                    "X-Channel: $channel",
                    'Content-Type: application/json',
                ],
            ]);
            
            $apiStart = microtime(true);
            $response = curl_exec($curl);
            $curlError = curl_error($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $apiTime = microtime(true) - $apiStart;
            
            curl_close($curl);
            
            $this->log('info', 'Easypaisa API response received', [
                'http_code' => $httpCode,
                'curl_error' => $curlError,
                'response' => $response,
                'api_time' => $apiTime,
                'retry_count' => $retryCount
            ]);

            $data = json_decode($response, true);

            // Handle session validation error and unauthorized access
            if (isset($data['ResponseCode']) && 
                ($data['ResponseCode'] === '2' || 
                ($data['ResponseCode'] === '09' && 
                 isset($data['ResponseMessage']) && 
                 strpos($data['ResponseMessage'], 'getaccountholderregistrationdates') !== false ))
            ) {
                $this->log('warning', 'Session validation failed or unauthorized access, refreshing timestamp', [
                    'retry_count' => $retryCount,
                    'response_code' => $data['ResponseCode'],
                    'response_message' => $data['ResponseMessage'] ?? 'Unknown error'
                ]);
                
                // Force refresh timestamp
                $timeStamp = $this->timestampService->forceRefreshTimestamp($clientId, $clientSecret, $channel);
                $xHashValue = $this->getXHashValue($timeStamp);
                $retryCount++;
                continue;
            }

            // Handle other error codes
            if (isset($data['ResponseCode']) && $data['ResponseCode'] !== '0') {
                $this->log('error', 'Easypaisa payout request completed with error', [
                    'total_execution_time' => microtime(true) - $startTime,
                    'response_code' => $data['ResponseCode'],
                    'response_message' => $data['ResponseMessage'] ?? 'Unknown error'
                ]);
                return $this->handleEasypaisaError($data, $user, $request, $callback_url);
            }

            // If we get here, the request was successful
            $success = true;

            // Reset timestamp expiration after successful MaToMa API call
            $this->timestampService->resetTimestampExpiration($clientId);
        }

        if (!$success) {
            $this->log('error', 'Easypaisa API failed after retries', [
                'retry_count' => $retryCount,
                'total_execution_time' => microtime(true) - $startTime
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to process payout after multiple attempts. Please try again.',
            ], 500);
        }

        // Process successful response
        $values = [
            'user_id' => $user->id,
            'code' => $data['ResponseCode'],
            'message' => $data['ResponseMessage'],
            'transaction_reference' => $data['TransactionReference'] ?? "",
            'amount' => $request->amount,
            'orderId' => $request->orderId,
            'phone' => $request->phone,
            'transaction_type' => $request->payout_method,
            'status' => 'success',
            'url' => $callback_url,
        ];
        
        $payoutStart = microtime(true);
        $transaction = Payout::create($values);
        $this->log('info', 'Payout record created', [
            'payout_id' => $transaction->id,
            'creation_time' => microtime(true) - $payoutStart
        ]);

        // Update user balance after successful payout
        $this->updateUserBalance($user, $request->amount, $request->payout_method);

        // Send success callback
        $url = $callback_url;
        $call_data = [
            'orderId' => $request->orderId,
            'TID' => $transaction->transaction_reference,
            'amount' => $transaction->amount,
            'status' => 'success',
        ];

        $callbackStart = microtime(true);
        $response = Http::timeout(60)->post($url, $call_data);
        $this->log('info', 'Success callback sent', [
            'callback_url' => $url,
            'callback_response' => $response->json(),
            'callback_time' => microtime(true) - $callbackStart
        ]);

        $this->log('info', 'Easypaisa payout request completed successfully', [
            'total_execution_time' => microtime(true) - $startTime,
            'orderId' => $request->orderId,
            'transaction_reference' => $values['transaction_reference']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payout processed successfully.',
            'transaction_id' => $values['transaction_reference'],
        ], 200);
    }

    /**
     * Handle Easypaisa API error responses
     * 
     * @param array $data
     * @param User $user
     * @param Request $request
     * @param string $callback_url
     * @return JsonResponse
     */
    private function handleEasypaisaError($data, $user, $request, $callback_url)
    {
        $this->log('error', 'Easypaisa API error', [
            'response_code' => $data['ResponseCode'],
            'response_message' => $data['ResponseMessage'] ?? 'Unknown error'
        ]);
        
        // Create failed payout record
        $values = [
            'user_id' => $user->id,
            'code' => $data['ResponseCode'],
            'message' => $data['ResponseMessage'] ?? 'Unknown error',
            'transaction_reference' => '',
            'amount' => $request->amount,
            'orderId' => $request->orderId,
            'phone' => $request->phone,
            'transaction_type' => $request->payout_method,
            'status' => 'failed',
            'url' => $callback_url,
        ];
        
        $payoutStart = microtime(true);
        $transaction = Payout::create($values);
        $this->log('info', 'Failed payout record created', [
            'payout_id' => $transaction->id,
            'creation_time' => microtime(true) - $payoutStart
        ]);

        // Send callback
        $url = $callback_url;
        $call_data = [
            'orderId' => $request->orderId,
            'TID' => $transaction->transaction_reference,
            'message' => 'Your payout cannot be processed due to ' . ($data['ResponseMessage'] ?? 'Unknown error') . ', please try again.',
            'status' => 'failed',
        ];

        $callbackStart = microtime(true);
        $response = Http::timeout(60)->post($url, $call_data);
        $this->log('info', 'Failure callback sent', [
            'callback_url' => $url,
            'callback_response' => $response->json(),
            'callback_time' => microtime(true) - $callbackStart
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'Your payout cannot be processed due to ' . ($data['ResponseMessage'] ?? 'Unknown error') . ', please try again.',
        ], 400);
    }

    public function getTimeStamp($clientId,$clientSecret,$channel)
    {
        $url = env('EASYPAY_LOGIN_URL');
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
            CURLOPT_POSTFIELDS => '{
            "LoginPayload":"rUpnSSD2Vp4VLWWH3dHXpTml7ntgvBFkh1EOhWgGRg2XCir4nzA++dwPb8aMeeQjeH90qMpH49NMz34yh6qMZ8d+SGU5AMMea08cWiPgqlDe02i+mZSc0Uh7YN7D5Mdo1YtMMEqx8WOA5z5VOFZ4W0dII2YxpdZ+vz1p4kqxFpE4671U+qSp6loxw02v/hDuYlI8hf4pFi/scvz577kwSeT3S7DxywFaB6mp3Sgi6yUjrXdj+Tmilhe9mNRNP65t0LiHq92y268lwJTetE0ZuYas1cyGeRln1YaUeyby/U0IIm5BlqGVfuxTvWDvGLPk81oyv0Q7TY135MW7ADuNbw=="
            }',
            CURLOPT_HTTPHEADER => [
                "X-IBM-Client-Id: $clientId",
                "X-IBM-Client-Secret: $clientSecret",
                "X-Channel: $channel",
                'Content-Type: application/json',
            ],
        ]);
    
        $response = curl_exec($curl);
        $error = curl_error($curl);
    
        curl_close($curl);
    
        if ($error) {
            return response()->json(['error' => $error], 500);
        }
        
        $data=json_decode($response, true);
        $timeStamp=$data['Timestamp'];
        return $timeStamp;
    }

    /**
     * Get X-Hash-Value for Easypaisa API (Optimized Version)
     * Uses caching for public key to improve performance
     * 
     * This function generates the X-Hash-Value required for Easypaisa API authentication.
     * It implements caching for the raw public key file content.
     * 
     * @param string $timeStamp The timestamp from Easypaisa API
     * @return string|JsonResponse The encrypted X-Hash-Value or error response
     */
    public function getXHashValue($timeStamp)
    {
        // Start timing the execution for performance monitoring
        $startTime = microtime(true);
        
        try {
            // Prepare the data string that needs to be encrypted
            $msisdn = env('EASYPAY_MSISDN');
            $data = $msisdn . "~" . $timeStamp;
            
            // Get or generate the public key from cache
            $publicKey = cache()->remember(
                self::PUBLIC_KEY_CACHE_KEY, 
                self::PUBLIC_KEY_CACHE_DURATION, 
                function () {
                    // This closure only executes when cache is empty or expired
                    $this->log('info', 'Generating easypaisa new public key cache', [
                        'cache_key' => self::PUBLIC_KEY_CACHE_KEY,
                        'duration' => self::PUBLIC_KEY_CACHE_DURATION
                    ]);
                    
                    // Read the public key file
                    $publicKeyPath = public_path('easypaisa_public_key/publickey.pem');
                    if (!file_exists($publicKeyPath)) {
                        throw new \Exception('Public key file not found.');
                    }
                    return file_get_contents($publicKeyPath);
                }
            );

            // Log when we successfully retrieve from cache
            if (cache()->has(self::PUBLIC_KEY_CACHE_KEY)) {
                $this->log('debug', 'Retrieved easypaisa public key from cache', [
                    'cache_key' => self::PUBLIC_KEY_CACHE_KEY
                ]);
            }

            // Create OpenSSL resource from the public key
            $publicKeyResource = openssl_pkey_get_public($publicKey);
            if (!$publicKeyResource) {
                throw new \Exception('Failed to load public key.');
            }

            // Perform the actual encryption using the resource
            $encryptedData = '';
            if (!openssl_public_encrypt($data, $encryptedData, $publicKeyResource)) {
                throw new \Exception('Failed to encrypt data.');
            }

            // Free the OpenSSL resource
            openssl_free_key($publicKeyResource);

            // Encode the encrypted data to base64
            $result = base64_encode($encryptedData);
            
            // Log the successful generation
            $executionTime = microtime(true) - $startTime;
            $this->log('debug', 'X-Hash-Value generated', [
                'execution_time' => $executionTime,
                'timestamp' => $timeStamp,
                'cache_status' => [
                    'public_key' => cache()->has(self::PUBLIC_KEY_CACHE_KEY) ? 'hit' : 'miss'
                ]
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->log('error', 'Failed to generate X-Hash-Value', [
                'error' => $e->getMessage(),
                'execution_time' => microtime(true) - $startTime,
                'cache_status' => [
                    'public_key' => cache()->has(self::PUBLIC_KEY_CACHE_KEY) ? 'hit' : 'miss'
                ]
            ]);
            
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Original getXHashValue function (Kept as backup) bynauman
     * This function is kept for reference and can be used as fallback if needed
     * 
     * @param string $timeStamp
     * @return string|JsonResponse
     */
    /*
    public function getXHashValue_original($timeStamp)
    {
        $msisdn = env('EASYPAY_MSISDN');
        $timestamp = $timeStamp;

        $data = $msisdn . "~" . $timestamp;
        
        $publicKeyPath = public_path('easypaisa_public_key/publickey.pem');
        if (!file_exists($publicKeyPath)) {
            return response()->json(['error' => 'Public key file not found.'], 500);
        }

        $publicKey = file_get_contents($publicKeyPath);
        $publicKeyResource = openssl_pkey_get_public($publicKey);
        if (!$publicKeyResource) {
            return response()->json(['error' => 'Failed to load public key.'], 500);
        }

        $encryptedData = '';
        if (!openssl_public_encrypt($data, $encryptedData, $publicKeyResource)) {
            return response()->json(['error' => 'Failed to encrypt data.'], 500);
        }
        openssl_free_key($publicKeyResource);
        $encryptedData = base64_encode($encryptedData);
        return $encryptedData;
    }
    */

    public function getToken()
    {
        try {
            $clientId = $this->getClientId();
            return $this->tokenService->getToken($clientId);
        } catch (\Exception $e) {
            $this->log('error', 'Failed to get JazzCash token', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    private function getClientId()
    {
        return config('payment.payout.cache_key');
    }

    public function encryptionFunc($data)
    {
        $DateTime 		= new \DateTime();
		$pp_TxnDateTime = $DateTime->format('YmdHis');
		$pp_TxnRefNo = 'T'.$pp_TxnDateTime . substr(uniqid(), -5);
		
        $encodeData = json_encode([
            // "receiverCNIC" => $data['cnic'],
            "receiverMSISDN" => $data['phone'],
            "amount" => $data['amount'],
            "referenceId" => $pp_TxnRefNo
        ]);
 
        $encryptionKey = env('JAZZCASH_SECRET_KEY');
        $iv = env('JAZZCASH_INITIAL_VECTOR');
    
        $encryptedData = openssl_encrypt($encodeData, 'AES-128-CBC', $encryptionKey, OPENSSL_RAW_DATA, $iv);
    
        $hexEncryptedData = bin2hex($encryptedData);
        return $hexEncryptedData;
    }
    public function decrytionFunc($response)
    {
        $encryptedDataHex = $response;
        $binaryData = hex2bin($encryptedDataHex);

        $decryptionKey = env('JAZZCASH_SECRET_KEY');
        $iv = env('JAZZCASH_INITIAL_VECTOR');

        $decryptedData = openssl_decrypt($binaryData, 'AES-128-CBC', $decryptionKey, OPENSSL_RAW_DATA, $iv);
        
        return $decryptedData;
    }

    private function handleRateLimitError($user, $request, $response, $callback_url)
    {
        $responseData = json_decode($response, true);
        $this->log('warning', '***** Rate limit exceeded for JazzCash API *****', [
            'error_code' => $responseData['fault']['code'] ?? 'unknown',
            'error_message' => $responseData['fault']['message'] ?? 'unknown',
            'description' => $responseData['fault']['description'] ?? 'unknown'
        ]);
        
        // Create failed payout record
        //$values = [
        //    'user_id' => $user->id,
        //    'code' => 'RATE_LIMIT',
        //    'message' => 'Rate limit exceeded for JazzCash API',
        //    'transaction_reference' => '',
        //    'amount' => $request->amount,
        //    'orderId' => $request->orderId,
        //    'phone' => $request->phone,
        //    'transaction_type' => $request->payout_method,
        //    'status' => 'failed',
        //    'url' => $request->callback_url,
        //];
        
        $payoutStart = microtime(true);
        //$transaction = Payout::create($values);
        //s$this->log('info', 'Rate limit payout record created', [
            //'payout_id' => $transaction->id,
            //'creation_time' => microtime(true) - $payoutStart
        //]);
        
        // Send callback for rate limit
        //$url = $callback_url;
        //s$call_data = [
        //    'orderId' => $request->orderId,
        //    'message' => 'Your payout cannot be processed due to rate limit exceeded. Please try again later.',
        //    'status' => 'failed',
        //];

       // $callbackStart = microtime(true);
       // $response = Http::timeout(60)->post($url, $call_data);
       // $this->log('info', 'Rate limit callback sent', [
       //     'callback_url' => $url,
       //     'callback_response' => $response->json(),
       //     'callback_time' => microtime(true) - $callbackStart
       // ]);

        //return response()->json([
        //    'status' => 'error',
        //    'message' => 'Your payout cannot be processed due to rate limit exceeded. Please try again later.',
        //], 429);
    }

    /**
     * Update user balance after successful payout
     * 
     * @param User $user
     * @param float $amount
     * @param string $payoutMethod
     * @return void
     */
    private function updateUserBalance($user, $amount, $payoutMethod)
    {
        $setting = Setting::where('user_id', $user->id)->first();
        
        if ($setting && $user->per_payout_fee) {
            $rate = $user->per_payout_fee;
            $deductAmount = $amount * $rate;
            
            $balanceStart = microtime(true);
            
            // Update method-specific balance
            if ($payoutMethod === 'easypaisa') {
                $setting->easypaisa -= $deductAmount;
            } else if ($payoutMethod === 'jazzcash') {
                $setting->jazzcash -= $deductAmount;
            }
            
            // Update overall payout balance
            $setting->payout_balance -= $deductAmount;
            $setting->save();
            
            $this->log('info', 'User balance updated', [
                'user_id' => $user->id,
                'payout_method' => $payoutMethod,
                'deducted_amount' => $deductAmount,
                'remaining_balance' => $payoutMethod === 'easypaisa' ? $setting->easypaisa : $setting->jazzcash,
                'update_time' => microtime(true) - $balanceStart
            ]);
        }
    }
}
