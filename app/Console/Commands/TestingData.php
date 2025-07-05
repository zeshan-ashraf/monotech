<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Carbon\Carbon;
use App\Models\{ArcheiveTransaction,BackupTransaction};

class TestingData extends Command
{
    protected $signature = 'transactions:testing-data';
    protected $description = 'Move transactions older than 10 days to the backup table';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $start = Carbon::today(); // Yesterday
        $end = Carbon::today()->subDays(11);  // 10 days before yesterday
    
        BackupTransaction::whereBetween('created_at', [$end, $start])
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
    
                // Insert the chunk in one query
                DB::table('archeive_transactions')->insert($insertData);
    
                // Delete in chunks for efficiency
                DB::table('backup_transactions')
                    ->whereIn('id', collect($transactions)->pluck('id'))
                    ->delete();
            });
    
        $this->info('Transactions older than 10 days have been archived.');
    }
}
