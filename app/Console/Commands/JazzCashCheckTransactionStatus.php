<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{Transaction,SurplusAmount,Setting,User};
use App\Service\StatusService;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class JazzCashCheckTransactionStatus extends Command
{
    // The name and signature of the console command.
    protected $signature = 'transactions:jazzcash-check-status';

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
        
        $list = Transaction::where('status', 'pending')->where('txn_type', 'jazzcash')->get();
        // \Log::info('Response from notifyurl:', ['response' => $now]);
        
        set_time_limit(0);

        if ($list->isNotEmpty()) {
            foreach ($list as $item) {
                $url=$item->url;
                $result = $this->statusService->process($item);
            
                if ($result['pp_ResponseCode'] == '000' && $result['pp_PaymentResponseCode'] == '121') {
                    // Success condition
                    $item->update([
                        'status' => 'success',
                        'transactionId'=>$result['pp_AuthCode'],
                        'pp_code' => $result['pp_ResponseCode'],
                        'pp_message' => $result['pp_ResponseMessage']
                    ]);
                    $data = [
                        'orderId' => $item->orderId,
                        'tid' => $item->transactionId,
                        'amount' => $item->amount,
                        'status' => 'success',
                    ];
                    $user = User::find($item->user_id);

                    if ($user && $user->per_payin_fee) {
                        $rate = $user->per_payin_fee;
                        $amount = $item->amount * $rate;
                    
                        $surplus = SurplusAmount::find(1);
                        $setting = Setting::where('user_id', $item->user_id)->first();
                    
                        if ($setting && $surplus && $setting->auto ==1) {
                            $setting->jazzcash += $amount;
                            $setting->payout_balance += $amount;
                            $setting->save();
                    
                            $surplus->jazzcash -= $amount;
                            $surplus->save();
                        }
                    }
                    $response = Http::timeout(60)->post($url, $data);
                } elseif ($result['pp_PaymentResponseCode'] == '157'){
                    $item->update([
                        'status' => 'pending',
                        'transactionId'=>$result['pp_AuthCode'],
                        'pp_code' => $result['pp_PaymentResponseCode'],
                        'pp_message' => $result['pp_PaymentResponseMessage']
                    ]);
                }else {
                    // Failure condition
                    $item->update([
                        'status' => 'failed',
                        'transactionId'=>$result['pp_AuthCode'],
                        'pp_code' => $result['pp_PaymentResponseCode'],
                        'pp_message' => $result['pp_PaymentResponseMessage']
                    ]);
            
                    $data = [
                        'orderId' => $item->orderId,
                        'tid' => $item->transactionId,
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
