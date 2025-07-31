<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Product,Client,Transaction};
use App\Service\PaymentService;
use DB;
use Illuminate\Support\Facades\Log;
use Zfhassaan\Easypaisa\Easypaisa;
use Illuminate\Database\QueryException;

class HomeController extends Controller
{
    public $service;

    public function __construct(PaymentService $service)
    {
        $this->service = $service;
    }
    public function index()
    {
        $list=Client::get();
        return view('frontend.index',get_defined_vars());
    }
    public function aboutus()
    {
        return view('frontend.aboutus');
    }
    public function faqs()
    {
        return view('frontend.faqs');
    }
    public function contactus()
    {
        return view('frontend.contactus');
    }
    public function services()
    {
        return view('frontend.services');
    }
    public function products()
    {
        $list=Product::take(12)->get();
        return view('frontend.products',get_defined_vars());
    }
    public function productDetail($id)
    {
        $item=Product::find($id);
        return view('frontend.product-detail',get_defined_vars());
    }
    public function policy()
    {
        return view('frontend.policy');
    }
    public function terms()
    {
        return view('frontend.terms');
    }
    public function refund()
    {
        return view('frontend.refund');
    }
    public function checkout(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'phone' => 'required',
            'email' => 'required',
            'client_email' => 'required',
            'amount' => 'required|numeric',
        ]);        
        try {
            list($post_data, $type, $url) = $this->service->process($request);
            // dd($post_data, $type, $url);
            if ($type == "easypaisa") {
                try {
                    $easypaisa = new Easypaisa;
                    $response = $easypaisa->sendRequest($post_data);
                    $responseCode = $response['responseCode'];
                    $responseDesc = $response['responseDesc'];
                    if ($responseCode != '0000') {
                        return redirect()->route('index')->with('error', 'Your transaction cannot be processed, please try again.');
                    }
                    if ($responseCode == '0000') {
                        // return view('front.cart.success', get_defined_vars());
                    }
                } catch (\Exception $e) {
                    return redirect()->route('home')->with('error', 'Your transaction cannot be processed, please try again.');
                }
            } else {
                $encode_data = json_encode($post_data, false);
                // dd($encode_data);
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
                    CURLOPT_POSTFIELDS => $encode_data,
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json'
                    ),
                ));
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

                $response = curl_exec($curl);
                
                curl_close($curl);

                if ($response === false) {
                    throw new \Exception('CURL Error: ' . curl_error($curl));
                }

                $result = json_decode($response, false);
                if (isset($result->pp_ResponseCode) && $result->pp_ResponseCode == '000') {
                    $values = [
                        'phone' => $request->phone,
                        'txn_ref_no' => $result->pp_TxnRefNo,
                        'amount' => $request->amount,
                        'orderId' => $result->pp_TxnRefNo,
                        'status' => 'success',
                        'txn_type' => 'jazzcash',
                        'pp_code' => $result->pp_ResponseCode,
                        'pp_message' => $result->pp_ResponseMessage,
                        'transactionId' => $request->phone,
                    ];
                    try {
                        $transaction = Transaction::create($values);
                    } catch (QueryException $e) {
                        // Handle duplicate orderId error
                        Log::warning('Duplicate orderId detected: ' . $values['orderId']);
                        return redirect()->route('home')->with('error', 'Transaction already processed. Please try again.');
                    }
                
                    return redirect()->route('home')->with('success', 'Trannsaction succesfully completed! Thanks for Choosing Jazzcash.');
                }
                return redirect()->route('home')->with('error', 'Your transaction cannot be processed, please try again.');
            }
        } catch (\Exception $e) {
            dd($e->getMessage());
            Log::error('Checkout Proceed Error: ' . $e->getMessage());
            return redirect()->route('home')->with('error', 'An error occurred during the transaction. Please try again.');
        }
    }
}
