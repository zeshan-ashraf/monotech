<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use Carbon\Carbon;

class AutoFailPendingTransactions extends Command
{
    protected $signature = 'transactions:auto-fail';
    protected $description = 'Mark pending transactions as failed after 30 minutes';

    public function handle()
    {
        $cutoffTime = Carbon::now()->subMinutes(30);

        $count = Transaction::where('status', 'pending')
            ->where('created_at', '<=', $cutoffTime)
            ->update([
                'status' => 'failed',
                'pp_code' => '999',
                'pp_message' => 'Auto-failed after 30 minutes'
            ]);

        $this->info("Updated $count transaction(s) to failed.");
    }
}
