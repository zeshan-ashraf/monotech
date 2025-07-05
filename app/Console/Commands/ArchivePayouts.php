<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Carbon\Carbon;
use App\Models\Payout;

class ArchivePayouts extends Command
{
    protected $signature = 'payouts:archive';
    protected $description = 'Move payouts older than 3 days to the archive table';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $list = Payout::whereDate('created_at', '<', Carbon::today())->get();
        if ($list->isNotEmpty()) {
            foreach ($list as $item) {
                DB::table('archeive_payouts')->insert([
                    'id' => $item->id,
                    'user_id' => $item->user_id,
                    'orderId' => $item->orderId ?? null,
                    'code' => $item->code ?? null,
                    'message' => $item->message ?? null,
                    'transaction_reference' => $item->transaction_reference ?? null,
                    'amount' => $item->amount ?? null,
                    'fee' => $item->fee ?? null,
                    'phone' => $item->phone ?? null,
                    'transaction_type' => $item->transaction_type ?? null,
                    'transaction_id' => $item->transaction_id ?? null,
                    'url' => $item->url ?? null,
                    'status' => $item->status ?? null,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ]);
                DB::table('payouts')->where('id', $item->id)->delete();
            }
        }
        
        $this->info('Payouts older than 1 day have been archived.');
    }
}
