<?php
namespace App\Service;

use App\Models\OrderBilling;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Carbon\Carbon;
use App\Models\{User, Transaction, Setting, SurplusAmount};
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Zfhassaan\Easypaisa\Easypaisa;
use Illuminate\Database\QueryException;

class PaymentServiceV1
{
    protected $merchantId;
    protected $version;
    protected $language;
    protected $password;
    protected $currency_code;
    protected $return_url;
    protected $transactionPostUrl;
    protected $integritySalt;
    
    public function __construct()
    {
        $this->merchantId = config('jazzcash.constants.MERCHANT_ID');
        $this->version = config('jazzcash.constants.VERSION');
        $this->language = config('jazzcash.constants.LANGUAGE');
        $this->password = config('jazzcash.constants.PASSWORD');
        $this->currency_code = config('jazzcash.constants.CURRENCY_CODE');
        $this->return_url = config('jazzcash.constants.RETURN_URL');
        $this->transactionPostUrl = config('jazzcash.constants.TRANSACTION_POST_URL');
        $this->integritySalt = config('jazzcash.constants.INTEGERITY_SALT');
    }

    /**
     * Process payment based on payment method
     *
     * @param Request $request
     * @return array
     */
    public function process(Request $request): array
    {
        $requestId = $request->request_id ?? uniqid();
        /*Log::channel('payout')->info('[TestPaymentService] Processing payment method selection', [
            'request_id' => $requestId,
            'payment_method' => $request->payment_method,
            'service_step' => 'process_start'
        ]);*/

        $function = $request->payment_method == 'easypaisa' ? 'easypaisa' : 'jazzcash';
        return $this->$function($request);
    }

    /**
     * Process JazzCash payment
     *
     * @param Request $request
     * @return array
     */
    public function jazzcash(Request $request): array
    {
        $requestId = $request->request_id ?? uniqid();
        /*Log::channel('payout')->info('[TestPaymentService] Initiating JazzCash payment processing', [
            'request_id' => $requestId,
            'service_step' => 'jazzcash_init',
            'amount' => $request->amount,
            'phone' => $request->phone
        ]);*/

        // Generate transaction reference and time
        $transactionData = $this->generateTransactionData($request->amount);
        $pp_Amount = $transactionData['amount'];
        $pp_TxnDateTime = $transactionData['dateTime'];
        $pp_TxnRefNo = $transactionData['referenceNumber'];

        // Create post data for JazzCash
        $post_data = $this->createJazzCashPostData($pp_Amount, $pp_TxnDateTime, $pp_TxnRefNo, $request->phone);

        /*
        Log::channel('payout')->info('[TestPaymentService] JazzCash request prepared', [
            'request_id' => $requestId,
            'service_step' => 'jazzcash_request_prepared',
            'transaction_ref' => $pp_TxnRefNo,
            'post_data' => json_encode($post_data)
        ]);
        */

        // Generate secure hash and add to post data
        $pp_SecureHash = $this->jazzcashSecureHash($post_data);
        $post_data['pp_SecureHash'] = $pp_SecureHash;
        
        // Create transaction and store it in the request for later use
        $transaction = $this->orderInitialProcess($request, $pp_TxnRefNo);
        $request->merge(['transaction_model' => $transaction]);
        
        return [
            $post_data, 
            'jazzcash', 
            $this->transactionPostUrl
        ];
    }

    /**
     * Create post data for JazzCash
     *
     * @param int $amount
     * @param string $txnDateTime
     * @param string $txnRefNo
     * @param string $phone
     * @return array
     */
    private function createJazzCashPostData(string $amount, string $txnDateTime, string $txnRefNo, string $phone): array
    {
        return [
            "pp_Amount" => $amount,
            "pp_BillReference" => "billRef",
            "pp_Description" => "Description of transaction",
            "pp_Language" => $this->language,
            "pp_MerchantID" => $this->merchantId,
            "pp_Password" => $this->password,
            "pp_ReturnURL" => $this->return_url,
            "pp_SecureHash" => "",
            "pp_TxnCurrency" => $this->currency_code,
            "pp_TxnDateTime" => $txnDateTime,
            "pp_TxnExpiryDateTime" => date('YmdHis', strtotime('+8 Days')),
            "pp_TxnRefNo" => $txnRefNo,
            "pp_TxnType" => "MWALLET",
            "pp_Version" => $this->version,
            "ppmpf_1" => $phone,
        ];
    }

    /**
     * Generate JazzCash secure hash
     *
     * @param array $data_array
     * @return string
     */
    private function jazzcashSecureHash(array $data_array): string
    {
        ksort($data_array);
        
        $str = '';
        foreach($data_array as $key => $value){
            if(!empty($value)){
                $str = $str . '&' . $value;
            }
        }
        
        $str = $this->integritySalt.$str;
        
        return hash_hmac('sha256', $str, $this->integritySalt);
    }

    /**
     * Process Easypaisa payment
     *
     * @param Request $request
     * @return array
     */
    public function easypaisa(Request $request): array
    {
        $requestId = $request->request_id ?? uniqid();
        /*Log::channel('payout')->info('[TestPaymentService] Initiating Easypaisa payment processing', [
            'request_id' => $requestId,
            'service_step' => 'easypaisa_init',
            'amount' => $request->amount,
            'phone' => $request->phone
        ]);*/

        // Generate transaction data
        $transactionData = $this->generateTransactionData($request->amount, false);
        $pp_Amount = $transactionData['amount'];
        $pp_TxnRefNo = $transactionData['referenceNumber'];

        // Create post data for Easypaisa
        $post_data = $this->createEasypaisaPostData(
            $pp_Amount, 
            $pp_TxnRefNo, 
            $request->phone, 
            $request->email
        );

        /*
        Log::channel('payout')->info('[TestPaymentService] Easypaisa request prepared', [
            'request_id' => $requestId,
            'service_step' => 'easypaisa_request_prepared',
            'transaction_ref' => $pp_TxnRefNo,
            'post_data' => json_encode($post_data)
        ]);*/
        
        // Create transaction and store it in the request for later use
        $transaction = $this->orderInitialProcess($request, $pp_TxnRefNo);
        $request->merge(['transaction_model' => $transaction]);
        
        return [
            $post_data, 
            'easypaisa', 
            env('EASYPAISA_PRODUCTION_URL')
        ];
    }

    /**
     * Create post data for Easypaisa
     *
     * @param int $amount
     * @param string $txnRefNo
     * @param string $phone
     * @param string $email
     * @return array
     */
    private function createEasypaisaPostData(int $amount, string $txnRefNo, string $phone, string $email): array
    {
        $date = Carbon::now();
        $expiryDate = $date->addHours(10)->format('Ymd His');  //YYYYMMDD HHMMSS
        
        return [
            "storeId" => env('EASYPAISA_PRODUCTION_STOREID'),
            "transactionAmount" => $amount . '.0',
            'orderId' => $txnRefNo,
            'expiryDate' => $expiryDate,
            'transactionType' => 'MA',
            'mobileAccountNo' => $phone,
            'emailAddress' => $email,
        ];
    }

    /**
     * Generate common transaction data for payment methods
     *
     * @param int $amount
     * @param bool $isJazzCash
     * @return array
     */
    private function generateTransactionData(int $amount, bool $isJazzCash = true): array
    {
        $DateTime = new \DateTime();
        $pp_TxnDateTime = $DateTime->format('YmdHis');
        $pp_TxnRefNo = 'T'.$pp_TxnDateTime . substr(uniqid(), -5);
        
        return [
            'amount' => $isJazzCash ? $amount * 100 : $amount,
            'dateTime' => $pp_TxnDateTime,
            'referenceNumber' => $pp_TxnRefNo
        ];
    }

    /**
     * Initialize order processing
     *
     * @param Request $request
     * @param string $pp_TxnRefNo
     * @return Transaction
     * @throws \Exception
     */
    public function orderInitialProcess(Request $request, string $pp_TxnRefNo): Transaction
    {
        $requestId = $request->request_id ?? uniqid();
        /*Log::channel('payout')->info('[TestPaymentService] Initiating order process', [
            'request_id' => $requestId,
            'service_step' => 'order_init',
            'transaction_ref' => $pp_TxnRefNo,
            'client_email' => $request->client_email
        ]);*/

        try {
            // Use the user from request instead of querying the database
            $user = $request->user_model ?? $request->user;
            
            if (!$user) {
                /*Log::channel('error')->error('[TestPaymentService] User model not found in request', [
                    'request_id' => $requestId,
                    'client_email' => $request->client_email,
                    'transaction_ref' => $pp_TxnRefNo
                ]);*/
                throw new \Exception('User model not found in request');
            }
            
            /*// Check for existing transaction
            $existingTransaction = $this->findExistingTransaction($request->orderId, $user->id);
            if ($existingTransaction) {
                return $existingTransaction;
            }*/
            
            // Create new transaction
            $transaction = $this->createTransaction($user->id, $pp_TxnRefNo, $request);

            /*Log::channel('payout')->info('[TestPaymentService] Order initialized successfully', [
                'request_id' => $requestId,
                'service_step' => 'order_created',
                'transaction_ref' => $pp_TxnRefNo,
                'transaction_id' => $transaction->id,
                'status' => 'pending'
            ]);*/

            return $transaction;
        } catch (\Exception $e) {
            Log::channel('error')->error('[TestPaymentService] Order initialization failed', [
                'request_id' => $requestId,
                'service_step' => 'order_init_failed',
                'error' => $e->getMessage(),
                'transaction_ref' => $pp_TxnRefNo,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Find existing transaction
     *
     * @param string $orderId
     * @param int $userId
     * @return Transaction|null
     */
    private function findExistingTransaction(string $orderId, int $userId): ?Transaction
    {
        $existingTransaction = Transaction::where('orderId', $orderId)
            ->where('user_id', $userId)
            ->first();
            
        if ($existingTransaction) {
            $requestId = request()->request_id ?? uniqid();
            Log::channel('payout')->warning('[TestPaymentService] Duplicate transaction attempt', [
                'request_id' => $requestId,
                'order_id' => $orderId,
                'user_id' => $userId,
                'existing_transaction_id' => $existingTransaction->id
            ]);
        }
        
        return $existingTransaction;
    }

    /**
     * Create a new transaction
     *
     * @param int $userId
     * @param string $txnRefNo
     * @param Request $request
     * @return Transaction
     * @throws \Exception
     */
    private function createTransaction(int $userId, string $txnRefNo, Request $request): Transaction
    {
        $values = [
            'user_id' => $userId,
            'txn_ref_no' => $txnRefNo,
            'amount' => $request->amount,
            'orderId' => $request->orderId,
            'status' => 'pending',
            'txn_type' => $request->payment_method,
            'phone' => $request->phone,
            'url' => $request->callback_url
        ];
          
        try {
            return Transaction::create($values);
        } catch (QueryException $e) {
            // Handle duplicate key exceptions
            if ($e->errorInfo[1] == 1062) { // MySQL error code for duplicate key
                $requestId = $request->request_id ?? uniqid();
                Log::channel('error')->warning('[TestPaymentService] Duplicate transaction attempt detected', [
                    'request_id' => $requestId,
                    'error' => $e->getMessage(),
                    'transaction_ref' => $txnRefNo,
                    'user_id' => $userId,
                    'orderId' => $request->orderId,
                    'trace' => $e->getTraceAsString()
                ]);
                
                throw new \Exception('Order ID already exists. Please use a different order ID.', 409);
            }
            throw $e; // Re-throw other exceptions
        }
    }

    /**
     * Process final order status
     *
     * @param mixed $request
     * @param string $RefNum
     * @param string $type
     * @param User|null $user
     * @param Transaction|null $transaction
     * @return Transaction|null
     */
    public function orderFinalProcess($request, string $RefNum, string $type, ?User $user = null, ?Transaction $transaction = null): ?Transaction
    {
        $requestId = request()->request_id ?? uniqid();
        /*Log::channel('payout')->info('[TestPaymentService] Processing final order status', [
            'request_id' => $requestId,
            'service_step' => 'final_process_init',
            'transaction_ref' => $RefNum,
            'payment_type' => $type
        ]);*/

        // Find transaction if not provided
        $transaction = $this->findTransaction($request, $RefNum, $transaction);
        
        if (!$transaction) {
            /*Log::channel('payout')->error('[TestPaymentService] Transaction not found', [
                'request_id' => $requestId,
                'service_step' => 'transaction_not_found',
                'transaction_ref' => $RefNum,
                'payment_type' => $type
            ]);*/
            return null;
        }
        
        // Process based on payment type
        if ($type == "jazzcash") {
            return $this->processJazzcashResult($request, $transaction, $RefNum, $requestId, $user);
        } else {
            return $this->processEasypaisaResult($request, $transaction, $RefNum, $requestId, $user);
        }
    }

    /**
     * Find transaction from request or database
     *
     * @param mixed $request
     * @param string $refNum
     * @param Transaction|null $transaction
     * @return Transaction|null
     */
    private function findTransaction($request, string $refNum, ?Transaction $transaction = null): ?Transaction
    {
        // Use transaction from request if available, otherwise look it up
        if (!$transaction && isset($request->transaction_model)) {
            $transaction = $request->transaction_model;
        }
        
        // Only query the database if we don't have the transaction model
        if (!$transaction) {
            $transaction = Transaction::where('txn_ref_no', $refNum)->first();
        }
        
        return $transaction;
    }

    /**
     * Process JazzCash result
     *
     * @param mixed $request
     * @param Transaction $transaction
     * @param string $refNum
     * @param string $requestId
     * @param User|null $user
     * @return Transaction
     */
    private function processJazzcashResult($request, Transaction $transaction, string $refNum, string $requestId, ?User $user = null): Transaction
    {
        if (isset($request->pp_ResponseCode) && $request->pp_ResponseCode == '000') {
            Log::channel('payout')->info('[TestPaymentService] JazzCash payment successful', [
                'request_id' => $requestId,
                'service_step' => 'jazzcash_success',
                'transaction_ref' => $refNum,
                'response_code' => $request->pp_ResponseCode,
                'response_message' => $request->pp_ResponseMessage
            ]);

            $transaction->update([
                'status' => 'success',
                'pp_code' => $request->pp_ResponseCode, 
                'pp_message' => $request->pp_ResponseMessage,
                'transactionId' => $request->ppmpf_1
            ]);
            
            // Refresh the model to get updated attributes
            $transaction->refresh();
            
            // Process user balance and send callback
            $this->handleSuccessfulTransaction($transaction, 'jazzcash', $requestId, $user);
        } else {
            Log::channel('payout')->warning('[TestPaymentService] JazzCash payment failed', [
                'request_id' => $requestId,
                'service_step' => 'jazzcash_failed',
                'transaction_ref' => $refNum,
                'response_code' => $request->pp_ResponseCode,
                'response_message' => $request->pp_ResponseMessage,
                'complete_response' => json_encode($request)
            ]);

            $transaction->update([
                'status' => 'failed',
                'pp_code' => $request->pp_ResponseCode, 
                'pp_message' => $request->pp_ResponseMessage,
                'transactionId' => $request->ppmpf_1
            ]);
            
            // Refresh the model to get updated attributes
            $transaction->refresh();
        }
        
        return $transaction;
    }

    /**
     * Process Easypaisa result
     *
     * @param mixed $request
     * @param Transaction $transaction
     * @param string $refNum
     * @param string $requestId
     * @param User|null $user
     * @return Transaction
     */
    private function processEasypaisaResult($request, Transaction $transaction, string $refNum, string $requestId, ?User $user = null): Transaction
    {
        if ($request['responseCode'] == '0000') {
            Log::channel('payout')->info('[TestPaymentService] Easypaisa payment successful', [
                'request_id' => $requestId,
                'service_step' => 'easypaisa_success',
                'transaction_ref' => $refNum,
                'response_code' => $request['responseCode'],
                'response_desc' => $request['responseDesc']
            ]);

            $transaction->update([
                'status' => 'success',
                'pp_code' => $request['responseCode'], 
                'pp_message' => $request['responseDesc'],
                'transactionId' => $request['transactionId']
            ]);
            
            // Refresh the model to get updated attributes
            $transaction->refresh();
            
            // Process user balance and send callback
            $this->handleSuccessfulTransaction($transaction, 'easypaisa', $requestId, $user);
        } else {
            Log::channel('payout')->warning('[TestPaymentService] Easypaisa payment failed', [
                'request_id' => $requestId,
                'service_step' => 'easypaisa_failed',
                'transaction_ref' => $refNum,
                'response_code' => $request['responseCode'],
                'response_desc' => $request['responseDesc'],
                'complete_response' => json_encode($request)
            ]);

            $transaction->update([
                'status' => 'failed',
                'pp_code' => $request['responseCode'], 
                'pp_message' => $request['responseDesc']
            ]);
            
            // Refresh the model to get updated attributes
            $transaction->refresh();
        }
        
        return $transaction;
    }

    /**
     * Handle successful transaction - update user balance and send callback
     *
     * @param Transaction $transaction
     * @param string $type
     * @param string $requestId
     * @param User|null $user
     * @return void
     */
    private function handleSuccessfulTransaction(Transaction $transaction, string $type, string $requestId, ?User $user = null): void
    {
        $userRates = config('payment.user_rates', [
            18 => 0.9685,
            19 => 0.971,
            28 => 0.963,
            30 => 0.963,
        ]);
        
        if (array_key_exists($transaction->user_id, $userRates)) {
            $this->processUserBalance($transaction, $userRates, $type, $requestId, $user);
        }
        
        // Send callback
        $this->sendCallback(
            $transaction->url, 
            [
                'orderId' => $transaction->orderId,
                'amount' => $transaction->amount,
                'status' => $transaction->status,
            ], 
            $requestId
        );
    }

    /**
     * Process user balance
     *
     * @param Transaction $transaction
     * @param array $userRates
     * @param string $type
     * @param string $requestId
     * @param User|null $user
     * @return bool
     */
    private function processUserBalance(Transaction $transaction, array $userRates, string $type, string $requestId, ?User $user = null): bool
    {
        try {
            $rate = $user->per_payin_fee;
            $amount = $transaction->amount * $rate;
        
            // Get settings and surplus
            list($setting, $surplus) = $this->getSettingsAndSurplus($transaction->user_id, $user, $requestId);
            
            if (!$setting || !$surplus) {
                return false;
            }
            
            // Update balances based on payment type
            $this->updateBalances($setting, $surplus, $amount, $type);

            //Log::channel('payout')->info('[TestPaymentService] User balance updated successfully', [
            //    'request_id' => $requestId,
            //    'user_id' => $transaction->user_id,
            //    'amount' => $amount,
            //    'type' => $type
            //]);
            
            return true;
        } catch (\Exception $e) {
            Log::channel('error')->error('[TestPaymentService] Balance update failed', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'user_id' => $transaction->user_id,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Get settings and surplus
     *
     * @param int $userId
     * @param User|null $user
     * @param string $requestId
     * @return array
     */
    private function getSettingsAndSurplus(int $userId, ?User $user, string $requestId): array
    {
        try {
            $setting = null;
            
            // If user model is provided, refresh it
            if ($user) {
                $user->refresh();
                
                if (!$user->relationLoaded('setting')) {
                    $user->load('setting');
                }
                
                $setting = $user->setting;
                
                if (!$setting) {
                    // If setting not found through relationship, try direct query
                    $setting = Setting::where('user_id', $userId)->first();
                }
            } else {
                // Fallback to database query
                $setting = Setting::where('user_id', $userId)->first();
            }
            
            // Get surplus
            $surplus = SurplusAmount::find(1);
            
            if (!$setting || !$surplus) {
                Log::channel('error')->error('[PaymentService] Settings or surplus not found', [
                    'request_id' => $requestId,
                    'user_id' => $userId,
                    'setting_found' => $setting ? true : false,
                    'surplus_found' => $surplus ? true : false
                ]);
                return [null, null];
            }
            
            return [$setting, $surplus];
        } catch (\Exception $e) {
            Log::channel('error')->error('[PaymentService] Error retrieving settings/surplus', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            
            return [null, null];
        }
    }

    /**
     * Update balances based on payment type
     *
     * @param Setting $setting
     * @param SurplusAmount $surplus
     * @param float $amount
     * @param string $type
     * @return void
     */
    private function updateBalances(Setting $setting, SurplusAmount $surplus, float $amount, string $type): void
    {
        try {
            if ($type == "jazzcash") {
                $setting->jazzcash += $amount;
                $surplus->jazzcash -= $amount;
            } else {
                $setting->easypaisa += $amount;
                $surplus->easypaisa -= $amount;
            }
            
            $setting->payout_balance += $amount;
            
            // Force the save operations
            $result1 = $setting->save();
            $result2 = $surplus->save();
            
            // Log successful update
            //Log::channel('payout')->info('[PaymentService] Balances updated', [
            //    'setting_id' => $setting->id,
            //    'setting_save_result' => $result1,
            //    'surplus_id' => $surplus->id,
            //    'surplus_save_result' => $result2,
            //    'type' => $type,
            //    'amount' => $amount
            //]);
            
            if (!$result1 || !$result2) {
                Log::channel('error')->error('[PaymentService] One or more models failed to save', [
                    'setting_save_result' => $result1,
                    'surplus_save_result' => $result2
                ]);
            }
        } catch (\Exception $e) {
            // Log the error
            Log::channel('error')->error('[PaymentService] Failed to update balances', [
                'error' => $e->getMessage(),
                'setting_id' => $setting->id,
                'surplus_id' => $surplus->id,
                'type' => $type,
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Send callback notification
     *
     * @param string $url
     * @param array $data
     * @param string $requestId
     * @param int|null $retries
     * @return bool
     */
    private function sendCallback(string $url, array $data, string $requestId, ?int $retries = null): bool
    {
        $maxRetries = $retries ?? config('payment.callback.max_retries', 3);
        $timeout = config('payment.callback.timeout', 120);
        
        $attempt = 0;
        while ($attempt < $maxRetries) {
            try {
                Log::channel('payout')->info('[TestPaymentService] Sending callback notification', [
                    'request_id' => $requestId,
                    'attempt' => $attempt + 1,
                    'url' => $url,
                    'data' => $data
                ]);
                
                $response = Http::timeout($timeout)->post($url, $data);
                
                if ($response->successful()) {
                    Log::channel('payout')->info('[TestPaymentService] Callback successful', [
                        'request_id' => $requestId,
                        'attempt' => $attempt + 1,
                        'status_code' => $response->status(),
                        'url' => $url
                    ]);
                    return true;
                }
                
                Log::channel('payout')->warning('[TestPaymentService] Callback returned non-success status', [
                    'request_id' => $requestId,
                    'attempt' => $attempt + 1,
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                    'url' => $url
                ]);
            } catch (\Exception $e) {
                Log::channel('error')->error('[TestPaymentService] Callback attempt failed', [
                    'request_id' => $requestId,
                    'attempt' => $attempt + 1,
                    'error' => $e->getMessage(),
                    'url' => $url,
                    'trace' => $e->getTraceAsString()
                ]);
            }
            
            $attempt++;
            if ($attempt < $maxRetries) {
                // Exponential backoff: 2^attempt seconds (2, 4, 8...)
                sleep(pow(2, $attempt));
            }
        }
        
        Log::channel('error')->error('[TestPaymentService] All callback attempts failed', [
            'request_id' => $requestId,
            'max_attempts' => $maxRetries,
            'url' => $url
        ]);
        
        return false;
    }

    /**
     * Create a transaction for a blocked number attempt
     *
     * @param Request $request
     * @param string $paymentMethod
     * @return Transaction
     * @throws \Exception
     */
    public function createBlockedTransaction(Request $request, string $paymentMethod): Transaction
    {
        $DateTime = new \DateTime();
        $pp_TxnDateTime = $DateTime->format('YmdHis');
        $pp_TxnRefNo = 'T'.$pp_TxnDateTime . substr(uniqid(), -5);

        try {
            return Transaction::create([
                'user_id' => $request->user_model->id,
                'txn_ref_no' => $pp_TxnRefNo,
                'amount' => $request->amount,
                'orderId' => $request->orderId,
                'status' => 'blocked',
                'txn_type' => $paymentMethod,
                'phone' => $request->phone,
                'url' => $request->callback_url,
                'pp_code' => 'BLOCKED',
                'pp_message' => 'Number is blocked'
            ]);
        } catch (QueryException $e) {
            // Handle duplicate key exceptions
            if ($e->errorInfo[1] == 1062) { // MySQL error code for duplicate key
                $requestId = $request->request_id ?? uniqid();
                Log::channel('error')->warning('[TestPaymentService] Duplicate blocked transaction attempt detected', [
                    'request_id' => $requestId,
                    'error' => $e->getMessage(),
                    'orderId' => $request->orderId,
                    'user_id' => $request->user_model->id,
                    'trace' => $e->getTraceAsString()
                ]);
                
                throw new \Exception('Order ID already exists. Please use a different order ID.', 409);
            }
            throw $e; // Re-throw other exceptions
        }
    }
} 