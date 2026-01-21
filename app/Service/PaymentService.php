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

class PaymentService
{
    protected $merchantId;
    protected $version;
    protected $language;
    protected $password;
    protected $currency_code;
    protected $return_url;
    protected $transactionPostUrl;
    protected $integritySalt;
    protected $logger;
    
    public function __construct()
    {
        $this->merchantId = config('jazzcash.constants.MERCHANT_ID');
        $this->version = config('jazzcash.constants.VERSION');
        $this->language = config('jazzcash.constants.LANGUAGE');
        $this->password = config('jazzcash.constants.PASSWORD');
        $this->currency_code = config('jazzcash.constants.CURRENCY_CODE');
        //$this->return_url = config('jazzcash.constants.RETURN_URL');
        $this->return_url = url('/api/jazzcash/callback');
        $this->transactionPostUrl = config('jazzcash.constants.TRANSACTION_POST_URL');
        $this->integritySalt = config('jazzcash.constants.INTEGERITY_SALT');
        $this->logger = Log::channel('payin');
    }

    public function process($request)
    {
        $startTime = microtime(true);
        $this->logger->info('PaymentService: Starting payment process', [
            'payment_method' => $request->payment_method,
            'amount' => $request->amount
        ]);

        $function = $request->payment_method == 'easypaisa' ? 'easypaisa'  : 'jazzcash';
        $result = $this->$function($request);

        $this->logger->info('PaymentService: Payment process completed', [
            'execution_time' => microtime(true) - $startTime
        ]);

        return $result;
    }

    public function jazzcash($request)
    {
        $startTime = microtime(true);
        $this->logger->info('PaymentService: Processing JazzCash payment', [
            'amount' => $request->amount
        ]);

		$pp_Amount = $request->amount*100;
		$DateTime = new \DateTime();
		$pp_TxnDateTime = $DateTime->format('YmdHis');
		$pp_TxnRefNo = 'T'.$pp_TxnDateTime . substr(uniqid(), -5);

        $user = User::where('email', $request->client_email)->first();
        $subStore="";
        if($user->email == "okpaysev@gmail.com"){
            $subStore="Young With Yoga";
        }else{
            $subStore="Code Base Academy";
        }

		$post_data = array(
            "pp_Amount" => (string)$pp_Amount,
            "pp_BillReference" => "billRef",
            "pp_Description" => "Description of transaction",
            "pp_Language" => $this->language,
            "pp_MerchantID" => $this->merchantId,
            "pp_Password" => $this->password,
            "pp_ReturnURL" => $this->return_url,
            "pp_SubMerchantID" => "",
            "pp_SubMerchantName" => $subStore,
            "pp_SecureHash" => "",
            "pp_TxnCurrency" => $this->currency_code,
            "pp_TxnDateTime" => $pp_TxnDateTime,
            "pp_TxnExpiryDateTime" => date('YmdHis', strtotime('+1 Days')),
            "pp_TxnRefNo" => $pp_TxnRefNo,
            "pp_TxnType" =>"MWALLET",
            "pp_Version" => $this->version,
            "ppmpf_1" => $request->phone,
        );
        $this->logger->debug('+++++++++++++++++++++++PaymentService: JazzCash secure hash parameters', [
            "pp_Amount" => (string)$pp_Amount,
            "pp_BillReference" => "billRef",
            "pp_Description" => "Description of transaction",
            "pp_Language" => $this->language,
            "pp_MerchantID" => $this->merchantId,
            "pp_Password" => $this->password,
            "pp_ReturnURL" => $this->return_url,
            "pp_SecureHash" => "",
            "pp_TxnCurrency" => $this->currency_code,
            "pp_TxnDateTime" => $pp_TxnDateTime,
            "pp_TxnExpiryDateTime" => date('YmdHis', strtotime('+8 Days')),
            "pp_TxnRefNo" => $pp_TxnRefNo,
            "pp_TxnType" =>"MWALLET",
            "pp_Version" => $this->version,
            "ppmpf_1" => $request->phone,
        ]);
		$pp_SecureHash = $this->jazzcashSecureHash($post_data);
        $post_data['pp_SecureHash'] = $pp_SecureHash;
        
        $this->orderInitialProcess($request, $pp_TxnRefNo);

        $this->logger->info('PaymentService: JazzCash payment processed', [
            'transaction_ref' => $pp_TxnRefNo,
            'execution_time' => microtime(true) - $startTime
        ]);

        return [
            $post_data, 
            'jazzcash', 
            $this->transactionPostUrl
        ];
    }

    private function jazzcashSecureHash($data_array)
    {
        $startTime = microtime(true);
        ksort($data_array);
		
        $str = '';
        foreach($data_array as $key => $value){
            if(!empty($value)){
                $str = $str . '&' . $value;
            }
        }
		
        $str = $this->integritySalt.$str;
        $pp_SecureHash = hash_hmac('sha256', $str, $this->integritySalt);

        $this->logger->debug('PaymentService: JazzCash secure hash generated', [
            'execution_time' => microtime(true) - $startTime
        ]);
		
        return $pp_SecureHash;
    }

    public function easypaisa($request)
    {
        $startTime = microtime(true);
        $this->logger->info('PaymentService: Processing Easypaisa payment', [
            'amount' => $request->amount
        ]);

		$pp_Amount = (int)$request->amount;
		$DateTime = new \DateTime();
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
        
        $this->orderInitialProcess($request, $pp_TxnRefNo);

        $this->logger->info('PaymentService: Easypaisa payment processed', [
            'transaction_ref' => $pp_TxnRefNo,
            'execution_time' => microtime(true) - $startTime
        ]);

		return [
            $post_data, 
            'easypaisa', 
            env('EASYPAISA_PRODUCTION_URL')
        ];
	}

	public function orderInitialProcess($request, $pp_TxnRefNo)
    {
        $startTime = microtime(true);
        $this->logger->info('PaymentService: Starting order initial process', [
            'transaction_ref' => $pp_TxnRefNo
        ]);

        $user = User::where('email', $request->client_email)->first();
        
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
			
			$this->logger->info('PaymentService: Order initial process completed', [
				'transaction_id' => $transaction->id,
				'execution_time' => microtime(true) - $startTime
			]);

			return true;
		} catch (QueryException $e) {
			// Handle duplicate key error (MySQL error code 1062)
			if ($e->getCode() == 1062) {
				$this->logger->warning('PaymentService: Duplicate orderId detected', [
					'orderId' => $request->orderId,
					'error_code' => $e->getCode(),
					'error_message' => $e->getMessage(),
					'execution_time' => microtime(true) - $startTime
				]);
				
				throw new \Exception('Order ID already exists. Please use a different order ID.', 409);
			}
			
			// Re-throw other database exceptions
			$this->logger->error('PaymentService: Database error during duplicate orderId transaction creation', [
				'error_code' => $e->getCode(),
				'error_message' => $e->getMessage(),
				'execution_time' => microtime(true) - $startTime
			]);
			
			throw $e;
		}
    }

    public function orderFinalProcess($request, $RefNum, $type)
    {
        $startTime = microtime(true);
        $this->logger->info('PaymentService: Starting order final process', [
            'transaction_ref' => $RefNum,
            'type' => $type
        ]);

        $transaction = Transaction::where('txn_ref_no', $RefNum)->first();
		if($transaction)
		{
			if($type == "jazzcash"){
                if(isset($request->pp_ResponseCode) && $request->pp_ResponseCode == '000'){
    			    $transaction->update(['status'=>'success', 'pp_code'=>$request->pp_ResponseCode, 'pp_message'=>$request->pp_ResponseMessage,'transactionId'=>$request->ppmpf_1]);
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
                } else {
                    $transaction->update(['status'=>'failed','pp_code'=>$request->pp_ResponseCode, 'pp_message'=>$request->pp_ResponseMessage,'transactionId'=>$request->ppmpf_1]);
                }
			} else {
			    if ($request['responseCode'] == '0000') {
			        $transaction->update(['status'=>'success','pp_code'=>$request['responseCode'], 'pp_message'=>$request['responseDesc'],'transactionId'=>$request['transactionId']]);
			        
			        $user = User::find($transaction->user_id);

                    if ($user && $user->per_payin_fee) {
                        $rate = $user->per_payin_fee;
                        $amount = $transaction->amount * $rate;
                    
                        $setting = Setting::where('user_id', $transaction->user_id)->first();
                        $surplus = SurplusAmount::find(1);
                        if($user->id == 2 || $user->id == 18){
                            if ($setting && $surplus) {
                                $setting->easypaisa += $amount;
                                $setting->payout_balance += $amount;
                                $setting->save();
                        
                                $surplus->easypaisa -= $amount;
                                $surplus->save();
                            }
                        }
                    }
			    } else {
			        $transaction->update(['status'=>'failed','pp_code'=>$request['responseCode'], 'pp_message'=>$request['responseDesc']]);
			    }
			}

            $this->logger->info('PaymentService: Order final process completed', [
                'transaction_id' => $transaction->id,
                'status' => $transaction->status,
                'execution_time' => microtime(true) - $startTime
            ]);

			return $transaction;
		}

        $this->logger->warning('PaymentService: Order final process failed - transaction not found', [
            'transaction_ref' => $RefNum,
            'execution_time' => microtime(true) - $startTime
        ]);

		return null;
    }
}