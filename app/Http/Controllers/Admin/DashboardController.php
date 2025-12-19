<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Payout;
use App\Models\Transaction;
use App\Models\ArcheiveTransaction;
use App\Models\{User,Settlement, Setting, BackupTransaction, SurplusAmount};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{

    public function index()
    {
        $today=today()->format('d-m-Y');
        $clients = User::where('user_role', 'Client')->where('active',1)->get();
        
        $month = Carbon::now()->month;
        $totalMonthlyAmount = DB::table('settlements')
            ->whereMonth('created_at', $month)
            ->sum('ep_payin');
        $data = [];
        
        foreach ($clients as $client) {
            $userId = $client->id;
            
            $prevBal=Settlement::where('user_id', $userId)->whereDate('date', today()->subDay())->value('closing_bal') ?? 0;
            $settlement=Settlement::where('user_id', $userId)->whereDate('date', today())->first();
            
            $epPayinAmount = $settlement->ep_payin;
            $jcPayinAmount = $settlement->jc_payin;
            $epPayoutAmount = $settlement->ep_payout;
            $jcPayoutAmount = $settlement->jc_payout;
            $reverseAmount = $settlement->reverse_amount ?? 0;
            // if($userId == 2 || $userId == 18){
                $payinSuccess= $epPayinAmount + $jcPayinAmount;
            // }else{
                // $payinSuccess= $jcPayinAmount;
            // }
            $payoutSuccess= $epPayoutAmount + $jcPayoutAmount;
            $prevUsdt= $settlement->usdt;
            $payinFee=$client->payin_fee;
            $payoutFee=$client->payout_fee;
            //getUnsettlement
            // if ($userId == 2) {
            //     $payinSuccess = $epPayinAmount;
            // } 
            $unsettletdAmount=$prevBal + $payinSuccess - ($payinSuccess*$payinFee + $payoutSuccess + $payoutSuccess*$payoutFee + $prevUsdt);
            $assignedAmount=Setting::where('user_id',$userId)->select('jazzcash','easypaisa','payout_balance')->first();
            $balance= $unsettletdAmount - $assignedAmount->payout_balance ;
    
            $data[] = [ 
                'user' => $client,
                'prev_balance' => $prevBal,
                'jc_payin' => $jcPayinAmount,
                'ep_payin' => $epPayinAmount,
                'reverse_amount' => $reverseAmount,
                'total_payin' => $payinSuccess,
                'jc_payout' => $jcPayoutAmount,
                'ep_payout' => $epPayoutAmount,
                'total_payout' => $payoutSuccess,
                'prev_usdt' => $prevUsdt,
                'unsettled_amount' => $unsettletdAmount,
                'unsettled_amount_balance' => $balance,
                'assigned_amount' => Setting::where('user_id', $userId)->first(),
                'setting' => Setting::where('user_id', $userId)->first(),
            ];
        }
        $totals = [
            'prev_balance' => 0,
            'jc_payin' => 0,
            'ep_payin' => 0,
            'reverse_amount' => 0,
            'total_payin' => 0,
            'jc_payout' => 0,
            'ep_payout' => 0,
            'total_payout' => 0,
            'prev_usdt' => 0,
            'unsettled_amount' => 0,
            'unsettled_amount_balance'=>0,
            'assigned_jc' => 0,
            'assigned_ep' => 0,
            'assigned_payout' => 0,
        ];
        
        foreach ($data as $item) {
            $totals['prev_balance'] += $item['prev_balance'];
            $totals['jc_payin'] += $item['jc_payin'];
            $totals['ep_payin'] += $item['ep_payin'];
            $totals['reverse_amount'] += $item['reverse_amount'];
            $totals['total_payin'] += $item['total_payin'];
            $totals['jc_payout'] += $item['jc_payout'];
            $totals['ep_payout'] += $item['ep_payout'];
            $totals['total_payout'] += $item['total_payout'];
            $totals['prev_usdt'] += $item['prev_usdt'];
            $totals['unsettled_amount'] += $item['unsettled_amount'];
            $totals['unsettled_amount_balance'] += $item['unsettled_amount_balance'];
            $totals['assigned_jc'] += $item['assigned_amount']->jazzcash ?? 0;
            $totals['assigned_ep'] += $item['assigned_amount']->easypaisa ?? 0;
            $totals['assigned_payout'] += $item['assigned_amount']->payout_balance ?? 0;
        }
        
        $jcOkPendingOrder = Transaction::where([
            ['status', 'pending'],
            ['user_id', '2'],
            ['txn_type', 'jazzcash']
        ])->count();
        
        $epOkPendingOrder = Transaction::where([
            ['status', 'pending'],
            ['user_id', '2'],
            ['txn_type', 'easypaisa']
        ])->count();
        
        $jcPiqPendingOrder = Transaction::where([
            ['status', 'pending'],
            ['user_id', '4'],
            ['txn_type', 'jazzcash']
        ])->count();
        
        $epPiqPendingOrder = Transaction::where([
            ['status', 'pending'],
            ['user_id', '4'],
            ['txn_type', 'easypaisa']
        ])->count();
        
        $jcPkNPendingOrder = Transaction::where([
            ['status', 'pending'],
            ['user_id', '5'],
            ['txn_type', 'jazzcash']
        ])->count();
        
        $epPkNPendingOrder = Transaction::where([
            ['status', 'pending'],
            ['user_id', '5'],
            ['txn_type', 'easypaisa']
        ])->count();
        
        $jcMoneyPendingOrder = Transaction::where([
            ['status', 'pending'],
            ['user_id', '14'],
            ['txn_type', 'jazzcash']
        ])->count();
        
        $epMoneyPendingOrder = Transaction::where([
            ['status', 'pending'],
            ['user_id', '14'],
            ['txn_type', 'easypaisa']
        ])->count();
        
        $list = Setting::all();
        $surplusAmount=SurplusAmount::where('id','1')->first();
        return view('admin.index', get_defined_vars());

    }
    
    public function zigIndex()
    {
        // $client = User::where('id', 4)->where('active',1)->first();
        $item=Settlement::where('user_id', 4)->whereDate('date', today())->first();
        // $prevBal=Settlement::where('user_id', 4)->whereDate('date', today()->subDay())->value('closing_bal') ?? 0;
        // $jcPayinAmount = $item->jc_payin;
        // $jcPayoutAmount = $item->jc_payout;
        // $payinSuccess= $jcPayinAmount;
        // $payoutSuccess= $jcPayoutAmount;
        // $prevUsdt= $item->usdt;
        // $payinFee=$client->payin_fee;
        // $payoutFee=$client->payout_fee;
        // $unsettletdAmount=$prevBal + $payinSuccess - ($payinSuccess*$payinFee + $payoutSuccess + $payoutSuccess*$payoutFee + $prevUsdt);
        // $assignedAmount=Setting::where('user_id',4)->select('jazzcash','easypaisa','payout_balance')->first();
        // $balance= $unsettletdAmount - $assignedAmount->payout_balance ;
        return view('admin.zig_index', get_defined_vars());
    }
    public function profile()
    {
        $user = auth()->user();
        return view('admin.security.profile',get_defined_vars());
    }
    public function accountSetting()
    {
        $user = auth()->user();
        return view('admin.security.password',get_defined_vars());
    }
    public function securityUpdate(Request $request)
    {
        $request->validate([
            'password' => ['required', 'confirmed','min:8','max:20']
        ]);
        User::where('id' , auth()->user()->id)->update(['password' => Hash::make($request->password)]);

        return redirect()->route('admin.account.settings')->with('message','Updated Successfully!');
    }

    public function testing()
    {
        $oneHourAgo = Carbon::now()->subHour(6);
        Transaction::where('user_id', 2)
            ->where('created_at', '>=', $oneHourAgo)
            ->orderBy('id')
            ->chunk(100, function ($transactions) {
                // dd($transactions);
                foreach ($transactions as $transaction) {
                    $url = $transaction->url;

                    $data = [
                        'orderId' => $transaction->orderId,
                        'tid' => $transaction->transactionId,
                        'amount' => $transaction->amount,
                        'status' => $transaction->status,
                    ];

                    try {
                        $requestStartTime = microtime(true);
                        
                        // Log request details
                        Log::channel('manual_callback')->info("Sending callback request", [
                            'transaction_id' => $transaction->id,
                            'url' => $url,
                            'request_params' => $data,
                        ]);

                        $response = Http::timeout(60)->post($url, $data);
                        
                        $requestDuration = microtime(true) - $requestStartTime;
                        
                        // Log response details
                        Log::channel('manual_callback')->info("Callback response received", [
                            'transaction_id' => $transaction->id,
                            'response_status' => $response->status(),
                            'response_body' => $response->body(),
                            'duration_seconds' => round($requestDuration, 4),
                        ]);

                    } catch (\Exception $e) {
                        $requestDuration = microtime(true) - $requestStartTime;
                        
                        Log::channel('manual_callback')->error("Failed to send callback", [
                            'transaction_id' => $transaction->id,
                            'url' => $url,
                            'request_params' => $data,
                            'error_message' => $e->getMessage(),
                            'duration_seconds' => round($requestDuration, 4),
                        ]);
                    }
                }
            });

        $totalDuration = microtime(true) - $startTime;
        Log::channel('manual_callback')->info("Manual callback process completed", [
            'total_duration_seconds' => round($totalDuration, 4),
        ]);

        return response()->json([
            'message' => 'Manual callback process completed',
            'status' => 'success',
            'duration_seconds' => round($totalDuration, 4)
        ]);
    }
    public function prevClientSettlementEntry($id)
    {
        $users=User::where('user_role','Client')->where('id',$id)->get();
        $transactionReverseHalf = 0;
        $today = Carbon::today();
        foreach ($users as $user) {
            $sumamry= Settlement::where('user_id',$user->id)->whereDate('date', Carbon::today()->subDay(1)->format('y-m-d'))->first();
            if($sumamry){
                // Get yesterday's closing balance
                $closingBal = DB::table('settlements')
                    ->where('user_id', $user->id)
                    ->whereDate('date', Carbon::today()->subDay(2)->format('y-m-d'))
                    ->value('closing_bal');
                $prev_pnl = DB::table('settlements')
                    ->where('user_id', $user->id)
                    ->whereDate('date', Carbon::today()->subDay(2)->format('y-m-d'))
                    ->value('total_pnl_amount');

                $prev_usdt_pnl = DB::table('settlements')
                    ->where('user_id', $user->id)
                    ->whereDate('date', Carbon::today()->subDay(1)->format('y-m-d'))
                    ->value('usdt_pnl_amount');
                
                $todayUsdt = DB::table('settlements')
                    ->where('user_id', $user->id)
                    ->whereDate('date', Carbon::today()->subDay(1)->format('y-m-d'))
                    ->value('usdt');
                
                // Sum of successful transaction amounts
                $transactionSumJC = DB::table('archeive_transactions')
                    ->where('user_id', $user->id)
                    ->whereIn('status', ['success', 'reverse'])
                    ->where('txn_type', 'jazzcash')
                    ->whereDate('created_at', Carbon::today()->subDay(1))
                    ->sum('amount');

                $transactionReverse = DB::table('archeive_transactions')
                    ->where('user_id', $user->id)
                    ->where('status', 'reverse')
                    ->whereDate('updated_at', Carbon::today()->subDay(1))
                    ->sum('amount');

                $archiveReverse = DB::table('archeive_transactions')
                    ->where('user_id', $user->id)
                    ->where('status', 'reverse')
                    ->whereDate('updated_at', Carbon::today()->subDay(1))
                    ->sum('amount');

                $backupReverse = DB::table('backup_transactions')
                    ->where('user_id', $user->id)
                    ->where('status', 'reverse')
                    ->whereDate('updated_at', Carbon::today()->subDay(1))
                    ->sum('amount');

                $totalReverseAmount = $transactionReverse + $archiveReverse + $backupReverse;
                
                if($user->id == 2){
                    $transactionReverseHalf = $totalReverseAmount * 0.5;
                }
                else{
                    $transactionReverseHalf = $totalReverseAmount;
                }
                $transactionSumEP = DB::table('archeive_transactions')
                    ->where('user_id', $user->id)
                    ->whereIn('status', ['success', 'reverse'])
                    ->where('txn_type', 'easypaisa')
                    ->whereDate('created_at', Carbon::today()->subDay(1))
                    ->sum('amount');

                // Sum of successful payout amounts
                $payoutSumJC = DB::table('archeive_payouts')
                    ->where('user_id', $user->id)
                    ->where('status', 'success')
                    ->where('transaction_type', 'jazzcash')
                    ->whereDate('created_at', Carbon::today()->subDay(1))
                    ->sum('amount');
                
                $payoutSumEP = DB::table('archeive_payouts')
                    ->where('user_id', $user->id)
                    ->where('status', 'success')
                    ->where('transaction_type', 'easypaisa')
                    ->whereDate('created_at', Carbon::today()->subDay(1))
                    ->sum('amount');

                // $payoutUrl = 'https://novapay.pk/api/get-payout-data';
                // $novaResponse = Http::get($payoutUrl);
                // $novaData = $novaResponse->json();
                // if($user->id == "4"){
                //     $marketUrl = 'https://marketmaven.com.pk/api/get-payin-data';
                //     $marketResponse = Http::get($marketUrl);
                //     $marketData = $marketResponse->json();
                //     $marketPayinAmount = $marketData['today_payin'];
                //     $transactionSumEP = $transactionSumEP + $marketPayinAmount;
                //     $transactionReverseHalf = $transactionReverseHalf + $marketData['today_reverse'];
                    
                //     $payoutSumJC = $payoutSumJC + $novaData['today_piq_jc_payout'];
                //     $payoutSumEP = $payoutSumEP + $novaData['today_piq_ep_payout'];
                // }
                // $url = 'https://khushiconnect.com/api/get-payin-data';
                // $khushiResponse = Http::get($url);
                // $KhushiData = $khushiResponse->json();
                // if($user->id == "2"){
                //     $khushiPayinAmount = $KhushiData['today_payin_ok'];

                //     $setting = Setting::where('user_id', 2)->first();
                //     $user=User::find(2);
                //     $surplus=SurplusAmount::find(1);
                //     $previousAmount=$user->temp_amount;
                //     $user->temp_amount = $khushiPayinAmount;
                //     $user->save();
                //     $surplus->easypaisa = $surplus->easypaisa+$previousAmount-$khushiPayinAmount;
                //     $surplus->save();

                //     $setting->easypaisa= $setting->easypaisa-$previousAmount+$khushiPayinAmount;
                //     $setting->payout_balance = $setting->payout_balance-$previousAmount+$khushiPayinAmount;
                //     $setting->save();

                //     $transactionSumEP = $transactionSumEP + $khushiPayinAmount;
                //     $transactionReverseHalf = $transactionReverseHalf + $KhushiData['today_reverse_ok'];
                // }
                // if($user->id == "4"){
                    
                //     $khushiPayinAmount = $KhushiData['today_payin_piq'];

                //     $setting = Setting::where('user_id', 4)->first();
                //     $user=User::find(4);
                //     $surplus=SurplusAmount::find(1);
                //     $previousAmount=$user->temp_amount;
                //     $user->temp_amount = $khushiPayinAmount;
                //     $user->save();
                //     $surplus->easypaisa = $surplus->easypaisa+$previousAmount-$khushiPayinAmount;
                //     $surplus->save();

                //     $setting->easypaisa= $setting->easypaisa-$previousAmount+$khushiPayinAmount;
                //     $setting->payout_balance = $setting->payout_balance-$previousAmount+$khushiPayinAmount;
                //     $setting->save();

                //     $transactionSumEP = $transactionSumEP + $khushiPayinAmount;
                //     $transactionReverseHalf = $transactionReverseHalf + $KhushiData['today_reverse_piq'];
                // }
                $payinFeeJC = $user->payin_fee;
                $payinFeeEP = $user->payin_ep_fee;
                $PayoutFeeJC = $user->payout_fee;
                $PayoutFeeEP = $user->payout_fee;
            
                // Calculate balances
                // if($user->id == 2 || $user->id == 18){
                    $payinBal = $closingBal + $transactionSumJC + $transactionSumEP - ($transactionSumJC * $payinFeeJC) - ($transactionSumEP * $payinFeeEP) - $transactionReverseHalf;
                // }else{
                //     $payinBal = $closingBal + $transactionSumJC - ($transactionSumJC * $payinFeeJC) - $transactionReverseHalf;
                // }
                $settleAmount = $payoutSumJC + $payoutSumEP + ($payoutSumJC * $PayoutFeeJC) + ($payoutSumEP * $PayoutFeeEP) + $todayUsdt;
                $pnl_amount=round($transactionSumJC * 0.01, 2);
                $total_pnl_amount=$pnl_amount+$prev_pnl-$prev_usdt_pnl;
                // Create a summary for the user
                $sumamry->update([
                    'date' => Carbon::today()->subDay(1)->format('y-m-d'),
                    'user_id' => $user->id,
                    'opening_bal'  => $closingBal,
                    'jc_payin' => $transactionSumJC,
                    'ep_payin' => $transactionSumEP,
                    'jc_payin_fee' => $transactionSumJC * $payinFeeJC,
                    'ep_payin_fee' => $transactionSumEP * $payinFeeEP,
                    'reverse_amount' =>$transactionReverseHalf,
                    'payin_bal' => $payinBal,
                    'jc_payout' => $payoutSumJC,
                    'ep_payout' => $payoutSumEP,
                    'jc_payout_fee' => $payoutSumJC * $PayoutFeeJC,
                    'ep_payout_fee' => $payoutSumEP * $PayoutFeeEP,
                    'usdt' => $sumamry->usdt,
                    'settled' => $settleAmount,
                    'closing_bal' => $payinBal - $settleAmount,
                    'pnl_amount' => $pnl_amount,
                    'total_pnl_amount' => $total_pnl_amount,
                ]);
                
            }
            else{
                Settlement::create([
                    'date' => Carbon::today()->subDay(1)->format('y-m-d'),
                    'user_id' => $user->id,
                    'opening_bal' => '0',
                    'jc_payin' => '0',
                    'ep_payin' => '0',
                    'jc_payin_fee' => '0',
                    'ep_payin_fee' => '0',
                    'reverse_amount' => '0',
                    'payin_bal' => '0',
                    'jc_payout' => '0',
                    'ep_payout' => '0',
                    'jc_payout_fee' => '0',
                    'ep_payout_fee' => '0',
                    'usdt' => '0',
                    'settled' => '0',
                    'closing_bal' => '0',
                    'pnl_amount' => '0',
                    'total_pnl_amount' => '0',
                    'usdt_pnl_amount' => '0',
                ]);
                User::query()->update([
                    'temp_amount' => 0
                ]);
            }
        }
    }
}
