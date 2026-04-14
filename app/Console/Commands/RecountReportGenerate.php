<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\{Settlement,SurplusAmount,Setting,User};

class RecountReportGenerate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:recount-report-generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users=User::where('user_role','Client')->where('active',1)->get();
        $transactionReverseHalf = 0;
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

                $todayWalletTrans = DB::table('settlements')
                    ->where('user_id', $user->id)
                    ->whereDate('date', Carbon::today()->subDay(1)->format('y-m-d'))
                    ->value('wallet_transfer');
                
                // Sum of successful transaction amounts
                $transactionSumJC = DB::table('transactions')
                    ->where('user_id', $user->id)
                    ->whereIn('status', ['success', 'reverse'])
                    ->where('txn_type', 'jazzcash')
                    ->whereDate('created_at', Carbon::today()->subDay(1))
                    ->sum('amount');

                $transactionReverse = DB::table('transactions')
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
                $transactionSumEP = DB::table('transactions')
                    ->where('user_id', $user->id)
                    ->whereIn('status', ['success', 'reverse'])
                    ->where('txn_type', 'easypaisa')
                    ->whereDate('created_at', Carbon::today()->subDay(1))
                    ->sum('amount');

                // Sum of successful payout amounts
                $payoutSumJC = DB::table('payouts')
                    ->where('user_id', $user->id)
                    ->where('status', 'success')
                    ->where('transaction_type', 'jazzcash')
                    ->whereDate('created_at', Carbon::today()->subDay(1))
                    ->sum('amount');
                
                $payoutSumEP = DB::table('payouts')
                    ->where('user_id', $user->id)
                    ->where('status', 'success')
                    ->where('transaction_type', 'easypaisa')
                    ->whereDate('created_at', Carbon::today()->subDay(1))
                    ->sum('amount');

                $payinFeeJC = $user->payin_fee;
                $payinFeeEP = $user->payin_ep_fee;
                $PayoutFeeJC = $user->payout_fee;
                $PayoutFeeEP = $user->payout_ep_fee;
            
                $op_cln=($transactionSumJC + $transactionSumEP) * 0.015 + ($payoutSumJC + $payoutSumEP) * 0.0075 +  $transactionReverseHalf;

                // Calculate balances
                // if($user->id == 2 || $user->id == 18){
                    $payinBal = $closingBal + $transactionSumJC + $transactionSumEP - ($transactionSumJC * $payinFeeJC) - ($transactionSumEP * $payinFeeEP) - $transactionReverseHalf;
                // }else{
                //     $payinBal = $closingBal + $transactionSumJC - ($transactionSumJC * $payinFeeJC) - $transactionReverseHalf;
                // }4
                $settleAmount = $payoutSumJC + $payoutSumEP + ($payoutSumJC * $PayoutFeeJC) + ($payoutSumEP * $PayoutFeeEP) + $todayUsdt + $todayWalletTrans;
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
                    'op_cln' => $op_cln,
                    'usdt' => $sumamry->usdt,
                    'wallet_transfer' => $sumamry->wallet_transfer,
                    'settled' => $settleAmount,
                    'closing_bal' => $payinBal - $settleAmount,
                    'pnl_amount' => $pnl_amount,
                    'total_pnl_amount' => $total_pnl_amount,
                ]);
                
            }
        }
    }
}
