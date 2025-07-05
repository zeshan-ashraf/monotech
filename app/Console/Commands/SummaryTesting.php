<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{Transaction,Payout};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Summary;

class SummaryTesting extends Command
{
    protected $signature = 'summary-test:add-report';
    protected $description = 'Create last date summary';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $userIds = [18, 19];

        // Today's date
        $today = Carbon::today();
        
        // Iterate through each user ID
        foreach ($userIds as $user_id) {
            // Get yesterday's closing balance
            $closingBal = DB::table('summary')
                ->where('user_id', $user_id)
                ->whereDate('date', Carbon::today()->subDay(2)->format('y-m-d'))
                ->value('closing_bal');
                
            // Sum of successful transaction amounts
            $transactionSum = DB::table('transactions')
                ->where('user_id', $user_id)
                ->where('status', 'success')
                ->whereDate('created_at', Carbon::today()->subDay())
                ->sum('amount');
        
            // Sum of successful payout amounts
            $payoutSum = DB::table('payouts')
                ->where('user_id', $user_id)
                ->where('status', 'success')
                ->whereDate('created_at', Carbon::today()->subDay())
                ->sum('amount');
        
            // Determine payin fee percentage
            $payinFee = $user_id == 18 ? 0.045 : 0.035;
        
            // Calculate balances
            $payinBal = $closingBal + $transactionSum - ($transactionSum * $payinFee);
            $settleAmount = $payoutSum + ($payoutSum * 0.02);
        
            // Create a summary for the user
            
            Summary::create([
                'date' => Carbon::today()->subDay()->format('y-m-d'),
                'user_id' => $user_id,
                'opening_bal' => $closingBal,
                'payin' => $transactionSum,
                'payin_fee' => $transactionSum * $payinFee,
                'payin_bal' => $payinBal,
                'settled' => $settleAmount,
                'payout' => $payoutSum,
                'usdt' => '0',
                // 'available_bal' => $assignedPayoutBln, // Uncomment if needed
                'payout_fee' => $payoutSum * 0.02,
                'closing_bal' => $payinBal - $settleAmount,
            ]);
        }

        $this->info('Summary added.');
    }
}
