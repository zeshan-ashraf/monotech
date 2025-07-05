<?php
namespace App\Service;

use Carbon\Carbon;
use App\Models\Transaction;
// use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

class StatusService
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
        $function = $request->txn_type == 'easypaisa' ? 'easypaisa'  : 'jazzcash';
        return $this->$function($request);
    }

    public function jazzcash($request)
    {
		$data =  [
			"pp_TxnRefNo" 			=> $request->txn_ref_no,
			"pp_MerchantID" 		=> $this->merchantId,
			"pp_Password" 			=> $this->password,
		];
		$pp_SecureHash = $this->jazzcashSecureHash($data);

        $data['pp_SecureHash'] = $pp_SecureHash;

		$response = Http::timeout(120)
        ->retry(3, 1000) // Retry 3 times, 2 seconds delay
        ->withHeaders([
            'Content-Type' => 'application/json',
        ])
        // ->withOptions([
        //     'verify' => public_path('jazz_public_key/new-cert.crt'), // SSL cert
        // ])
        ->post(env('JAZZCASH_STATUS_INQUIRY'), $data);
        $result = $response->json();
        return $result;
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
	    
        $data = [
            "storeId" => env('EASYPAISA_PRODUCTION_STOREID'),
            'orderId' => $request->txn_ref_no,
            'accountNum' => env('EASYPAISA_ACCOUNT_NUM'),
		];
        $credentials=$this->getCredentials();
        
		$response = Http::timeout(120)->retry(3, 1000)->withHeaders([
            'credentials'=>$credentials,
            'Content-Type'=> 'application/json'
        ])->post(env('EASYPAISA_STATUS_INQUIRY'),$data);

        $result = $response->json();
        return $result;
		
	}

    protected function getCredentials() {
        return base64_encode(env('EASYPAISA_PRODUCTION_USERNAME').':'.env('EASYPAISA_PRODUCTION_PASSWORD'));
    }
}