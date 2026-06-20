<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AutoFailPendingTransactions extends Command
{
    protected $signature = 'transactions:auto-fail';
    protected $description = 'Mark pending transactions as failed after 60 minutes';

    public function handle()
    {
        $cutoffTime = Carbon::now()->subMinutes(60);

        $count = Transaction::where('status', 'pending')
            ->where('created_at', '<=', $cutoffTime)
            ->update([
                'status' => 'failed',
                'pp_code' => '999',
                'pp_message' => 'Auto-failed after 60 minutes'
            ]);

        DB::table('backup_transactions')
            ->where('created_at', '<', now()->subMonths(2))
            ->limit(1000)
            ->delete();


        $this->info("Updated $count transaction(s) to failed.");
    }
}
