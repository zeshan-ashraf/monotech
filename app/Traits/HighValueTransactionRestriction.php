<?php

namespace App\Traits;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait HighValueTransactionRestriction
{
    /**
     * Check if high-value transaction restriction applies
     * Restricts transactions of 50000+ from the same phone number for 10 minutes
     * after a successful or pending transaction
     *
     * @param Request $request
     * @param string $requestId
     * @param float $startTime
     * @param int $thresholdAmount
     * @param int $restrictionMinutes
     * @return array|null Returns error response array if restriction applies, null otherwise
     */
    protected function checkHighValueTransactionRestriction(
        Request $request, 
        string $requestId, 
        float $startTime,
        int $thresholdAmount = 50000,
        int $restrictionMinutes = 10
    ): ?array {
        // Debug logging to see what's happening
        \Log::info('Restriction check started', [
            'phone' => $request->phone,
            'amount' => $request->amount,
            'threshold' => $thresholdAmount,
            'request_id' => $requestId
        ]);

        // Only check if the current transaction amount meets the threshold
        if ($request->amount >= $thresholdAmount) {
            \Log::info('Amount threshold met, checking for recent transactions');
            
            $recentHighValueTransaction = Transaction::where('phone', $request->phone)
                ->where('amount', '>=', $thresholdAmount)
                ->whereIn('status', ['success', 'pending'])
                ->where('created_at', '>=', now()->subMinutes($restrictionMinutes))
                ->first();

            \Log::info('Recent transaction query result', [
                'found' => $recentHighValueTransaction ? true : false,
                'transaction_details' => $recentHighValueTransaction ? [
                    'id' => $recentHighValueTransaction->id,
                    'amount' => $recentHighValueTransaction->amount,
                    'status' => $recentHighValueTransaction->status,
                    'created_at' => $recentHighValueTransaction->created_at
                ] : null
            ]);

            if ($recentHighValueTransaction) {
                // Log the restriction trigger
                if (method_exists($this, 'logger')) {
                    $this->logger->warning('High-value transaction restriction triggered', [
                        'request_id' => $requestId,
                        'phone' => $request->phone,
                        'amount' => $request->amount,
                        'threshold_amount' => $thresholdAmount,
                        'restriction_minutes' => $restrictionMinutes,
                        'recent_transaction_id' => $recentHighValueTransaction->id,
                        'recent_transaction_status' => $recentHighValueTransaction->status,
                        'recent_transaction_time' => $recentHighValueTransaction->created_at,
                        'execution_time' => microtime(true) - $startTime
                    ]);
                }

                \Log::info('Restriction triggered - returning error response');
                return [
                    'status' => 'error',
                    'message' => "Your transactions is restricted.",
                    'code' => 429
                ];
            } else {
                \Log::info('No recent high-value transactions found - restriction not triggered');
            }
        } else {
            \Log::info('Amount below threshold - no restriction check needed');
        }

        return null;
    }

    /**
     * Check if high-value transaction restriction applies with custom statuses
     *
     * @param Request $request
     * @param string $requestId
     * @param float $startTime
     * @param int $thresholdAmount
     * @param int $restrictionMinutes
     * @param array $restrictedStatuses
     * @return array|null Returns error response array if restriction applies, null otherwise
     */
    protected function checkHighValueTransactionRestrictionWithCustomStatuses(
        Request $request, 
        string $requestId, 
        float $startTime,
        int $thresholdAmount = 50000,
        int $restrictionMinutes = 10,
        array $restrictedStatuses = ['success', 'pending']
    ): ?array {
        // Only check if the current transaction amount meets the threshold
        if ($request->amount >= $thresholdAmount) {
            $recentHighValueTransaction = Transaction::where('phone', $request->phone)
                ->where('amount', '>=', $thresholdAmount)
                ->whereIn('status', $restrictedStatuses)
                ->where('created_at', '>=', now()->subMinutes($restrictionMinutes))
                ->first();

            if ($recentHighValueTransaction) {
                // Log the restriction trigger
                if (method_exists($this, 'logger')) {
                    $this->logger->warning('High-value transaction restriction triggered', [
                        'request_id' => $requestId,
                        'phone' => $request->phone,
                        'amount' => $request->amount,
                        'threshold_amount' => $thresholdAmount,
                        'restriction_minutes' => $restrictionMinutes,
                        'restricted_statuses' => $restrictedStatuses,
                        'recent_transaction_id' => $recentHighValueTransaction->id,
                        'recent_transaction_status' => $recentHighValueTransaction->status,
                        'recent_transaction_time' => $recentHighValueTransaction->created_at,
                        'execution_time' => microtime(true) - $startTime
                    ]);
                }

                return [
                    'status' => 'error',
                    //'message' => "High-value transactions ({$thresholdAmount}+) are restricted for {$restrictionMinutes} minutes after a transaction with status: " . implode(', ', $restrictedStatuses) . " from this number.",
                    'message' => "Your transactions is restricted.",
                    'code' => 429
                ];
            }
        }

        return null;
    }
}
