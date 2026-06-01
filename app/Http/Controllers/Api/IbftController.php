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

            if ($response === false) {
                $this->dumpCurlDiagnostics('ibft_inquiry', $curl, $response, [
                    'configured_url' => $transactionUrl,
                ]);
            }

            $inquiryHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            $decodeData = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                dd([
                    'step' => 'ibft_inquiry_json_decode',
                    'json_error' => json_last_error_msg(),
                    'http_code' => $inquiryHttpCode,
                    'raw_response' => $response,
                ]);
            }

            if (! isset($decodeData['data'])) {
                dd([
                    'step' => 'ibft_inquiry_missing_data_key',
                    'http_code' => $inquiryHttpCode,
                    'decoded_response' => $decodeData,
                    'raw_response' => $response,
                ]);
            }

            $decrptionData = $this->decrytionFunc($decodeData['data']);
            $data = json_decode($decrptionData, true);

            if ($data['responseCode'] == 'G2P-T-0') {
                $encryptionIbftData = $this->encryptionIbftFunc($data);
                $transactionConfirmUrl = env('JAZZCASH_MATOIBFTCONFIRM_URL');

                if (empty($transactionConfirmUrl)) {
                    dd([
                        'step' => 'ibft_confirm_env',
                        'error' => 'JAZZCASH_MATOIBFTCONFIRM_URL is empty or not set in .env',
                        'hint' => 'Run: php artisan config:clear after updating .env',
                    ]);
                }

                $confirmPayload = json_encode(['data' => $encryptionIbftData]);

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
                    CURLOPT_POSTFIELDS => $confirmPayload,
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json',
                        'Content-Type: application/json',
                        "Authorization: Bearer $token",
                    ],
                ]);

                $confirmResponse = curl_exec($curl_new);

                $confirmDebug = $this->buildCurlDiagnostics('ibft_confirm', $curl_new, $confirmResponse, [
                    'configured_url' => $transactionConfirmUrl,
                    'inquiry_response' => $data,
                    'confirm_plain_payload' => [
                        'Init_transactionID' => $data['transactionID'] ?? null,
                        'referenceID' => '(generated in encryptionIbftFunc)',
                    ],
                    'encrypted_payload_length' => strlen($encryptionIbftData),
                    'post_body_length' => strlen($confirmPayload),
                    'token_prefix' => substr($token, 0, 20) . '...',
                ]);

                $this->logger->info('IBFT confirm curl diagnostics', $confirmDebug);

                if ($confirmResponse === false) {
                    dd($confirmDebug);
                }

                $confirmHttpCode = curl_getinfo($curl_new, CURLINFO_HTTP_CODE);
                curl_close($curl_new);

                $confirmDebug['http_code'] = $confirmHttpCode;
                $confirmDebug['raw_response'] = $confirmResponse;

                $decodeData = json_decode($confirmResponse, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    dd(array_merge($confirmDebug, [
                        'decode_error' => json_last_error_msg(),
                    ]));
                }

                if (! isset($decodeData['data'])) {
                    dd(array_merge($confirmDebug, [
                        'decoded_response' => $decodeData,
                    ]));
                }

                $decrptionData = $this->decrytionFunc($decodeData['data']);
                $data = json_decode($decrptionData, true);

                dd(array_merge($confirmDebug, [
                    'decrypted_confirm_response' => $data,
                ]));
            }
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

    /**
     * Build a diagnostic array for cURL failures (transport-level).
     */
    private function buildCurlDiagnostics(string $step, $curl, $response, array $extra = []): array
    {
        return array_merge([
            'step' => $step,
            'curl_succeeded' => $response !== false,
            'curl_error' => curl_error($curl),
            'curl_errno' => curl_errno($curl),
            'http_code' => curl_getinfo($curl, CURLINFO_HTTP_CODE),
            'effective_url' => curl_getinfo($curl, CURLINFO_EFFECTIVE_URL),
            'total_time_seconds' => curl_getinfo($curl, CURLINFO_TOTAL_TIME),
            'primary_ip' => curl_getinfo($curl, CURLINFO_PRIMARY_IP),
            'ssl_verify_result' => curl_getinfo($curl, CURLINFO_SSL_VERIFYRESULT),
            'response_preview' => is_string($response) ? substr($response, 0, 500) : null,
        ], $extra);
    }

    /**
     * Log and dump cURL diagnostics when curl_exec returns false.
     */
    private function dumpCurlDiagnostics(string $step, $curl, $response, array $extra = []): void
    {
        $debug = $this->buildCurlDiagnostics($step, $curl, $response, $extra);
        curl_close($curl);
        $this->logger->error("IBFT curl failed: {$step}", $debug);
        dd($debug);
    }
}