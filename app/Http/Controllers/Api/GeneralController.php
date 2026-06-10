<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\{Transaction,ArcheiveTransaction,BackupTransaction,Payout,ArcheivePayout,Summary,Setting,Settlement,User,SurplusAmount,WalletTransfer};
use Illuminate\Database\QueryException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GeneralController extends Controller
{
    public function index(Request $request)
    {
        $url = 'https://marketmaven.com.pk/api/get-transactions';

        // Sending the request
        $response = Http::get($url);
        $data = $response->json();
        foreach($data['data'] as $item){
            try {
                $transaction = Transaction::create([
                    'orderId' => $item['orderId'],
                    'amount' => $item['amount'],
                    'txn_ref_no' => $item['txn_ref_no'],
                    'transactionId' => $item['transactionId'],
                    'txn_type' => $item['txn_type'],
                    'status' => $item['status'],
                    'pp_code' => $item['pp_code'],
                    'pp_message' => $item['pp_message'],
                    'created_at' => $item['created_at'],
                    'updated_at' => $item['updated_at'],
                ]);
            } catch (QueryException $e) {
                // Handle duplicate key exceptions silently for external data sync
                if ($e->errorInfo[1] == 1062) {
                    // Log duplicate but don't throw error for external data
                    \Log::error('Duplicate orderId during external sync: external orderid=' . $item['orderId']);
                    continue;
                }
                throw $e; // Re-throw other exceptions
            }
        }
          
    }
    public function checkStatus(Request $request)
    {
        // Fetch transactions by orderId
        $order_details = Transaction::where('orderId', $request->orderId)->get();

        if ($order_details->isEmpty()) {
            $order_details = ArcheiveTransaction::where('orderId', $request->orderId)->get();
        }
        
        if ($order_details->isEmpty()) {
            $order_details = BackupTransaction::where('orderId', $request->orderId)->get();
        }
    
        // Find the first transaction with 'success' status
        $successful_transaction = $order_details->where('status', 'success')->first();
    
        // If no successful transaction is found, take the first transaction
        $transaction = $successful_transaction ?? $order_details->first();
    
        return response()->json(['order' => $transaction]);
    }
    public function checkPayoutStatus(Request $request)
    {
        // Fetch transactions by orderId
        $order_details = Payout::where('orderId', $request->orderId)->get();
        
        if ($order_details->isEmpty()) {
            $order_details = ArcheivePayout::where('orderId', $request->orderId)->get();
        }
        // Find the first transaction with 'success' status
        $successful_transaction = $order_details->where('status', 'success')->first();
    
        // If no successful transaction is found, take the first transaction
        $transaction = $successful_transaction ?? $order_details->first();
    
        return response()->json(['order' => $transaction]);
    }
    public function dashboardData(Request $request)
    {
        $user=User::where('email',$request->client_email)->first();
       
        
        $userId = $user->id;
        
        $epPayinAmount = Settlement::where('user_id', $userId)->whereDate('date', today())->value('ep_payin') ?? 0;
        $payinSuccess=Transaction::where('user_id',$userId)
            ->where('status','success')
            ->whereDate('created_at',today())
            ->sum('amount');
        $payoutSuccess=Payout::where('user_id',$userId)
            ->where('status','success')
            ->whereDate('created_at',today())
            ->sum('amount');
        $prevBal=Settlement::where('user_id',$userId)
            ->whereDate('date',today()->subDay()->format('Y-m-d'))
            ->select('closing_bal')
            ->value('closing_bal');
        $prevUsdt=Settlement::where('user_id',$userId)
            ->whereDate('date',today()->format('Y-m-d'))
            ->select('usdt')
            ->value('usdt');
        $assignedAmount=Setting::where('user_id',$userId)->select('jazzcash','easypaisa','payout_balance')->first();
        $payin_fee=$user->payin_fee;
        $payout_fee=$user->payout_fee;
        // Calculation for unsettled amount
        //if ($userId == 2) {
        //    $payinSuccess = $epPayinAmount;
        //} 
        $unSettledAmount= $prevBal + $payinSuccess - ($payinSuccess*$payin_fee + $payoutSuccess + $payoutSuccess*$payout_fee + $prevUsdt);
        $wallet = [
            "easypaisa" => number_format($assignedAmount->easypaisa),
            "jazzcash" => number_format($assignedAmount->jazzcash),
        ];
        return response()->json([
           /* 'Previous Balance' => number_format($prevBal),
            'Payin' => number_format($payinSuccess),
            'Payout' => number_format($payoutSuccess),
            'JC' => number_format($assignedAmount->jazzcash ?? 0),
            'EP' => number_format($assignedAmount->easypaisa ?? 0),
            'Total' => number_format($assignedAmount->payout_balance ?? 0),
            'USDT' => number_format($prevUsdt),*/
            /*'Previous Balance' => number_format($prevBal),
            'Payin success' => number_format($payinSuccess),
            'Payout success' => number_format($payoutSuccess),
            'USDT' => number_format($prevUsdt),
            'Payin fee' => number_format($payin_fee),
            'Payout fee' => number_format($payout_fee),*/
            'Unsettled (After Fee)' => number_format($unSettledAmount),
            'Wallet' => $wallet,
        ]);
    }

    public function dashboardDataV1(Request $request)
    {
       
        $user = $request->user;
        
        $userId = $user->id;
        $epPayinAmount = Settlement::where('user_id', $userId)->whereDate('date', today())->value('ep_payin') ?? 0;
        $payinSuccess=Transaction::where('user_id',$userId)
            ->where('status','success')
            ->whereDate('created_at',today())
            ->sum('amount');
        $payoutSuccess=Payout::where('user_id',$userId)
            ->where('status','success')
            ->whereDate('created_at',today())
            ->sum('amount');
        $prevBal=Settlement::where('user_id',$userId)
            ->whereDate('date',today()->subDay()->format('Y-m-d'))
            ->select('closing_bal')
            ->value('closing_bal');
        $prevUsdt=Settlement::where('user_id',$userId)
            ->whereDate('date',today()->format('Y-m-d'))
            ->select('usdt')
            ->value('usdt');
        $assignedAmount=Setting::where('user_id',$userId)->select('jazzcash','easypaisa','payout_balance')->first();
        $payin_fee=$user->payin_fee;
        $payout_fee=$user->payout_fee;
        // Calculation for unsettled amount
        if ($userId == 2) {
            $payinSuccess = $epPayinAmount;
        }
        $unSettledAmount= $prevBal + $payinSuccess - ($payinSuccess*$payin_fee + $payoutSuccess + $payoutSuccess*$payout_fee + $prevUsdt);
    
        return response()->json([
            'Previous Balance' => number_format($prevBal),
            'Payin' => number_format($payinSuccess),
            'Payout' => number_format($payoutSuccess),
            'JC' => number_format($assignedAmount->jazzcash ?? 0),
            'EP' => number_format($assignedAmount->easypaisa ?? 0),
            'Total' => number_format($assignedAmount->payout_balance ?? 0),
            'USDT' => number_format($prevUsdt),
            'Unsettled (After Fee)' => number_format($unSettledAmount),
        ]);
    }

    public function payoutData()
    {
        $todayOkJcPayout = DB::table('payouts')
            ->where('user_id', 2)
            ->where('status','success')
            ->where('transaction_type','jazzcash')
            ->whereDate('created_at', Carbon::today())
            ->sum('amount');

        return [
            'today_ok_jc_payout' => $todayOkJcPayout,
        ];
    }
    public function getPayinData()
    {
        $users = [4, 5];
        $results = [];
        
        foreach ($users as $userId) {
            $todayPayin = DB::table('transactions')
                ->where('user_id', $userId)
                ->where('txn_type','easypaisa')
                ->whereIn('status', ['success', 'reverse'])
                ->whereDate('created_at', Carbon::today())
                ->sum('amount');
        
            $todayTransReverse = DB::table('transactions')
                ->where('user_id', $userId)
                ->where('status', 'reverse')
                ->whereDate('updated_at', Carbon::today())
                ->sum('amount');
        
            $todayArcReverse = DB::table('archeive_transactions')
                ->where('user_id', $userId)
                ->where('status', 'reverse')
                ->whereDate('updated_at', Carbon::today())
                ->sum('amount');
        
            $todayBackReverse = DB::table('backup_transactions')
                ->where('user_id', $userId)
                ->where('status', 'reverse')
                ->whereDate('updated_at', Carbon::today())
                ->sum('amount');
        
            $todayReverse = $todayTransReverse + $todayArcReverse + $todayBackReverse;
        
            if ($userId == 4) {
                $todayPayinUserPiq   = $todayPayin;
                $todayReverseUserPiq = $todayReverse;
            // } elseif ($userId == 2) {
            //     $todayPayinUserOk   = $todayPayin;
            //     $todayReverseUserOk = $todayReverse;
            } elseif ($userId == 5) {
                $todayPayinUserPkn   = $todayPayin;
                $todayReverseUserPkn = $todayReverse;
            }
        }
        
        return [
            'today_payin_piq'   => $todayPayinUserPiq ?? 0,
            'today_reverse_piq' => $todayReverseUserPiq ?? 0,
            // 'today_payin_ok'   => $todayPayinUserOk ?? 0,
            // 'today_reverse_ok' => $todayReverseUserOk ?? 0,
            'today_payin_pkn'   => $todayPayinUserPkn ?? 0,
            'today_reverse_pkn' => $todayReverseUserPkn ?? 0,
        ];
        
    }

    public function getSettlementData()
    {
        $activeUserIds = User::where('user_role', 'Client')->where('active', 1)->pluck('id');
        $settlementData = Settlement::whereIn('user_id', $activeUserIds)
            ->whereDate('date', Carbon::today()->format('y-m-d'))
            ->get();
        $settingData=Setting::whereIn('user_id', $activeUserIds)->get();
        $surplusData=SurplusAmount::where('id', 1)->get();
        
        return [
            'settlements' => $settlementData,
            'settings'    => $settingData,
            'surplus'     => $surplusData,
        ];
    }
    public function getPrevDaySettlementData()
    {
        $activeUserIds = User::where('user_role', 'Client')->where('active', 1)->pluck('id');
        $settlementData = Settlement::whereIn('user_id', $activeUserIds)
            ->whereDate('date', Carbon::today()->subDay(1)->format('y-m-d'))
            ->get();
        
        return [
            'settlements' => $settlementData,
        ];
    }
    public function addWalletAmount(Request $request)
    {
        // if($request->user_id == "4"){
        //     $userId = 2;
        // }
        // else{

        // }

        if ($request->from_store_name == "Khushi Connect") {
            $userId = $request->user_id == "4" ? 2
                    : ($request->user_id == "3" ? 4
                    : ($request->user_id == "12" ? 25 : null));
        } else {
            $userId = $request->user_id == "19" ? 2 : ($request->user_id == "36" ? 4 : null);
        }
        $trans_amount=$request->trans_amount * -1;

        WalletTransfer::create([
            'date'        => $request->date,
            'time'        => $request->time,
            'user_id'     => $userId,
            'req_id'      => $request->req_id,
            'store_name'  => $request->from_store_name,
            'trans_amount'=> $trans_amount,
        ]);

        $summary=Settlement::where('user_id',$userId)->whereDate('date', Carbon::today()->format('y-m-d'))->first();
        $summary->update([
            'wallet_transfer' => $summary->wallet_transfer + ($trans_amount),
            'settled' => $summary->settled + ($trans_amount),
        ]);

        return response()->json(['status' => 'success']);
    }
    public function getCocktailData(Request $request)
    {
        // $request->validate([
        //     'usdt'=>'required',
        // ]);
        $user=User::where('email',$request->client_email)->first();
        
        $item = Settlement::where('user_id',$user->id)->whereDate('date', Carbon::today()->format('y-m-d'))->first();
        

        if($request->wallet_transfer > 0 && $request->store_name != "None"){
            $request->validate([
                'store_name'=>'required',
                'wallet_transfer'=>'required',
            ]);
            $date = now()->format('Y-m-d');
            $time = now()->format('H:i:s');
            $req_id = 'REQ-' . now()->format('YmdHis') . '-' . Str::random(6);
            
            if($request->store_name == "Khushi Connect"){
                $url = 'https://khushiconnect.com/api/add-wallet-transfer-amount';
            }else{
                $url = 'https://novapay.pk/api/add-wallet-transfer-amount';
            }
            $response = Http::timeout(10)->post($url, [
                'date'        => $date,
                'time'        => $time,
                'user_id'     => $item->user_id,
                'req_id'      => $req_id,
                'store_name'  => $request->store_name,
                'from_store_name' => "Monotech",
                'trans_amount'=> $request->wallet_transfer,
            ]);
    
            $result = $response->json();

            if ($result['status'] == 'success') {

                WalletTransfer::create([
                    'date'        => now()->format('Y-m-d'),
                    'time'        => now()->format('H:i:s'),
                    'user_id'     => $item->user_id,
                    'req_id'      => 'REQ-' . now()->format('YmdHis') . '-' . Str::random(6),
                    'store_name'  => $request->store_name,
                    'trans_amount'=> $request->wallet_transfer,
                ]);

            }
        }
        if($request->store_name == "None"){
            WalletTransfer::create([
                'date'        => now()->format('Y-m-d'),
                'time'        => now()->format('H:i:s'),
                'user_id'     => $item->user_id,
                'req_id'      => 'REQ-' . now()->format('YmdHis') . '-' . Str::random(6),
                'store_name'  => $request->store_name,
                'trans_amount'=> $request->wallet_transfer,
            ]);
        }

        $totalUsdt = $item->usdt+$request->usdt;
        $todayWalletTrans = $item->wallet_transfer+$request->wallet_transfer;
        $item->usdt = $totalUsdt;
        $item->wallet_transfer = $todayWalletTrans;
        $item->settled = $item->settled+$totalUsdt+$todayWalletTrans;

        $item->save();

        return response()->json(['status' => 'success']);
    }
    public function addSurplusCocktail(Request $request)
    {
        $surplus=SurplusAmount::where('id','1')->first();
        $surplus->jazzcash=$surplus->jazzcash+$request->jazzcash  * 0.995;
        $surplus->easypaisa=$surplus->easypaisa+$request->easypaisa * 0.9925;
        $surplus->save();

        return response()->json(['status' => 'success']);
    }
    public function addCocktailSettlements()
    {
        $activeUserIds = User::where('user_role', 'Client')
            ->where('active', 1)
            ->pluck('id');

        $settlementData = Settlement::whereIn('user_id', $activeUserIds)
            ->whereBetween('date', ['2026-03-01', '2026-05-14'])
            ->get();

        return [
            'settlements' => $settlementData,
        ];
    }
    public function novaPayout(Request $request)
    {
        // dd($request->all());
        // return response()->json($request->all());
        $clientId = env('EASYPAY_CLIENT_ID');
        $clientSecret = env('EASYPAY_CLIENT_SECRET');
        $channel = env('EASYPAY_CHANNEL');
        
        $timeStamp=$this->getTimeStamp($clientId,$clientSecret,$channel);
        $xHashValue=$this->getXHashValue($timeStamp);

        $msisdn=env('EASYPAY_MSISDN');
        $transfer_url=env('EASYPAY_MATOMA_TRANSFER_URL');
        
        $curl = curl_init();
        $payload = [
            "Amount" => (float) $request->data['amount'],
            "MSISDN" => $msisdn,
            "ReceiverMSISDN" => $request->data['phone'],
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

            return response()->json([
                'status' => false,
                'message' => $error
            ], 500);
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {

            return response()->json([
                'status' => false,
                'message' => 'Invalid JSON response',
                'raw_response' => $response
            ], 500);
        }

        return response()->json([
            'status' => true,
            'http_code' => $httpCode,
            'data' => $data
        ]);
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
    public function novaPayoutMMBL(Request $request)
    {    
        $data=$request->all();
        $token=$this->getToken();
        $encryptionData=$this->encryptionFunc($request->all());
        $transactionUrl=env('JAZZCASH_MATOIBFTINQ_URL');
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

            if($data['responseCode'] === 'G2P-T-0'){
                
                return response()->json([
                    'status' => true,
                    'data' => $data
                ]);
            }else{
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your payout cannot be processed due to '. $data['responseDescription']. ' , please try again.',
                ], 400);
            }
        }
        else{
            return response()->json([
                'status' => 'error',
                'message' => 'Your payout cannot be processed due to '. $data['responseDescription']. ' , please try again.',
            ], 400);
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
}