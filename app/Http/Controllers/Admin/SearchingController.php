<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\Admin\SearchingDataTable;
use App\DataTables\Admin\PayoutSearchingDataTable;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Models\{Transaction,ArcheiveTransaction,BackupTransaction,User,SurplusAmount};
use Illuminate\Http\Request;
use App\Service\StatusService;
use Carbon\Carbon;

class SearchingController extends Controller
{
    protected $merchantId;
    protected $password;
    protected $transactionPostUrl;
    protected $integritySalt;
    protected $easyUsername;
    protected $easyPassword;
    protected $storeId;
    protected $accountNumber;
    protected $easyStatusUrl;
    
    public function __construct(StatusService $statusService)
    {
        $this->middleware(['permission:Searching']);
        $this->merchantId = env('JAZZCASH_MERCHANT_ID');
        $this->password = env('JAZZCASH_PASSWORD');
        $this->jazzcashStatusUrl = env('JAZZCASH_STATUS_INQUIRY');
        $this->integritySalt = env('JAZZCASH_INTEGERITY_SALT');
        $this->easyUsername = env('EASYPAISA_PRODUCTION_USERNAME');
        $this->easyPassword = env('EASYPAISA_PRODUCTION_PASSWORD');
        $this->storeId = env('EASYPAISA_PRODUCTION_STOREID');
        $this->accountNumber = env('EASYPAISA_ACCOUNT_NUM');
        $this->easyStatusUrl = env('EASYPAISA_STATUS_INQUIRY');
        $this->statusService = $statusService;
    }
    public function list(SearchingDataTable $searchingDataTable)
    {
        return $searchingDataTable->render('admin.searching.list', get_defined_vars());
    }
    public function payoutList(PayoutSearchingDataTable $payoutSearchingDataTable)
    {
        return $payoutSearchingDataTable->render('admin.searching.payout_list', get_defined_vars());
    }
    public function srList(Request $request)
    {
        $user_id = request()->user_id;
        $time = request()->time;
        $trans_type = request()->trans_type;
        if($request->params){
            if ($time == "1") {
                $timeWindow = Carbon::now()->subMinutes(1);
            } elseif ($time == "2") {
                $timeWindow = Carbon::now()->subMinutes(2);
            } elseif ($time == "10") {
                $timeWindow = Carbon::now()->subMinutes(10);
            } elseif ($time == "30") {
                $timeWindow = Carbon::now()->subMinutes(30);
            } elseif ($time == "60") {
                $timeWindow = Carbon::now()->subHour();
            } else {
                $timeWindow = Carbon::now()->subHours(3);
            }
            $query = Transaction::query()
            ->when($user_id !== 'All', function ($q) use ($user_id) {
                $q->where('user_id', $user_id);
            })
            ->where('created_at', '>=', $timeWindow);
            
            // Handle txn_type conditions
            if ($trans_type === 'jazzcash') {
                $query->where('txn_type', 'jazzcash');
            } elseif ($trans_type === 'easypaisa') {
                $query->where('txn_type', 'easypaisa');
            } else {
                // 'all' or null — do not filter by payment_method
            }
            
            // Clone the query before modifying it
            $totalTransactions = (clone $query)->count();
            // if($time == "one_mints"){
            //     $transactionPerMint=$totalTransactions;
            // }else{
                $transactionPerMint = (clone $query)->count()/$time*1;
            // }
            
            $successfulTransactions = (clone $query)
                ->where('status', 'success')
                ->count();
            
            $totalAmount = (clone $query)
                ->where('status', 'success')
                ->sum('amount');
            
            $successRate = $totalTransactions > 0 
                ? round(($successfulTransactions / $totalTransactions) * 100, 2)
                : 0;
            $client=User::find($user_id);
        }
        
        $users= User::where('user_role','Client')->where('active',1)->get();
        return view('admin.searching.sr_list', get_defined_vars());
    }
    public function callback($id)
    {
        $integritySalt;
        $merchantId;
        $password;
        $item=Transaction::find($id);
        if (!$item) {
            $item = ArcheiveTransaction::find($id);
        }
        if (!$item) {
            $item = BackupTransaction::find($id);
        }
        // dd($item);
        $url=$item->url;
        if($item->txn_type === 'jazzcash'){
            $integritySalt = $this->integritySalt;
            $merchantId = $this->merchantId;
            $password = $this->password;

            $dataToHash = $integritySalt . '&' . $merchantId . '&' . $password . '&' . $item->txn_ref_no;
            $secureHash = hash_hmac('sha256', $dataToHash, $integritySalt);
            
            $payload = [
                'pp_MerchantID' => $merchantId,
                'pp_Password' => $password,
                'pp_TxnRefNo' => $item->txn_ref_no,
                'pp_SecureHash' => $secureHash,
            ];
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->jazzcashStatusUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
                // CURLOPT_SSL_VERIFYPEER => true,
                // CURLOPT_CAINFO => public_path('jazz_public_key/new-cert.crt'),
            ));
    
            $response = curl_exec($curl);
            curl_close($curl);
            $result = json_decode($response, true);
            if ($result['pp_ResponseCode'] == '000' && $result['pp_PaymentResponseCode'] == '121') {
                $item->update([
                    'status' => 'success',
                    'transactionId'=>$result['pp_AuthCode'],
                    'pp_code' => $result['pp_ResponseCode'],
                    'pp_message' => $result['pp_ResponseMessage']
                ]);
        
                $data = [
                    'orderId' => $item->orderId,
                    'amount' => $item->amount,
                    'status' => 'success',
                ];
                $response = Http::timeout(120)->post($url, $data);
            } elseif ($result['pp_PaymentResponseCode'] == '157'){
                $item->update([
                    'status' => 'pending',
                    'transactionId'=>$result['pp_AuthCode'],
                    'pp_code' => $result['pp_PaymentResponseCode'],
                    'pp_message' => $result['pp_PaymentResponseMessage']
                ]);
            }else {
                // Failure condition
                $item->update([
                    'status' => 'failed',
                    'transactionId'=>$result['pp_AuthCode'],
                    'pp_code' => $result['pp_PaymentResponseCode'],
                    'pp_message' => $result['pp_PaymentResponseMessage']
                ]);
        
                $data = [
                    'orderId' => $item->orderId,
                    'amount' => $item->amount,
                    'status' => 'failed',
                ];
                $response = Http::timeout(120)->post($url, $data);
            }
        }
        else{
            
            $result = $this->statusService->process($item);
            if ($result['responseCode'] == '0000') {
                // Check transactionStatus in response
                if ($result['transactionStatus'] == 'PAID') {
                    $item->update([
                        'status' => 'success',
                        'transactionId'=>$result['msisdn']  ?? null
                    ]);
                    $data = [
                        'orderId' => $item->orderId,
                        'amount' => $item->amount,
                        'status' => 'success',
                    ];
                    $user = User::find($item->user_id);

                    // if ($user && $user->per_payin_fee) {
                    //     $percentage = $user->per_payin_fee;
                    //     $amount = $item->amount * $percentage;
                    
                    //     $surplus = SurplusAmount::find(1);
                    //     $setting = Setting::where('user_id', $item->user_id)->first();
                    
                    //     if ($setting && $surplus) {
                    //         $setting->easypaisa += $amount;
                    //         $setting->payout_balance += $amount;
                    //         $setting->save();
                    
                    //         $surplus->easypaisa -= $amount;
                    //         $surplus->save();
                    //     }
                    // }
                    $response = Http::timeout(60)->post($url, $data);
                } elseif ($result['transactionStatus'] == 'FAILED') {
                    $item->update([
                        'status' => 'failed',
                        'transactionId'=>$result['msisdn']  ?? null,
                        'pp_code' => $result['errorCode'] ?? null,
                        'pp_message' => $result['errorReason'] ?? null
                    ]);
                    $data = [
                        'orderId' => $item->orderId,
                        'amount' => $item->amount,
                        'status' => 'failed',
                    ];
                    $response = Http::timeout(60)->post($url, $data);
                }
            } elseif ($result['responseCode'] == '0003') {
                // Transaction failed, update and notify
                $item->update([
                    'status' => 'failed',
                    'pp_code' => $result['responseCode'],
                    'pp_message' => $result['responseDesc']
                ]);
                $data = [
                    'orderId' => $item->orderId,
                    'amount' => $item->amount,
                    'status' => 'failed',
                ];
                $response = Http::timeout(60)->post($url, $data);
            }
        }
        return redirect()->back()->with('message','Callback send manually successfully!');
    }
    protected function getCredentials() {
        return base64_encode(env('EASYPAISA_PRODUCTION_USERNAME').':'.env('EASYPAISA_PRODUCTION_PASSWORD'));
    }
}
