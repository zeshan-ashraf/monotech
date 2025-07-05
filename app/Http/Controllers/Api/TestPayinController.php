<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Service\TestPaymentService;
use Illuminate\Http\Request;
use App\Models\{Product,Client,Transaction,User};
use Illuminate\Support\Facades\Log;
use Zfhassaan\Easypaisa\Easypaisa;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use DB;

class TestPayinController extends Controller
{
    public $service;

    public function __construct(TestPaymentService $service)
    {
        $this->service = $service;
    }

    public function checkout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'regex:/^03[0-9]{9}$/'],
            'email' => 'required|email',
            'client_email' => 'required|email',
            'payment_method' => 'required|in:jazzcash,easypaisa',
            'amount' => 'required|numeric|min:1',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $user=User::where('email',$request->client_email)->first();
        if(($request->payment_method == "jazzcash" && $user->jc_api == 0) || ($request->payment_method == "easypaisa" && $user->ep_api == 0)){
            return response()->json([
                'status' => 'error',
                'message' => 'Api suspended by administrator.',
            ], 400);
        }
        else{
            try {
                    
                list($post_data, $type, $url) = $this->service->process($request);
                if ($type == "easypaisa") {
                    try {
                        $easypaisa = new Easypaisa;
                        $response = $easypaisa->sendRequest($post_data);
                
                        // Decode the response into an array if needed
                        if ($response instanceof \Illuminate\Http\JsonResponse) {
                            $response = $response->getData(true);
                        }
                
                        // Validate the structure of the response
                        if (isset($response['responseCode'], $response['responseDesc'], $response['orderId'])) {
                            $responseCode = $response['responseCode'];
                            $responseDesc = $response['responseDesc'];
                            if ($responseCode == '0000') {
                                $transaction = $this->service->orderFinalProcess($response, $response['orderId'], 'easypaisa');
                                if ($transaction) {
                                    $url = $transaction->url;
                                    $data = [
                                        'orderId' => $transaction->orderId,
                                        'amount' => $transaction->amount,
                                        'status' => $transaction->status,
                                    ];
                
                                    try {
                                        Http::timeout(120)->post($url, $data);
                
                                        return response()->json([
                                            'status' => $transaction->status,
                                            'transaction_id' => $transaction->txn_ref_no,
                                            'message' => 'Payment checkout initiated successfully.',
                                        ], 200);
                                    } catch (\Exception $e) {
                                        return response()->json([
                                            'status' => 'error',
                                            'message' => 'Error notifying the callback URL.',
                                        ], 500);
                                    }
                                }
                            }
                            
                            $this->service->orderFinalProcess($response, $response['orderId'], 'easypaisa');
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Payment checkout cannot be processed, please try again.',
                            ], 400);
                        }
                
                        // Invalid response structure
                        Log::error('Unexpected Easypaisa response:', ['response' => $response]);
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Invalid response from Easypaisa.',
                        ], 500);
                    } catch (\Exception $e) {
                        Log::error('Error processing Easypaisa payment: ' . $e->getMessage());
                        return response()->json([
                            'status' => 'error',
                            'message' => 'An error occurred while processing the payment.',
                        ], 500);
                    }
                } else {
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
                        CURLOPT_SSL_VERIFYPEER => true,
                        CURLOPT_CAINFO => public_path('jazz_public_key/new-cert.crt'),
                    ]);
        
                    $response = curl_exec($curl);
                    if ($response === false) {
                        $error = curl_error($curl);
                        curl_close($curl);
                        throw new \Exception('CURL Error: ' . $error);
                    }
        
                    curl_close($curl);
                    $result = json_decode($response, false);
                    if (isset($result->pp_ResponseCode) && $result->pp_ResponseCode == '000') {
                        $transaction = $this->service->orderFinalProcess($result, $result->pp_TxnRefNo, 'jazzcash');
                        if ($transaction) {
                            $url =$transaction->url;
                            $data = [
                                'orderId' => $transaction->orderId,
                                'amount' => $transaction->amount,
                                'status' => $transaction->status,
                            ];
        
                            try {
                                $response = Http::timeout(120)->post($url, $data);
                                
                                return response()->json([
                                    'status' => $transaction->status,
                                    'transaction_id' => $transaction->txn_ref_no,
                                    'message' => 'Payment checkout initiated successfully.',
                                ], 200);
                            } catch (\Exception $e) {
                                \Log::error('Error posting to notifyurl: ' . $e->getMessage());
                            }
                        }
                    }else{
                        $transaction = $this->service->orderFinalProcess($result, $result->pp_TxnRefNo, 'jazzcash');
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Payment checkout cannot be processed, please try again.',
                        ], 400);
                    }
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Payment checkout cannot be processed, please try again.',
                    ], 400);
                }
        
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'An error occurred during the transaction. Please try again.',
                ], 500);
            }
        }
    }
}