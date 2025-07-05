<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Transaction;

class FetchTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:transactions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch transactions from external API and store them in the database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $url = 'https://marketmaven.com.pk/api/get-transactions';
        $response = Http::get($url);

        if ($response->successful()) {
            $data = $response->json()['transactions'];
            foreach ($data as $item) {
                $transaction = Transaction::create([
                    'user_id' => 19,
                    'orderId' => $item['orderId'],
                    'amount' => $item['amount'],
                    'txn_ref_no' => $item['txn_ref_no'],
                    'transactionId' => $item['transactionId'],
                    'txn_type' => $item['txn_type'],
                    'status' => $item['status'],
                    'pp_code' => $item['pp_code'],
                    'pp_message' => $item['pp_message'],
                    'url' => $item['url'],
                    'created_at' => $item['created_at'],
                    'updated_at' => $item['updated_at'],
                    'src' => "Market",
                ]);
            }

            $this->info('Transactions fetched and stored successfully.');
        } else {
            $this->error('Failed to fetch transactions: ' . $response->body());
        }

        return 0;
    }
}
