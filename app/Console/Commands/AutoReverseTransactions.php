<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TransactionReversalService;

class AutoReverseTransactions extends Command
{
    protected $signature = 'transactions:auto-reverse';
    protected $description = 'Automatically reverse transactions that have passed the 6-hour waiting period';

    protected $reversalService;

    public function __construct(TransactionReversalService $reversalService)
    {
        parent::__construct();
        $this->reversalService = $reversalService;
    }

    public function handle()
    {
        $this->info('Starting auto-reversal process...');
        
        $reversedCount = $this->reversalService->processAutoReversals();
        
        if ($reversedCount > 0) {
            $this->info("Successfully reversed {$reversedCount} transaction(s).");
        } else {
            $this->info('No transactions found for auto-reversal.');
        }

        return 0;
    }
}
