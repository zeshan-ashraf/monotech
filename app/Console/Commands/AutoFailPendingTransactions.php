<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Support\PayinCallbackTracker;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoFailPendingTransactions extends Command
{
    protected $signature = 'transactions:auto-fail';
    protected $description = 'Mark pending transactions as failed after 60 minutes';

    public function handle()
    {
        $cutoffTime = Carbon::now()->subMinutes(45);
        $count = 0;

        set_time_limit(0);

        Transaction::query()
            ->where('status', 'pending')
            ->where('created_at', '<=', $cutoffTime)
            ->chunkById(100, function ($rows) use (&$count) {
                foreach ($rows as $item) {
                    $updated = Transaction::query()
                        ->where('id', $item->id)
                        ->where('status', 'pending')
                        ->update([
                            'status' => 'failed',
                            'pp_code' => '999',
                            'pp_message' => 'Auto-failed after 60 minutes',
                        ]);

                    if (! $updated) {
                        continue;
                    }

                    $item->refresh();
                    $count++;

                    PayinCallbackTracker::sendAndRecord($item, $item->url, [
                        'orderId' => $item->orderId,
                        'tid' => $item->transactionId,
                        'amount' => $item->amount,
                        'status' => 'failed',
                    ]);
                }
            });

        $this->info("Updated $count transaction(s) to failed.");
    }
}
