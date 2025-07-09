<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{Transaction,SurplusAmount,Setting,User};
use App\Service\StatusService;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class EasyPaisaCheckTransactionStatus extends Command
{
    // The name and signature of the console command.
    protected $signature = 'transactions:easypaisa-check-status';

    // The console command description.
    protected $description = 'Check status of pending transactions and update them.';

    // Dependency injection for the StatusService
    protected $statusService;

    public function __construct(StatusService $statusService)
    {
        parent::__construct();
        $this->statusService = $statusService;
    }

    // Execute the console command.
    public function handle()
    {
        $now=Carbon::now();
        
        $list = Transaction::where('status', 'pending')->where('txn_type', 'easypaisa')->get();
        // \Log::info('Response from notifyurl:', ['response' => $now]);
        
        set_time_limit(0);

        if ($list->isNotEmpty()) {
            foreach ($list as $item) {
                $url=$item->url;

                $result = $this->statusService->process($item);
              
                // Check if the response code is successful
                if ($result['responseCode'] == '0000') {
                    // Check transactionStatus in response
                    if ($result['transactionStatus'] == 'PAID') {
                        $item->update([
                            'status' => 'success',
                            'transactionId' => $result['transactionId'] ?? $result['msisdn'] ?? null
                        ]);
                        $data = [
                            'orderId' => $item->orderId,
                            'TID' => $item->transactionId,
                            'amount' => $item->amount,
                            'status' => 'success',
                        ];
                        
                        $user = User::find($item->user_id);

                        if ($user && $user->per_payin_fee) {
                            $rate = $user->per_payin_fee;
                            $amount = $item->amount * $rate;
                        
                            $surplus = SurplusAmount::find(1);
                            $setting = Setting::where('user_id', $item->user_id)->first();
                        
                            if ($setting && $surplus) {
                                $setting->easypaisa += $amount;
                                $setting->payout_balance += $amount;
                                $setting->save();
                        
                                $surplus->easypaisa -= $amount;
                                $surplus->save();
                            }
                        }
                        $response = Http::timeout(60)->post($url, $data);
                    } elseif ($result['transactionStatus'] == 'FAILED') {
                        $item->update([
                            'status' => 'failed',
                            'transactionId'=>$result['transactionId'] ?? $result['msisdn'] ?? null,
                            'pp_code' => $result['errorCode'] ?? null,
                            'pp_message' => $result['errorReason'] ?? null
                        ]);
                        $data = [
                            'orderId' => $item->orderId,
                            'TID' => $item->transactionId,
                            'amount' => $item->amount,
                            'status' => 'failed',
                        ];
                        $response = Http::timeout(60)->post($url, $data);
                    }
                } elseif ($result['responseCode'] == '0003') {
                    // Transaction failed, update and notify
                    $item->update([
                        'status' => 'failed',
                        'pp_code' => $result['responseCode'],
                        'pp_message' => $result['responseDesc']
                    ]);
                    $data = [
                        'orderId' => $item->orderId,
                        'TID' => $item->transactionId,
                        'amount' => $item->amount,
                        'status' => 'failed',
                    ];
                    $response = Http::timeout(60)->post($url, $data);
                }

            }
        }

        $this->info('Pending transactions checked and updated.');
    }
}
