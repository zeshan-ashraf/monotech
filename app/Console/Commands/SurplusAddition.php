<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\{TempAmountPayout,Settlement,SurplusAmount};
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SurplusAddition extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'suplus:addition';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add surplus amount in wallet';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        try {
            DB::beginTransaction();

            $tempAmount = TempAmountPayout::first();
            $surplus = SurplusAmount::first();

            $totals = Settlement::whereDate('date', today())
                ->selectRaw('
                    COALESCE(SUM(jc_payout), 0) as payoutSumJC,
                    COALESCE(SUM(ep_payout), 0) as payoutSumEP,
                    COALESCE(SUM(ibft_amount), 0) as ibftAmount
                ')
                ->first();

            $totalJCPayout = $totals->payoutSumJC + $totals->ibftAmount;
            $totalEPPayout = $totals->payoutSumEP;

            $surplus->update([
                'jazzcash' => $surplus->jazzcash + $tempAmount->jc_amount - $totalJCPayout,
                'easypaisa' => $surplus->easypaisa + $tempAmount->ep_amount - $totalEPPayout,
            ]);

            $tempAmount->update([
                'jc_amount' => $totalJCPayout,
                'ep_amount' => $totalEPPayout,
            ]);

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::channel('daily')->error('Settlement Cron Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
        }
    }
}