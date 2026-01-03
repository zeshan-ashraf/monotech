<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\{Settlement,SurplusAmount,Setting,User};
use Illuminate\Support\Facades\Http;

class ReportGenerate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate daily report for users';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $users=User::where('user_role','Client')->where('active',1)->get();
        $transactionReverseHalf = 0;
        $today = Carbon::today();
        foreach ($users as $user) {
            $sumamry= Settlement::where('user_id',$user->id)->whereDate('date', Carbon::today()->format('y-m-d'))->first();
            if($sumamry){
                // Get yesterday's closing balance
                $closingBal = DB::table('settlements')
                    ->where('user_id', $user->id)
                    ->whereDate('date', Carbon::today()->subDay(1)->format('y-m-d'))
                    ->value('closing_bal');
                $prev_pnl = DB::table('settlements')
                    ->where('user_id', $user->id)
                    ->whereDate('date', Carbon::today()->subDay(1)->format('y-m-d'))
                    ->value('total_pnl_amount');

                $prev_usdt_pnl = DB::table('settlements')
                    ->where('user_id', $user->id)
                    ->whereDate('date', Carbon::today()->format('y-m-d'))
                    ->value('usdt_pnl_amount');
                
                $todayUsdt = DB::table('settlements')
                    ->where('user_id', $user->id)
                    ->whereDate('date', Carbon::today()->format('y-m-d'))
                    ->value('usdt');

                $todayWalletTrans = DB::table('settlements')
                    ->where('user_id', $user->id)
                    ->whereDate('date', Carbon::today()->format('y-m-d'))
                    ->value('wallet_transfer');
                
                // Sum of successful transaction amounts
                $transactionSumJC = DB::table('transactions')
                    ->where('user_id', $user->id)
                    ->whereIn('status', ['success', 'reverse'])
                    ->where('txn_type', 'jazzcash')
                    ->whereDate('created_at', Carbon::today())
                    ->sum('amount');

                $transactionReverse = DB::table('transactions')
                    ->where('user_id', $user->id)
                    ->where('status', 'reverse')
                    ->whereDate('updated_at', Carbon::today())
                    ->sum('amount');

                $archiveReverse = DB::table('archeive_transactions')
                    ->where('user_id', $user->id)
                    ->where('status', 'reverse')
                    ->whereDate('updated_at', Carbon::today())
                    ->sum('amount');

                $backupReverse = DB::table('backup_transactions')
                    ->where('user_id', $user->id)
                    ->where('status', 'reverse')
                    ->whereDate('updated_at', Carbon::today())
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
                    ->whereDate('created_at', Carbon::today())
                    ->sum('amount');

                // Sum of successful payout amounts
                $payoutSumJC = DB::table('payouts')
                    ->where('user_id', $user->id)
                    ->where('status', 'success')
                    ->where('transaction_type', 'jazzcash')
                    ->whereDate('created_at', Carbon::today())
                    ->sum('amount');
                
                $payoutSumEP = DB::table('payouts')
                    ->where('user_id', $user->id)
                    ->where('status', 'success')
                    ->where('transaction_type', 'easypaisa')
                    ->whereDate('created_at', Carbon::today())
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
                $settleAmount = $payoutSumJC + $payoutSumEP + ($payoutSumJC * $PayoutFeeJC) + ($payoutSumEP * $PayoutFeeEP) + $todayUsdt + $todayWalletTrans;
                $pnl_amount=round($transactionSumJC * 0.01, 2);
                $total_pnl_amount=$pnl_amount+$prev_pnl-$prev_usdt_pnl;
                // Create a summary for the user
                $sumamry->update([
                    'date' => Carbon::today()->format('y-m-d'),
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
                    'wallet_transfer' => $sumamry->wallet_transfer,
                    'settled' => $settleAmount,
                    'closing_bal' => $payinBal - $settleAmount,
                    'pnl_amount' => $pnl_amount,
                    'total_pnl_amount' => $total_pnl_amount,
                ]);
                
            }
            else{
                Settlement::create([
                    'date' => Carbon::today()->format('y-m-d'),
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
                    'wallet_transfer' => '0',
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
        $this->info('Daily report generated successfully.');
    }
}
