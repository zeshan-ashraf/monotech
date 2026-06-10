<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\{TempAmountPayout,Settlement};
use Illuminate\Support\Facades\Cache;

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
        $totals = Settlement::whereDate('date', today())
            ->selectRaw('
                COALESCE(SUM(jc_payout), 0) as payoutSumJC,
                COALESCE(SUM(ep_payout), 0) as payoutSumEP,
                COALESCE(SUM(ibft_amount), 0) as ibftAmount
            ')
            ->first();

        $payoutSumJC=$totals->payoutSumJC;
        $payoutSumEP=$totals->payoutSumEP;
        $ibftAmount=$totals->ibftAmount;

        $jc_temp_amount = $payoutSumJC + $ibftAmount;
        $ep_temp_amount = $payoutSumEP;

        TempAmountPayout::update([
            'jc_amount' => $jc_temp_amount,
            'ep_amount' => $ep_temp_amount,
        ]);
                    
        $this->info('Adding surplus amount in wallet successfully.');
    }
}