<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\{Transaction,ArcheiveTransaction,BackupTransaction,Payout,ArcheivePayout,Summary,Setting,Settlement,User};

class GeneralController extends Controller
{
    public function index(Request $request)
    {
        $url = 'https://marketmaven.com.pk/api/get-transactions';

        // Sending the request
        $response = Http::get($url);
        $data = $response->json();
        foreach($data['data'] as $item){
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

}