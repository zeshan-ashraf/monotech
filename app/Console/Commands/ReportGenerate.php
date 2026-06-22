<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\{Settlement, SurplusAmount, TempAmountPayout, User};
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
     * @return int
     */
    public function handle()
    {
        $lock = Cache::lock('report:generate:lock', 900);

        if (!$lock->get()) {
            Log::channel('schedule_debug')->warning('report:generate skipped — another instance is already running');
            $this->warn('Another instance of report:generate is already running.');

            return Command::SUCCESS;
        }

        try {
            $users = User::where('user_role', 'Client')->where('active', 1)->get();
            $transactionReverseHalf = 0;
            foreach ($users as $user) {
                $sumamry = Settlement::where('user_id', $user->id)->whereDate('date', Carbon::today()->format('y-m-d'))->first();
                if ($sumamry) {
                    // Get yesterday's closing balance
                    $preClosingBal = DB::table('settlements')
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

                    // if ($user->id == 2) {
                    //     $transactionReverseHalf = $totalReverseAmount * 0.5;
                    // } else {
                        $transactionReverseHalf = $totalReverseAmount;
                    // }
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
                    $ibftAmount = 0;
                    if ($user->id == "24") {
                        $url = 'https://novapay.pk/api/get-nova-payout';
                        $response = Http::get($url);
                        $data = $response->json();
                        $payoutSumEP = $data['today_ok_ep_mono_payout'];
                        $ibftAmount = $data['today_ok_ep_mono_mmbl_payout'];
                    } elseif ($user->id == "26") {
                        $url = 'https://khushiconnect.com/api/get-khushi-payout';
                        $response = Http::get($url);
                        $data = $response->json();
                        $payoutSumEP = $data['today_ep_mono_payout'];
                        $ibftAmount = $data['today_ep_mono_mmbl_payout'];
                    } else {
                        $payoutSumEP = DB::table('payouts')
                            ->where('user_id', $user->id)
                            ->where('status', 'success')
                            ->where('transaction_type', 'easypaisa')
                            ->where('ibft', '0')
                            ->whereDate('created_at', Carbon::today())
                            ->sum('amount');
                        $ibftAmount = DB::table('payouts')
                            ->where('user_id', $user->id)
                            ->where('status', 'success')
                            ->where('transaction_type', 'easypaisa')
                            ->where('ibft', '1')
                            ->whereDate('created_at', Carbon::today())
                            ->sum('amount');
                    }

                    $payinFeeJC = $user->payin_fee;
                    $payinFeeEP = $user->payin_ep_fee;
                    $PayoutFeeJC = $user->payout_fee;
                    $PayoutFeeEP = $user->payout_ep_fee;
                    if ($user->id == "24" || $user->id == "26") {
                        $op_cln = 0;
                        $rev_cln = 0;
                        $settleAmount = 0;
                        $payinBal = 0;
                        $closingBal = 0;
                    } else {
                        $op_cln = ($transactionSumJC + $transactionSumEP) * 0.015 + ($payoutSumJC + $payoutSumEP) * 0.0075 + $transactionReverseHalf;
                        $rev_cln = ($transactionSumJC * $payinFeeJC + $transactionSumEP * $payinFeeEP) + ($payoutSumJC * $PayoutFeeJC + $payoutSumEP * $PayoutFeeEP) - $op_cln;
                        $settleAmount = $payoutSumJC + $payoutSumEP + $ibftAmount + ($payoutSumJC * $PayoutFeeJC) + ($payoutSumEP * $PayoutFeeEP) + ($ibftAmount * $PayoutFeeEP) + $todayUsdt + $todayWalletTrans;
                        $payinBal = $preClosingBal + $transactionSumJC + $transactionSumEP - ($transactionSumJC * $payinFeeJC) - ($transactionSumEP * $payinFeeEP) - $transactionReverseHalf;
                        $closingBal = $payinBal - $settleAmount;
                    }

                    $pnl_amount = round($transactionSumJC * 0.01, 2);
                    $total_pnl_amount = $pnl_amount + $prev_pnl - $prev_usdt_pnl;

                    // Create a summary for the user
                    $sumamry->update([
                        'date' => Carbon::today()->format('y-m-d'),
                        'user_id' => $user->id,
                        'opening_bal' => $preClosingBal,
                        'jc_payin' => $transactionSumJC,
                        'ep_payin' => $transactionSumEP,
                        'jc_payin_fee' => $transactionSumJC * $payinFeeJC,
                        'ep_payin_fee' => $transactionSumEP * $payinFeeEP,
                        'reverse_amount' => $transactionReverseHalf,
                        'payin_bal' => $payinBal,
                        'jc_payout' => $payoutSumJC,
                        'ep_payout' => $payoutSumEP,
                        'jc_payout_fee' => $payoutSumJC * $PayoutFeeJC,
                        'ep_payout_fee' => $payoutSumEP * $PayoutFeeEP + ($ibftAmount * $PayoutFeeEP),
                        'op_cln' => $op_cln,
                        'rev_cln' => $rev_cln,
                        'usdt' => $sumamry->usdt,
                        'wallet_transfer' => $sumamry->wallet_transfer,
                        'settled' => $settleAmount,
                        'closing_bal' => $closingBal,
                        'pnl_amount' => $pnl_amount,
                        'total_pnl_amount' => $total_pnl_amount,
                        'ibft_amount' => $ibftAmount,
                    ]);
                } else {
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
                        'op_cln' => '0',
                        'rev_cln' => '0',
                        'usdt' => '0',
                        'wallet_transfer' => '0',
                        'settled' => '0',
                        'closing_bal' => '0',
                        'pnl_amount' => '0',
                        'total_pnl_amount' => '0',
                        'usdt_pnl_amount' => '0',
                        'ibft_amount' => '0',
                    ]);
                    TempAmountPayout::query()->update([
                        'jc_amount' => 0,
                        'ep_amount' => 0,
                    ]);
                    User::query()->update([
                        'temp_amount' => 0,
                    ]);
                }
            }

            $this->info('Daily report generated successfully.');

            Cache::put('report:generate:last_completed_at', now(), now()->addDay());

            return Command::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
