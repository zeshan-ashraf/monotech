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

class IbftController extends Controller
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

        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'client_email' => 'required|email',
            'payout_method' => 'required|in:jazzcash,easypaisa',
            'amount' => 'required|numeric|min:1',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        // dd($request->all());
        $user= User::where('email',$request->client_email)->first();
        if(($request->payout_method == "jazzcash" && $user->payout_jc_api == 0) || ($request->payout_method == "easypaisa" && $user->payout_ep_api == 0)) {
            return response()->json([
                'status' => 'error',
                //'message' => 'Payout Api suspended by administrator.',
                'message' => 'Error:Daily limit exceeded.',
            ], 400);
        }
        $orderId=Payout::where('orderId',$request->orderId)->first();
        if($orderId){

            $url =$request->callback_url;
            $call_data = [
                'orderId' => $request->orderId,
                'message' => 'Your payout cannot be processed due to Order Id already exist, please try again.',
                'status' => 'failed',
            ];

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
            
            $data=$request->all();
            $token=$this->getToken();
            $encryptionData=$this->encryptionFunc($request->all());
            $transactionUrl=env('JAZZCASH_MATOIBFTINQ_URL');
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

            if($data['responseCode'] == "G2P-T-0"){
                $encryptionIbftData=$this->encryptionIbftFunc($data);
                $transactionConfirmUrl=env('JAZZCASH_MATOIBFTCONFIRM_URL');
                $curl_new = curl_init();
                curl_setopt_array($curl_new, [
                    CURLOPT_URL => $transactionConfirmUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => json_encode([
                        "data" => $encryptionIbftData,
                    ]),
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json',
                        'Content-Type: application/json',
                        "Authorization: Bearer $token",
                    ],
                ]);
                $response = curl_exec($curl_new);
                curl_close($curl_new);
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
                    'ibft' => '1',
                    // 'fee' => $data['Fee'] ?? "",
                    'phone' => $request->phone,
                    'transaction_type' => $request->payout_method,
                    'transaction_id' => $data['transactionID'] ?? "",
                    'status' => $data['responseCode'] === 'G2P-T-0' ? 'success' : 'failed',
                    'url' => $request->callback_url,
                ];
                $transaction=Payout::create($values);
                if($data['responseCode'] === 'G2P-T-0'){
    
                    $call_url =$callback_url;
                    $call_data = [
                        'orderId' => $request->orderId,
                        'tid' => $request->transaction_reference,
                        'amount' => $transaction->amount,
                        'status' => 'success',
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
    
                    return response()->json([
                        'success' => true,
                        'message' => 'Payout processed successfully.',
                        'transaction_id' => $values['transaction_reference'],
                    ], 200);
                }else{
                    $url =$callback_url;
                    $call_data = [
                        'orderId' => $request->orderId,
                        'message' => 'Your payout cannot be processed due to '. $data['responseDescription']. ' , please try again.',
                        'status' => 'failed',
                    ];
                    $response = Http::timeout(60)->post($url, $call_data);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Your payout cannot be processed due to '. $data['responseDescription']. ' , please try again.',
                    ], 400);
                }
            }
            else{
                $url =$callback_url;
                $call_data = [
                    'orderId' => $request->orderId,
                    'message' => 'Your payout cannot be processed due to '. $data['responseDescription']. ' , please try again.',
                    'status' => 'failed',
                ];
                $response = Http::timeout(60)->post($url, $call_data);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your payout cannot be processed due to '. $data['responseDescription']. ' , please try again.',
                ], 400);
            }
        }
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
        // dd($response);
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
        $phone = preg_replace('/^92/', '0', $data['phone']);
        $DateTime 		= new \DateTime();
		$pp_TxnDateTime = $DateTime->format('YmdHis');
		$pp_TxnRefNo = 'T'.$pp_TxnDateTime . substr(uniqid(), -5);
		
        $encodeData = json_encode([
            "receiverMSISDN" => $phone,
            "amount" => $data['amount'],
            "bankAccountNumber" => $phone,
            "bankCode" => "59",
            "referenceId" => $pp_TxnRefNo
        ]);
 
        $encryptionKey = env('JAZZCASH_SECRET_KEY');
        $iv = env('JAZZCASH_INITIAL_VECTOR');
    
        $encryptedData = openssl_encrypt($encodeData, 'AES-128-CBC', $encryptionKey, OPENSSL_RAW_DATA, $iv);
    
        $hexEncryptedData = bin2hex($encryptedData);
        return $hexEncryptedData;
    }
    public function encryptionIbftFunc($data)
    {
        $DateTime 		= new \DateTime();
		$pp_TxnDateTime = $DateTime->format('YmdHis');
		$pp_TxnRefNo = 'T'.$pp_TxnDateTime . substr(uniqid(), -5);
		
        $encodeData = json_encode([
            "Init_transactionID" => $data['transactionID'],
            "referenceID" => $pp_TxnRefNo
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