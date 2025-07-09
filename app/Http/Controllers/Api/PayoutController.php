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
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use App\Models\{Transaction, Payout, User, Setting, SurplusAmount};

class PayoutController extends Controller
{
    public $service;
    protected $logger;

	public function __construct(PaymentService $service) 
	{
		$this->service = $service;
        $this->logger = Log::channel('payout');
	}
    public function checkout(Request $request)
    {
        $requestId = uniqid('req_');
        $startTime = microtime(true);
        
        $this->logger->info('Starting payout checkout process', [
            'request_id' => $requestId,
            'request_data' => $request->all(),
            'timestamp' => now()->toDateTimeString()
        ]);

        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'client_email' => 'required|email',
            'payout_method' => 'required|in:jazzcash,easypaisa',
            'amount' => 'required|numeric|min:1',
        ]);
    
        if ($validator->fails()) {
            $this->logger->warning('Validation failed', [
                'request_id' => $requestId,
                'errors' => $validator->errors()->toArray(),
                'execution_time' => microtime(true) - $startTime
            ]);
            return response()->json(['errors' => $validator->errors()], 422);
        }
        // dd($request->all());
        $user= User::where('email',$request->client_email)->first();
        if(($request->payout_method == "jazzcash" && $user->payout_jc_api == 0) || ($request->payout_method == "easypaisa" && $user->payout_ep_api == 0)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payout Api suspended by administrator.',
            ], 400);
        }
        $orderId=Payout::where('orderId',$request->orderId)->first();
        if($orderId){
            $this->logger->warning('Duplicate order ID detected', [
                'request_id' => $requestId,
                'order_id' => $request->orderId,
                'execution_time' => microtime(true) - $startTime
            ]);

            $url =$request->callback_url;
            $call_data = [
                'orderId' => $request->orderId,
                'message' => 'Your payout cannot be processed due to Order Id already exist, please try again.',
                'status' => 'failed',
            ];
            $this->logger->info('Sending callback for duplicate order', [
                'request_id' => $requestId,
                'callback_url' => $url,
                'callback_data' => $call_data
            ]);
            $response = Http::timeout(60)->post($url, $call_data);
            $this->logger->info('Callback response for duplicate order', [
                'request_id' => $requestId,
                'response_status' => $response->status(),
                'response_body' => $response->json()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Your payout cannot be processed due to due to Order Id already exist, please try again.',
            ], 400);
        }
        else{
            $callback_url = $request->callback_url;
            if($user->email == "okpaysev@gmail.com"){
                $setting=Setting::where('user_id',$user->id)->first();
                $assigned_amount=0;
                if($request->payout_method == "easypaisa"){
                    $assigned_amount=$setting->easypaisa;
                }else {
                    $assigned_amount=$setting->jazzcash;
                }
                if($request->amount > $assigned_amount){
                    $values=[
                        'user_id' => $user->id,
                        'code' => "Nova-Failed",
                        'message' => "Merchant assigned limit breached",
                        'transaction_reference' => "",
                        'amount' => $request->amount,
                        'orderId' => $request->orderId,
                        'fee' => "",
                        'phone' => $request->phone,
                        'transaction_type' => $request->payout_method,
                        'status' => 'failed',
                        'url' => $request->callback_url,
                    ];
                    Payout::create($values);
                    $url =$callback_url;
                    $call_data = [
                        'orderId' => $request->orderId,
                        'message' => 'Your payout cannot be processed due to not enough balance , please try again.',
                        'status' => 'failed',
                    ];
                    $response = Http::timeout(60)->post($url, $call_data);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Merchant assigned limit breached',
                    ], 400);
                }
            }
            else{
                $setting=Setting::where('user_id',$user->id)->first();
                $assigned_amount=0;
                if($request->payout_method == "easypaisa"){
                    $assigned_amount=$setting->easypaisa;
                }else {
                    $assigned_amount=$setting->jazzcash;
                }
                if($request->amount > $assigned_amount){
                    $values=[
                        'user_id' => $user->id,
                        'code' => "Nova-Failed",
                        'message' => "Merchant assigned limit breached",
                        'transaction_reference' => "",
                        'amount' => $request->amount,
                        'orderId' => $request->orderId,
                        'fee' => "",
                        'phone' => $request->phone,
                        'transaction_type' => $request->payout_method,
                        'status' => 'failed',
                        'url' => $request->callback_url,
                    ];
                    Payout::create($values);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Merchant assigned limit breached',
                    ], 400);
                }
            }
            
            if($request->payout_method == "easypaisa"){
                $this->logger->info('Processing Easypaisa payout', [
                    'request_id' => $requestId,
                    'amount' => $request->amount,
                    'phone' => $request->phone
                ]);

                $clientId = env('EASYPAY_CLIENT_ID');
                $clientSecret = env('EASYPAY_CLIENT_SECRET');
                $channel = env('EASYPAY_CHANNEL');
                
                $timeStamp=$this->getTimeStamp($clientId,$clientSecret,$channel);
                $xHashValue=$this->getXHashValue($timeStamp);
        
                $msisdn=env('EASYPAY_MSISDN');
                $transfer_url=env('EASYPAY_MATOMA_TRANSFER_URL');
                
                $curl = curl_init();
                $payload = [
                    "Amount" => (float) $request->amount,
                    "MSISDN" => $msisdn,
                    "ReceiverMSISDN" => $request->phone,
                ];
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
            
                $response = curl_exec($curl);
                
                if ($response === false) {
                    $error = curl_error($curl);
                    curl_close($curl);
                    return response()->json(['error' => $error], 500);
                }
        
                curl_close($curl);
                $data = json_decode($response, true);

                $this->logger->info('Easypaisa API response', [
                    'request_id' => $requestId,
                    'response' => $data
                ]);

                $values=[
                    'user_id' => $user->id,
                    'code' => $data['ResponseCode'],
                    'message' => $data['ResponseMessage'],
                    'transaction_reference' => $data['TransactionReference'] ?? "",
                    'amount' => $request->amount,
                    'orderId' => $request->orderId,
                    // 'fee' => $data['Fee'] ?? "",
                    'phone' => $request->phone,
                    'transaction_type' => $request->payout_method,
                    'status' => $data['ResponseCode'] === '0' && $data['ResponseMessage'] === 'Success' ? 'success' : 'failed',
                    'url' => $request->callback_url,
                ];
                $transaction=Payout::create($values);
                if($data['ResponseCode'] === '0' && $data['TransactionStatus'] === 'success'){
                    $this->logger->info('Easypaisa payout successful, sending callback', [
                        'request_id' => $requestId,
                        'transaction_id' => $transaction->id
                    ]);

                    $url =$callback_url;
                    $call_data = [
                        'orderId' => $request->orderId,
                        'tid' => $request->transaction_reference,
                        'amount' => $transaction->amount,
                        'status' => 'success',
                    ];
                    $this->logger->info('Sending success callback', [
                        'request_id' => $requestId,
                        'callback_url' => $url,
                        'callback_data' => $call_data
                    ]);

                    $setting = Setting::where('user_id', $user->id)->first();

                    if ($setting && $user->per_payout_fee) {
                        $rate = $user->per_payout_fee;
                        $amount = $request->amount * $rate;
                    
                        $setting->easypaisa -= $amount;
                        $setting->payout_balance -= $amount;
                        $setting->save();
                    }
                    $response = Http::timeout(60)->post($url, $call_data); // increased timeout
    
                    $this->logger->info('Callback response received', [
                        'request_id' => $requestId,
                        'response_status' => $response->status(),
                        'response_body' => $response->json()
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Payout processed successfully.',
                        'transaction_id' => $values['transaction_reference'],
                    ], 200);
                }else{
                    $this->logger->warning('Easypaisa payout failed, sending callback', [
                        'request_id' => $requestId,
                        'error_code' => $data['ResponseCode'],
                        'error_message' => $data['ResponseMessage']
                    ]);

                    $url =$callback_url;
                    $call_data = [
                        'orderId' => $request->orderId,
                        'tid' => $request->transaction_reference,
                        'message' => 'Your payout cannot be processed due to '. $data['ResponseMessage']. ' , please try again.',
                        'status' => 'failed',
                    ];
                    $this->logger->info('Sending failure callback', [
                        'request_id' => $requestId,
                        'callback_url' => $url,
                        'callback_data' => $call_data
                    ]);
                    $response = Http::timeout(60)->post($url, $call_data);
                    $this->logger->info('Callback response received', [
                        'request_id' => $requestId,
                        'response_status' => $response->status(),
                        'response_body' => $response->json()
                    ]);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Your payout cannot be processed due to '. $data['ResponseMessage']. ' , please try again.',
                    ], 400);
                }
            }
            else{
                $data=$request->all();
                $token=$this->getToken();
                $encryptionData=$this->encryptionFunc($request->all());
                $transactionUrl=env('JAZZCASH_MATOMA_URL');
                // dd($transactionUrl);
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
                
                $response = curl_exec($curl);
                
                curl_close($curl);
                $decodeData=json_decode($response, true);
                $decrptionData=$this->decrytionFunc($decodeData['data']);
                $data=json_decode($decrptionData, true);
                $values=[
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
                $transaction=Payout::create($values);
                if($data['responseCode'] === 'G2P-T-0'){
                    $this->logger->info('Jazzcash payout successful, sending callback', [
                        'request_id' => $requestId,
                        'transaction_id' => $transaction->id
                    ]);

                    $call_url =$callback_url;
                    $call_data = [
                        'orderId' => $request->orderId,
                        'tid' => $request->transaction_reference,
                        'amount' => $transaction->amount,
                        'status' => 'success',
                    ];
                    $this->logger->info('Sending success callback', [
                        'request_id' => $requestId,
                        'callback_url' => $call_url,
                        'callback_data' => $call_data
                    ]);
                    $userRates = [
                        2 => 1.015,
                        4 => 1.02,
                    ];
                    
                    $setting = Setting::where('user_id', $user->id)->first();
                    //Log::debug('********user settings found', [
                    //    'response' => $setting,
                    //    'user payout fee' => $user,
                    //]);
                    if ($setting && $user->per_payout_fee) {
                        $rate = $user->per_payout_fee;
                        $amount = $request->amount * $rate;
                    
                        $setting->jazzcash -= $amount;
                        $setting->payout_balance -= $amount;
                        $setting->save();
                    }
                    else{
                        Log::debug('*******unable to update wallet');
                        
                    }
                    $response = Http::timeout(60)->post($call_url, $call_data); // increased timeout
                    
                    $this->logger->info('Callback response received', [
                        'request_id' => $requestId,
                        'response_status' => $response->status(),
                        'response_body' => $response->json()
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Payout processed successfully.',
                        'transaction_id' => $values['transaction_reference'],
                    ], 200);
                }else{
                    $this->logger->warning('Jazzcash payout failed, sending callback', [
                        'request_id' => $requestId,
                        'error_code' => $data['responseCode'],
                        'error_message' => $data['responseDescription']
                    ]);

                    $url =$callback_url;
                    $call_data = [
                        'orderId' => $request->orderId,
                        'message' => 'Your payout cannot be processed due to '. $data['responseDescription']. ' , please try again.',
                        'status' => 'failed',
                    ];
                    $this->logger->info('Sending failure callback', [
                        'request_id' => $requestId,
                        'callback_url' => $url,
                        'callback_data' => $call_data
                    ]);
                    $response = Http::timeout(60)->post($url, $call_data);
                    $this->logger->info('Callback response received', [
                        'request_id' => $requestId,
                        'response_status' => $response->status(),
                        'response_body' => $response->json()
                    ]);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Your payout cannot be processed due to '. $data['responseDescription']. ' , please try again.',
                    ], 400);
                }
            }
        }
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
            "LoginPayload":"AR0j+stHw8+9OTQ2xxM4ubJ3w/Z5V7OMImla/8J2uyWVUXt8Pfogi21CuOMNoPkj0FFdqKYbmaJMEbYu+hsqqYC3SfDSCPlWwtMQt14uiAYP8MJsMDxo/Yjk0lrtcplkGt3z4PZRWBnehnpv+qCLjzA/S55ctTNe8QICDazC6F8mheHCTEzdImEn3lTo+TrvbEYKh/SqodbQ0zHOLJvzvcjjfNHn8xuIj3SW7HY2afdxxIsyY2AhrPzBbtDutrdk2d6qcFBCmWPAbEFrF1nlMMYMCiBCW9Lrx+Mlb+CqpKcpH5+yQ4JPggso57QYKt2KYgB5s84dqnJvUldNJCFU0w=="
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
    public function getXHashValue($timeStamp)
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
    public function getToken()
    {
        $url = env('JAZZCASH_GET_TOKEN_URL');
        $token = 'Basic ' . env('JAZZCASH_TOKEN');
        $curl = curl_init();
    
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
                "Authorization: $token",
            ),
        ));
    
        $response = curl_exec($curl);
    
        if (curl_errno($curl)) {
            // Log error if needed
            echo 'Error: ' . curl_error($curl);
        }
    
        curl_close($curl);
        $data=json_decode($response, true);
        $accessToken=$data['access_token'];
        return $accessToken;
    }
    public function encryptionFunc($data)
    {
        $DateTime 		= new \DateTime();
		$pp_TxnDateTime = $DateTime->format('YmdHis');
		$pp_TxnRefNo = 'T'.$pp_TxnDateTime . substr(uniqid(), -5);
		
        $encodeData = json_encode([
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
}
