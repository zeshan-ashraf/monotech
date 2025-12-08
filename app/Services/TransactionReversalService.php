<?php

namespace App\Services;

use App\Models\{Transaction, ArcheiveTransaction, BackupTransaction};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Transaction Reversal Service
 * 
 * This service handles transaction reversal operations.
 * Can be easily copied to other projects.
 */
class TransactionReversalService
{
    /**
     * Number of hours to wait before auto-reversal
     * Can be configured via .env REVERSAL_WAIT_HOURS or default to 6
     */
    protected $waitHours;

    public function __construct()
    {
        $this->waitHours = env('REVERSAL_WAIT_HOURS', 6);
    }

    /**
     * Mark a transaction for reversal
     * 
     * @param int $transactionId
     * @param string $tableType 'transactions', 'archeive_transactions', or 'backup_transactions'
     * @return bool
     */
    public function markForReversal($transactionId, $tableType = 'transactions')
    {
        try {
            $model = $this->getModel($tableType);
            $transaction = $model->find($transactionId);

            if (!$transaction) {
                Log::error("Transaction not found for reversal", [
                    'transaction_id' => $transactionId,
                    'table_type' => $tableType
                ]);
                return false;
            }

            // Only mark if status is 'success' and not already reversed
            if ($transaction->status === 'success' && $transaction->status !== 'reverse') {
                $transaction->reverse_requested_at = now();
                $transaction->save();

                Log::info("Transaction marked for reversal", [
                    'transaction_id' => $transactionId,
                    'table_type' => $tableType,
                    'reverse_requested_at' => $transaction->reverse_requested_at
                ]);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Error marking transaction for reversal", [
                'transaction_id' => $transactionId,
                'table_type' => $tableType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Cancel reversal request (before 6 hours)
     * 
     * @param int $transactionId
     * @param string $tableType
     * @return bool
     */
    public function cancelReversal($transactionId, $tableType = 'transactions')
    {
        try {
            $model = $this->getModel($tableType);
            $transaction = $model->find($transactionId);

            if (!$transaction) {
                return false;
            }

            // Only cancel if reverse_requested_at is set and status is not already 'reverse'
            if ($transaction->reverse_requested_at && $transaction->status !== 'reverse') {
                $transaction->reverse_requested_at = null;
                $transaction->status = 'success'; // Change back to success
                $transaction->save();

                Log::info("Transaction reversal cancelled", [
                    'transaction_id' => $transactionId,
                    'table_type' => $tableType
                ]);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Error cancelling transaction reversal", [
                'transaction_id' => $transactionId,
                'table_type' => $tableType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Reverse a transaction immediately (bypass 6-hour wait)
     * 
     * @param int $transactionId
     * @param string $tableType
     * @return bool
     */
    public function reverseNow($transactionId, $tableType = 'transactions')
    {
        try {
            $model = $this->getModel($tableType);
            $transaction = $model->find($transactionId);

            if (!$transaction) {
                return false;
            }

            // Only reverse if status is 'success' and not already reversed
            if ($transaction->status === 'success' && $transaction->status !== 'reverse') {
                $transaction->status = 'reverse';
                $transaction->reverse_requested_at = null; // Clear the request timestamp
                $transaction->save();

                Log::info("Transaction reversed immediately", [
                    'transaction_id' => $transactionId,
                    'table_type' => $tableType
                ]);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Error reversing transaction", [
                'transaction_id' => $transactionId,
                'table_type' => $tableType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get pending reversals (transactions waiting for auto-reversal)
     * 
     * @return \Illuminate\Support\Collection
     */
    public function getPendingReversals()
    {
        $pending = collect();

        // Get from transactions table
        $transactions = Transaction::whereNotNull('reverse_requested_at')
            ->where('status', '!=', 'reverse')
            ->where('status', 'success')
            ->with('user')
            ->get()
            ->map(function ($item) {
                $item->table_type = 'transactions';
                return $item;
            });

        // Get from archeive_transactions table
        $archeive = ArcheiveTransaction::whereNotNull('reverse_requested_at')
            ->where('status', '!=', 'reverse')
            ->where('status', 'success')
            ->with('user')
            ->get()
            ->map(function ($item) {
                $item->table_type = 'archeive_transactions';
                return $item;
            });

        // Get from backup_transactions table
        $backup = BackupTransaction::whereNotNull('reverse_requested_at')
            ->where('status', '!=', 'reverse')
            ->where('status', 'success')
            ->with('user')
            ->get()
            ->map(function ($item) {
                $item->table_type = 'backup_transactions';
                return $item;
            });

        return $pending->merge($transactions)->merge($archeive)->merge($backup);
    }

    /**
     * Process auto-reversals (called by cron job)
     * Finds transactions where reverse_requested_at + 6 hours <= now()
     * 
     * @return int Number of transactions reversed
     */
    public function processAutoReversals()
    {
        $cutoffTime = now()->subHours($this->waitHours);
        $reversedCount = 0;

        try {
            // Process transactions table
            $transactions = Transaction::whereNotNull('reverse_requested_at')
                ->where('reverse_requested_at', '<=', $cutoffTime)
                ->where('status', '!=', 'reverse')
                ->where('status', 'success')
                ->get();

            foreach ($transactions as $transaction) {
                $transaction->status = 'reverse';
                $transaction->reverse_requested_at = null;
                $transaction->save();
                $reversedCount++;
            }

            // Process archeive_transactions table
            $archeive = ArcheiveTransaction::whereNotNull('reverse_requested_at')
                ->where('reverse_requested_at', '<=', $cutoffTime)
                ->where('status', '!=', 'reverse')
                ->where('status', 'success')
                ->get();

            foreach ($archeive as $transaction) {
                $transaction->status = 'reverse';
                $transaction->reverse_requested_at = null;
                $transaction->save();
                $reversedCount++;
            }

            // Process backup_transactions table
            $backup = BackupTransaction::whereNotNull('reverse_requested_at')
                ->where('reverse_requested_at', '<=', $cutoffTime)
                ->where('status', '!=', 'reverse')
                ->where('status', 'success')
                ->get();

            foreach ($backup as $transaction) {
                $transaction->status = 'reverse';
                $transaction->reverse_requested_at = null;
                $transaction->save();
                $reversedCount++;
            }

            if ($reversedCount > 0) {
                Log::info("Auto-reversed transactions", [
                    'count' => $reversedCount,
                    'cutoff_time' => $cutoffTime
                ]);
            }

            return $reversedCount;
        } catch (\Exception $e) {
            Log::error("Error processing auto-reversals", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $reversedCount;
        }
    }

    /**
     * Calculate remaining time until auto-reversal
     * 
     * @param Carbon $reverseRequestedAt
     * @return string Format: HH:MM:SS
     */
    public function getRemainingTime($reverseRequestedAt)
    {
        if (!$reverseRequestedAt) {
            return '00:00:00';
        }

        $deadline = $reverseRequestedAt->copy()->addHours($this->waitHours);
        $now = now();

        if ($now >= $deadline) {
            return '00:00:00'; // Time expired
        }

        $diff = $now->diff($deadline);
        $hours = str_pad($diff->h + ($diff->days * 24), 2, '0', STR_PAD_LEFT);
        $minutes = str_pad($diff->i, 2, '0', STR_PAD_LEFT);
        $seconds = str_pad($diff->s, 2, '0', STR_PAD_LEFT);

        return "{$hours}:{$minutes}:{$seconds}";
    }

    /**
     * Get the appropriate model based on table type
     * 
     * @param string $tableType
     * @return Model
     */
    protected function getModel($tableType)
    {
        switch ($tableType) {
            case 'archeive_transactions':
                return new ArcheiveTransaction();
            case 'backup_transactions':
                return new BackupTransaction();
            default:
                return new Transaction();
        }
    }

    /**
     * Determine table type from transaction ID
     * Checks all three tables to find the transaction
     * 
     * @param int $transactionId
     * @return string|null
     */
    public function getTableType($transactionId)
    {
        if (Transaction::find($transactionId)) {
            return 'transactions';
        }
        if (ArcheiveTransaction::find($transactionId)) {
            return 'archeive_transactions';
        }
        if (BackupTransaction::find($transactionId)) {
            return 'backup_transactions';
        }
        return null;
    }
}
