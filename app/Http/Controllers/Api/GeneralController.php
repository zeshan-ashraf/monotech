<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\{Transaction,ArcheiveTransaction,BackupTransaction,Payout,ArcheivePayout,Summary,Setting,Settlement,User};
use Illuminate\Database\QueryException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
        $users = [3, 4, 6];
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
            } elseif ($userId == 2) {
                $todayPayinUserOk   = $todayPayin;
                $todayReverseUserOk = $todayReverse;
            } elseif ($userId == 5) {
                $todayPayinUserPkn   = $todayPayin;
                $todayReverseUserPkn = $todayReverse;
            }
        }
        
        return [
            'today_payin_piq'   => $todayPayinUserPiq ?? 0,
            'today_reverse_piq' => $todayReverseUserPiq ?? 0,
            'today_payin_ok'   => $todayPayinUserOk ?? 0,
            'today_reverse_ok' => $todayReverseUserOk ?? 0,
            'today_payin_pkn'   => $todayPayinUserPkn ?? 0,
            'today_reverse_pkn' => $todayReverseUserPkn ?? 0,
        ];
        
    }
}