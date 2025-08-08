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
use Illuminate\Database\QueryException;

class TestPaymentService
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
    public function process($request)
    {
        $function = $request->payment_method == 'easypaisa' ? 'easypaisa'  : 'jazzcash';
        return $this->$function($request);
    }

    public function jazzcash($request)
    {
		$pp_Amount 		= $request->amount*100;

		$DateTime 		= new \DateTime();
		$pp_TxnDateTime = $DateTime->format('YmdHis');
		$pp_TxnRefNo = 'T'.$pp_TxnDateTime . substr(uniqid(), -5);

		$post_data =  array(
            "pp_Amount" => (string)$pp_Amount,
            "pp_BillReference" => "billRef",
            "pp_Description" => "Description of transaction",
            "pp_Language" => $this->language,
            "pp_MerchantID" => $this->merchantId,
            "pp_Password" => $this->password,
            "pp_ReturnURL" 	=> $this->return_url,
            "pp_SecureHash" => "",
            "pp_TxnCurrency" => $this->currency_code,
            "pp_TxnDateTime" => $pp_TxnDateTime,
            "pp_TxnExpiryDateTime" => date('YmdHis', strtotime('+8 Days')),
            "pp_TxnRefNo" => $pp_TxnRefNo,
            "pp_TxnType" =>"MWALLET",
            "pp_Version" => $this->version,
            "ppmpf_1" => $request->phone,
        );
		$pp_SecureHash = $this->jazzcashSecureHash($post_data);

        $post_data['pp_SecureHash'] = $pp_SecureHash;
        $this->orderInitialProcess($request , $pp_TxnRefNo);
        return [
            $post_data, 
            'jazzcash', 
            $this->transactionPostUrl
        ];
    }
	private function jazzcashSecureHash($data_array)
	{
		ksort($data_array);
		
		$str = '';
		foreach($data_array as $key => $value){
			if(!empty($value)){
				$str = $str . '&' . $value;
			}
		}
		
		$str = $this->integritySalt.$str;
		
		$pp_SecureHash = hash_hmac('sha256', $str, $this->integritySalt);
		
		return $pp_SecureHash;
	}
    public function easypaisa($request)
	{
		$pp_Amount 		= (int)$request->amount;
		$DateTime 		= new \DateTime();
		$pp_TxnDateTime = $DateTime->format('YmdHis');
        $pp_TxnRefNo = 'T'.$pp_TxnDateTime . substr(uniqid(), -5);
        $date = Carbon::now();
        $expiryDate = $date->addHours(10)->format('Ymd His');
        

        $post_data = array(
            "storeId" => env('EASYPAISA_PRODUCTION_STOREID'),
            "transactionAmount" => $pp_Amount . '.0',
            'orderId' => $pp_TxnRefNo,
            'expiryDate' => $expiryDate,
            'transactionType' => 'MA',
            'mobileAccountNo' => $request->phone,
            'emailAddress' => $request->email,
        );
        
        $this->orderInitialProcess($request , $pp_TxnRefNo);
		return [
            $post_data, 
            'easypaisa', 
            env('EASYPAISA_PRODUCTION_URL')
        ];
		
	}
	public function orderInitialProcess($request , $pp_TxnRefNo)
    {
        $user=User::where('email',$request->client_email)->first();
        
		$values = array(
			'user_id'=> $user->id,
			'txn_ref_no'=> $pp_TxnRefNo,
			'amount'=> $request->amount,
			'orderId'=>$request->orderId,
			'status'=> 'pending',
			'txn_type'=> $request->payment_method,
			'phone' => $request->phone,
			'url' => $request->callback_url
		);
          
		try {
			$transaction = Transaction::create($values);
		} catch (QueryException $e) {
			// Handle the case where the transaction already exists
			Log::warning('Duplicate transaction attempt for txn_ref_no: ' . $pp_TxnRefNo, ['exception' => $e]);
			// You might want to update the existing transaction or return an error
			// For now, we'll just return true as the transaction is already there
			return true;
		}

        return true;
    }
    public function orderFinalProcess($request , $RefNum, $type)
    {
        $transaction = Transaction::where('txn_ref_no',$RefNum)->first();
		if($transaction)
		{
			if($type == "jazzcash"){
                if(isset($request->pp_ResponseCode) && $request->pp_ResponseCode == '000'){
    			    $transaction->update(['status'=>'success' ,'pp_code'=>$request->pp_ResponseCode, 'pp_message'=>$request->pp_ResponseMessage,'transactionId'=>$request->ppmpf_1]);
        	        $user = User::find($transaction->user_id);

                    if ($user && $user->per_payin_fee) {
                        $rate = $user->per_payin_fee;
                        $amount = $transaction->amount * $rate;
                    
                        $setting = Setting::where('user_id', $transaction->user_id)->first();
                        $surplus = SurplusAmount::find(1);
                    
                        if ($setting && $surplus) {
                            $setting->jazzcash += $amount;
                            $setting->payout_balance += $amount;
                            $setting->save();
                    
                            $surplus->jazzcash -= $amount;
                            $surplus->save();
                        }
                    }
                }else{
                    $transaction->update(['status'=>'failed','pp_code'=>$request->pp_ResponseCode, 'pp_message'=>$request->pp_ResponseMessage,'transactionId'=>$request->ppmpf_1]);
                }
			}else{
			    if ($request['responseCode'] == '0000') {
			        $transaction->update(['status'=>'success','pp_code'=>$request['responseCode'], 'pp_message'=>$request['responseDesc'],'transactionId'=>$request['transactionId']]);
			        
			        $user = User::find($transaction->user_id);

                    if ($user && $user->per_payin_fee) {
                        $rate = $user->per_payin_fee;
                        $amount = $transaction->amount * $rate;
                    
                        $setting = Setting::where('user_id', $transaction->user_id)->first();
                        $surplus = SurplusAmount::find(1);
                    
                        if ($setting && $surplus) {
                            $setting->easypaisa += $amount;
                            $setting->payout_balance += $amount;
                            $setting->save();
                    
                            $surplus->easypaisa -= $amount;
                            $surplus->save();
                        }
                    }
			    }
			    else{
			        $transaction->update(['status'=>'failed','pp_code'=>$request['responseCode'], 'pp_message'=>$request['responseDesc']]);
			    }
			}
			return $transaction;
		}
		return null;
    }
}