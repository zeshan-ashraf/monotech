<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\Admin\ReversalDataTable;
use App\Http\Controllers\Controller;
use App\Services\TransactionReversalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TransactionReversalController extends Controller
{
    protected $reversalService;

    public function __construct(TransactionReversalService $reversalService)
    {
        $this->middleware(['permission:Reverse Transactions']);
        $this->reversalService = $reversalService;
    }

    /**
     * Display pending reversals page
     */
    public function index(ReversalDataTable $dataTable)
    {
        return $dataTable->render('admin.transaction.reversals');
    }

    /**
     * Mark transaction for reversal
     */
    public function markForReversal(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'table_type' => 'nullable|in:transactions,archeive_transactions,backup_transactions'
        ]);

        $transactionId = $request->id;
        //$tableType = $request->table_type ?? $this->reversalService->getTableType($transactionId);
        $tableType = $this->reversalService->getTableType($transactionId);

        if (!$tableType) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        $result = $this->reversalService->markForReversal($transactionId, $tableType);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Transaction marked for reversal. Countdown started.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to mark transaction for reversal. Transaction may not be eligible.'
        ], 400);
    }

    /**
     * Cancel reversal request
     */
    public function cancelReversal(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'table_type' => 'nullable|in:transactions,archeive_transactions,backup_transactions'
        ]);

        $transactionId = $request->id;
        $tableType = $request->table_type ?? $this->reversalService->getTableType($transactionId);

        if (!$tableType) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        $result = $this->reversalService->cancelReversal($transactionId, $tableType);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Reversal request cancelled successfully.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to cancel reversal request.'
        ], 400);
    }

    /**
     * Reverse transaction immediately (bypass 6-hour wait)
     */
    public function reverseNow(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'table_type' => 'nullable|in:transactions,archeive_transactions,backup_transactions'
        ]);

        $transactionId = $request->id;
        $tableType = $request->table_type ?? $this->reversalService->getTableType($transactionId);

        if (!$tableType) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        $result = $this->reversalService->reverseNow($transactionId, $tableType);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Transaction reversed successfully.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to reverse transaction. Transaction may not be eligible.'
        ], 400);
    }

    /**
     * Bulk reverse selected transactions
     */
    public function bulkReverse(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer',
            'table_types' => 'nullable|array',
            'table_types.*' => 'nullable|in:transactions,archeive_transactions,backup_transactions'
        ]);

        $ids = $request->ids;
        $tableTypes = $request->table_types ?? [];
        $successCount = 0;
        $failedCount = 0;

        foreach ($ids as $index => $transactionId) {
            $tableType = $tableTypes[$index] ?? $this->reversalService->getTableType($transactionId);
            
            if (!$tableType) {
                $failedCount++;
                continue;
            }

            $result = $this->reversalService->reverseNow($transactionId, $tableType);
            
            if ($result) {
                $successCount++;
            } else {
                $failedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Reversed {$successCount} transaction(s). " . ($failedCount > 0 ? "{$failedCount} failed." : ''),
            'success_count' => $successCount,
            'failed_count' => $failedCount
        ]);
    }
}
