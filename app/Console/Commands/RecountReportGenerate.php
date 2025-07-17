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
        foreach ($users as $user) {
            $sumamry= Settlement::where('user_id',$user->id)->whereDate('date', Carbon::yesterday()->format('y-m-d'))->first();
            if($sumamry){
                // Get yesterday's closing balance
                $closingBal = DB::table('settlements')
                    ->where('user_id', $user->id)
                    ->whereDate('date', Carbon::today()->subDay(2)->format('y-m-d'))
                    ->value('closing_bal');
                
                $todayUsdt = DB::table('settlements')
                    ->where('user_id', $user->id)
                    ->whereDate('date', Carbon::yesterday()->format('y-m-d'))
                    ->value('usdt');
                
                // Sum of successful transaction amounts
                $transactionSumJC = DB::table('transactions')
                    ->where('user_id', $user->id)
                    ->whereIn('status', ['success', 'reverse'])
                    ->where('txn_type', 'jazzcash')
                    ->whereDate('created_at', Carbon::yesterday())
                    ->sum('amount');
                
                $yesterday = \Carbon\Carbon::yesterday()->toDateString();
                
                $totalReverseAmount = DB::table(DB::raw("(
                    SELECT amount FROM transactions 
                    WHERE user_id = $user->id AND status = 'reverse' AND DATE(updated_at) = '$yesterday'
                    UNION ALL
                    SELECT amount FROM archeive_transactions 
                    WHERE user_id = $user->id AND status = 'reverse' AND DATE(updated_at) = '$yesterday'
                    UNION ALL
                    SELECT amount FROM backup_transactions 
                    WHERE user_id = $user->id AND status = 'reverse' AND DATE(updated_at) = '$yesterday'
                ) as combined"))
                ->sum('amount');
                
                $transactionReverseHalf = $totalReverseAmount;
                $transactionSumEP = DB::table('transactions')
                    ->where('user_id', $user->id)
                    ->whereIn('status', ['success', 'reverse'])
                    ->where('txn_type', 'easypaisa')
                    ->whereDate('created_at', Carbon::yesterday())
                    ->sum('amount');
                
                // Sum of successful payout amounts
                $payoutSumJC = DB::table('archeive_payouts')
                    ->where('user_id', $user->id)
                    ->where('status', 'success')
                    ->where('transaction_type', 'jazzcash')
                    ->whereDate('created_at', Carbon::yesterday())
                    ->sum('amount');
                
                $payoutSumEP = DB::table('archeive_payouts')
                    ->where('user_id', $user->id)
                    ->where('status', 'success')
                    ->where('transaction_type', 'easypaisa')
                    ->whereDate('created_at', Carbon::yesterday())
                    ->sum('amount');
            
                
                $payinFeeJC = $user->payin_fee;
                $payinFeeEP = $user->payin_fee;
                $PayoutFeeJC = $user->payout_fee;
                $PayoutFeeEP = $user->payout_fee;
            
                // Calculate balances
                $payinBal = $closingBal + $transactionSumJC + $transactionSumEP - ($transactionSumJC * $payinFeeJC) - ($transactionSumEP * $payinFeeEP) - $transactionReverseHalf;
                $settleAmount = $payoutSumJC + $payoutSumEP + ($payoutSumJC * $PayoutFeeJC) + ($payoutSumEP * $PayoutFeeEP) + $todayUsdt;
            
                // Create a summary for the user
                $sumamry->update([
                    'date' => Carbon::yesterday()->format('y-m-d'),
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
                ]);
                
            }
        }
    }
}
