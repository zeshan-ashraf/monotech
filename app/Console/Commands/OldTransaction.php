<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Carbon\Carbon;
use App\Models\BackupTransaction;

class OldTransaction extends Command
{
    protected $signature = 'transactions:old';
    protected $description = 'Move transactions older than last two month data to the old table';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        BackupTransaction::whereDate('created_at', '<', Carbon::today()->subMonths(2))
            ->chunkById(500, function ($transactions) {
                $insertData = [];
    
                foreach ($transactions as $transaction) {
                    $insertData[] = [
                        'id' => $transaction->id,
                        'user_id' => $transaction->user_id,
                        'phone' => $transaction->phone ? mb_convert_encoding($transaction->phone, 'UTF-8', 'UTF-8') : null,
                        'orderId' => $transaction->orderId ?? null,
                        'amount' => $transaction->amount ?? null,
                        'txn_ref_no' => $transaction->txn_ref_no ?? null,
                        'transactionId' => $transaction->transactionId ?? null,
                        'txn_type' => $transaction->txn_type ?? null,
                        'pp_code' => $transaction->pp_code ?? null,
                        'pp_message' => $transaction->pp_message ?? null,
                        'status' => $transaction->status ?? null,
                        'src' => $transaction->src ?? null,
                        'url' => $transaction->url ?? null,
                        'created_at' => $transaction->created_at,
                        'updated_at' => $transaction->updated_at,
                    ];
                }
    
                // Insert all transactions from this chunk in one query
                DB::table('old_transactions')->insert($insertData);
    
                // Delete all transactions from archive table in one query
                DB::table('backup_transactions')
                    ->whereIn('id', collect($transactions)->pluck('id'))
                    ->delete();
            });
    
        $this->info('Transactions older than two month have been archived.');
    }

}
