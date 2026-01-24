<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Product,Client,Transaction,User};
use App\Service\PaymentService;
use Illuminate\Support\Facades\Log;
use Zfhassaan\Easypaisa\Easypaisa;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use DB;

class TestingController extends Controller
{
    public $service;

    public function __construct(PaymentService $service)
    {
        $this->service = $service;
    }
    public function payinTest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'regex:/^03[0-9]{9}$/'],
            'email' => 'required|email',
            'client_email' => 'required|email',
            'payment_method' => 'required|in:jazzcash,easypaisa',
            'amount' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // $user = User::where('email', $request->client_email)->first();

        // if (
        //     ($request->payment_method == "jazzcash" && $user->jc_api == 0) ||
        //     ($request->payment_method == "easypaisa" && $user->ep_api == 0)
        // ) {
        //     return redirect()->back()->with('error', 'API suspended by administrator.');
        // }

        try {
            list($post_data, $type, $url) = $this->service->process($request);

            /** ---------------- EASYPaisa ---------------- */
            if ($type == "easypaisa") {

                $easypaisa = new Easypaisa;
                $response = $easypaisa->sendRequest($post_data);

                if ($response instanceof \Illuminate\Http\JsonResponse) {
                    $response = $response->getData(true);
                }

                if (
                    isset($response['responseCode']) &&
                    $response['responseCode'] == '0000'
                ) {
                    $transaction = $this->service->orderFinalProcess(
                        $response,
                        $response['orderId'],
                        'easypaisa'
                    );

                    return redirect()->back()->with([
                        'success' => 'Payment checkout initiated successfully.',
                        'transaction_id' => $transaction->txn_ref_no
                    ]);
                }

                $this->service->orderFinalProcess($response, $response['orderId'], 'easypaisa');
                return redirect()->back()->with('error', 'Payment checkout failed.');

            }

            /** ---------------- JazzCash ---------------- */
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
            ]);

            $response = curl_exec($curl);
            curl_close($curl);

            $result = json_decode($response);

            if (isset($result->pp_ResponseCode) && $result->pp_ResponseCode == '000') {
                $transaction = $this->service->orderFinalProcess(
                    $result,
                    $result->pp_TxnRefNo,
                    'jazzcash'
                );

                return redirect()->back()->with([
                    'success' => 'Payment checkout initiated successfully.',
                    'transaction_id' => $transaction->txn_ref_no
                ]);
            }

            $this->service->orderFinalProcess($result, $result->pp_TxnRefNo, 'jazzcash');
            return redirect()->back()->with('error', 'Payment checkout failed.');

        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong.');
        }
    }
}