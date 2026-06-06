<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\Admin\TransactionDataTable;
use App\DataTables\Admin\ZigTransactionDataTable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\{User,Transaction,ArcheiveTransaction,BackupTransaction,Settlement};
use Carbon\Carbon;

class TransactionController extends Controller
{
    private $transactionDatatable;
    private $transactionZigDatatable;
    protected $merchantId;
    protected $password;
    protected $transactionPostUrl;
    protected $integritySalt;
    protected $easyUsername;
    protected $easyPassword;
    protected $storeId;
    protected $accountNumber;
    protected $easyStatusUrl;
    
    public function __construct()
    {
        $this->middleware(['permission:Transactions']);
        $this->transactionDatatable = new TransactionDataTable();
        $this->transactionZigDatatable = new ZigTransactionDataTable();
        $this->merchantId = env('JAZZCASH_MERCHANT_ID');
        $this->password = env('JAZZCASH_PASSWORD');
        $this->jazzcashStatusUrl = env('JAZZCASH_STATUS_INQUIRY');
        $this->integritySalt = env('JAZZCASH_INTEGERITY_SALT');
        $this->easyUsername = env('EASYPAISA_PRODUCTION_USERNAME');
        $this->easyPassword = env('EASYPAISA_PRODUCTION_PASSWORD');
        $this->storeId = env('EASYPAISA_PRODUCTION_STOREID');
        $this->accountNumber = env('EASYPAISA_ACCOUNT_NUM');
        $this->easyStatusUrl = env('EASYPAISA_STATUS_INQUIRY');
    }

    public function list()
    {
        $status = null;
        $assets = ['data-table'];
        $start = request()->start_date;
        $end = request()->end_date;
        $txn_type = request()->txn_type;
        $userRole = auth()->user()->user_role;
        $client = request()->client;
        $status = request()->status;
        $baseQuery = Transaction::when($userRole !== 'Super Admin', function ($query) {
            return $query->where('user_id', auth()->id());
        })
        ->when($client, function ($query) use ($client) {
            return $query->where('user_id', $client);
        })
        ->when(request()->filled('txn_type') && $txn_type !== 'all', function ($query) use ($txn_type) {
            return $query->where('txn_type', $txn_type); // adjust if using 'payment_method'
        })
        ->when(request()->filled('status') && $status !== 'all', function ($query) use ($status) {
            return $query->where('status', $status);
        })
        ->when($start && $end, function ($query) use ($start, $end) {
            return $query->whereBetween('created_at', ["$start 00:00:00", "$end 23:59:59"]);
        }, function ($query) {
            return $query->whereDate('created_at', Carbon::today());
        });
        
        $totalPayinTransactionsCount = (clone $baseQuery)->count();
        $totalPayinSuccessCount = (clone $baseQuery)->where('status', 'success')->count();
        $totalPayinSuccessAmount = (clone $baseQuery)->where('status', 'success')->sum('amount');
        $totalPayinFailedCount = (clone $baseQuery)->where('status', 'failed')->count();
        
        $payinSuccessRate = $totalPayinTransactionsCount > 0
            ? ($totalPayinSuccessCount / $totalPayinTransactionsCount) * 100
            : 0;
        return $this->transactionDatatable->with(['user_id'=>'both','status' => $status])->render('admin.transaction.list', get_defined_vars());
    }
    public function zigList()
    {
        $status = null;
        $assets = ['data-table'];
        $start = request()->start_date;
        $end = request()->end_date;
        $status = request()->status;
        $baseQuery = Transaction::where('user_id', 4)
        ->where('txn_type', 'jazzcash')
        ->when(request()->filled('status') && $status !== 'all', function ($query) use ($status) {
            return $query->where('status', $status);
        })
        ->when($start && $end, function ($query) use ($start, $end) {
            return $query->whereBetween('created_at', ["$start 00:00:00", "$end 23:59:59"]);
        }, function ($query) {
            return $query->whereDate('created_at', Carbon::today());
        });
        
        $totalPayinTransactionsCount = (clone $baseQuery)->count();
        $totalPayinSuccessCount = (clone $baseQuery)->where('status', 'success')->count();
        $totalPayinSuccessAmount = (clone $baseQuery)->where('status', 'success')->sum('amount');
        $totalPayinFailedCount = (clone $baseQuery)->where('status', 'failed')->count();
        
        $payinSuccessRate = $totalPayinTransactionsCount > 0
            ? ($totalPayinSuccessCount / $totalPayinTransactionsCount) * 100
            : 0;
        return $this->transactionZigDatatable->with(['user_id'=>'both','status' => $status])->render('admin.transaction.zig_list', get_defined_vars());
    }
    public function statusInquiry($id, $type)
    {
        $item = Transaction::where('txn_ref_no', $id)->first();
        if (!$item) {
            $item = ArcheiveTransaction::where('txn_ref_no', $id)->first();
        }
        if (!$item) {
            $item = BackupTransaction::where('txn_ref_no', $id)->first();
        }

        if ($type === 'jazzcash') {
            $response = $this->jazzcashStatusFunc($id, $this->integritySalt, $this->merchantId, $this->password);
        } else {
            $response = $this->easypaisaStatusFunc($id, $this->storeId, $this->accountNumber, $this->easyUsername, $this->easyPassword);
        }

        if ($this->isCarrierUnavailable($response)) {
            return $this->carrierDownView($id, $type);
        }

        $transactionDetails = $response;

        return view('admin.transaction.detail', get_defined_vars());
    }

    /**
     * Render the carrier-unavailable page when the provider API cannot be reached.
     */
    private function carrierDownView(string $referenceId, string $type)
    {
        $carrier = strtolower($type) === 'jazzcash' ? 'jazzcash' : 'easypaisa';

        $messages = [
            'easypaisa' => 'Easypaisa status inquiry is temporarily unavailable. This usually means the carrier API is down or not responding. Please try again in a few minutes.',
            'jazzcash' => 'JazzCash status inquiry is temporarily unavailable. This usually means the carrier API is down or not responding. Please try again in a few minutes.',
        ];

        return view('admin.transaction.carrier-down', [
            'referenceId' => $referenceId,
            'carrierMessage' => $messages[$carrier],
            'retryUrl' => route('admin.jazzcash.status-inquiry', ['id' => $referenceId, 'type' => $type]),
            'backUrl' => url()->previous() !== url()->current()
                ? url()->previous()
                : route('admin.transaction.list'),
        ]);
    }

    /**
     * Detect transport-level or empty failures from carrier status APIs.
     */
    private function isCarrierUnavailable($response): bool
    {
        if (! is_array($response)) {
            return true;
        }

        return (bool) ($response['_carrier_unavailable'] ?? false);
    }
	public function jazzcashStatusFunc($id,$integritySalt,$merchantId,$password)
	{
        $dataToHash = $integritySalt . '&' . $merchantId . '&' . $password . '&' . $id;
        $secureHash = hash_hmac('sha256', $dataToHash, $integritySalt);
        
        $payload = [
            'pp_MerchantID' => $merchantId,
            'pp_Password' => $password,
            'pp_TxnRefNo' => $id,
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

        if ($response === false) {
            $error = curl_error($curl);
            $errno = curl_errno($curl);
            curl_close($curl);

            Log::channel('payout')->warning('JazzCash status inquiry transport failure', [
                'txn_ref_no' => $id,
                'curl_error' => $error,
                'curl_errno' => $errno,
            ]);

            return ['_carrier_unavailable' => true];
        }

        curl_close($curl);
        $result = json_decode($response, true);

        if (! is_array($result)) {
            Log::channel('payout')->warning('JazzCash status inquiry invalid response', [
                'txn_ref_no' => $id,
                'raw_response' => is_string($response) ? substr($response, 0, 500) : null,
            ]);

            return ['_carrier_unavailable' => true];
        }

        return $result;
	}
    public function easypaisaStatusFunc($id,$storeId,$accountNumber,$easyUsername,$easyPassword)
    {
        $data = [
            "storeId" => $storeId,
            'orderId' => $id,
            'accountNum' => $accountNumber,
		];
        
        $credentials=base64_encode($easyUsername.':'.$easyPassword);

        try {
            $response = Http::timeout(60)
                ->retry(3, 1000, function ($exception) {
                    return $exception instanceof ConnectionException;
                })
                ->withHeaders([
                    'credentials' => $credentials,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->easyStatusUrl, $data);

            if ($response->failed()) {
                Log::channel('payout')->warning('Easypaisa status inquiry HTTP failure', [
                    'txn_ref_no' => $id,
                    'http_status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ['_carrier_unavailable' => true];
            }

            $result = $response->json();

            if (! is_array($result)) {
                Log::channel('payout')->warning('Easypaisa status inquiry invalid JSON', [
                    'txn_ref_no' => $id,
                    'body' => $response->body(),
                ]);

                return ['_carrier_unavailable' => true];
            }

            return $result;
        } catch (ConnectionException $exception) {
            Log::channel('payout')->warning('Easypaisa status inquiry connection failure', [
                'txn_ref_no' => $id,
                'error' => $exception->getMessage(),
            ]);

            return ['_carrier_unavailable' => true];
        } catch (\Throwable $exception) {
            Log::channel('payout')->error('Easypaisa status inquiry unexpected failure', [
                'txn_ref_no' => $id,
                'error' => $exception->getMessage(),
            ]);

            return ['_carrier_unavailable' => true];
        }
    }
    public function easyReceipt($id)
    {
        $item=Transaction::find($id);
        return view('admin.receipt.easypaisa',get_defined_vars());
    }
    public function jazzReceipt($id)
    {
        $item=Transaction::find($id);
        return view('admin.receipt.jazzcash',get_defined_vars());
    }
    public function changeStatus(Request $request)
    {
        // Fetch the transaction first
        $transaction = Transaction::find($request->id);
    
        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }
    
        // Update the status
        $transaction->status = $request->status;
        $transaction->save();
    
        // Prepare the data for the HTTP request
        $data = [
            'orderId' => $transaction->orderId,
            'amount' => $transaction->amount,
            'status' => $transaction->status,
        ];
    
        // Make an HTTP request
        $response = Http::timeout(60)->post($transaction->url, $data);
    
        return response()->json(['message' => 'Status changed successfully!']);
    }
    public function changeStatusReverse(Request $request)
    {

        // Fetch the transaction first
        $transaction = Transaction::find($request->id);
        if(!$transaction){
            $transaction=ArcheiveTransaction::find($request->id);
        }
        if(!$transaction){
            $transaction=BackupTransaction::find($request->id);
        }
        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }
        // $settlement=Settlement::where('user_id', $transaction->user_id)
        //     ->where('date', Carbon::yesterday()->format('Y-m-d'))
        //     ->first();
        // Update the status
        $transaction->status = $request->status;
        $transaction->save();
        // $settlement->closing_bal -=$transaction->amount;
        // $settlement->save();
    
        return response()->json(['message' => 'Status changed successfully!']);
    }
}
