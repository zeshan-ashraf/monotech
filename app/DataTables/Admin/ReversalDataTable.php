<?php

namespace App\DataTables\Admin;

use App\Services\TransactionReversalService;
use App\Models\{Transaction, ArcheiveTransaction, BackupTransaction};
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;

class ReversalDataTable extends DataTable
{
    protected $reversalService;

    public function __construct()
    {
        $this->reversalService = new TransactionReversalService();
    }

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addIndexColumn()
            ->addColumn('checkbox', function ($query) {
                return '<input type="checkbox" class="transaction-checkbox" value="' . $query->id . '" data-table-type="' . ($query->table_type ?? 'transactions') . '">';
            })
            ->addColumn('client_name', function ($query) {
                // For union queries, we need to manually load the user
                $userId = $query->user_id;
                if ($userId) {
                    $user = \App\Models\User::find($userId);
                    return $user ? $user->name : '-';
                }
                return '-';
            })
            ->editColumn('status', function ($query) {
                $reason = $query->pp_message ?? '';
                $type = $query->status;
                return view('admin.transaction.badge', get_defined_vars());
            })
            ->editColumn('created_at', function ($query) {
                return $query->created_at ? $query->created_at->format('d-m-y H:i:s') : 'N/A';
            })
            ->editColumn('reverse_requested_at', function ($query) {
                return $query->reverse_requested_at ? $query->reverse_requested_at->format('d-m-y H:i:s') : 'N/A';
            })
            ->addColumn('remaining_time', function ($query) {
                if (!$query->reverse_requested_at) {
                    return 'N/A';
                }
                $remaining = $this->reversalService->getRemainingTime($query->reverse_requested_at);
                $deadline = $query->reverse_requested_at->copy()->addHours(6);
                $now = now();
                
                // Add data attribute for JavaScript countdown
                $isExpired = $now >= $deadline;
                $dataDeadline = $deadline->timestamp;
                
                return '<span class="countdown-timer" data-deadline="' . $dataDeadline . '" data-reverse-requested="' . $query->reverse_requested_at->timestamp . '">' . $remaining . '</span>';
            })
            ->editColumn('amount', function ($query) {
                return number_format($query->amount, 2) . ' PKR';
            })
            ->addColumn('actions', function ($query) {
                $tableType = $query->table_type ?? 'transactions';
                $buttons = '';
                
                // Cancel Reversal button (only if not expired)
                $deadline = $query->reverse_requested_at ? $query->reverse_requested_at->copy()->addHours(6) : null;
                if ($deadline && now() < $deadline) {
                    $buttons .= '<button class="btn btn-warning btn-sm cancel-reversal-btn" data-id="' . $query->id . '" data-table-type="' . $tableType . '">Cancel</button> ';
                }
                
                // Reverse Now button
                $buttons .= '<button class="btn btn-danger btn-sm reverse-now-btn" data-id="' . $query->id . '" data-table-type="' . $tableType . '">Reverse Now</button>';
                
                return $buttons;
            })
            ->rawColumns(['checkbox', 'remaining_time', 'actions']);
    }

    public function query()
    {
        // Use union query approach with proper select
        $transactions = Transaction::whereNotNull('reverse_requested_at')
            ->where('status', '!=', 'reverse')
            ->where('status', 'success')
            ->selectRaw("id, user_id, phone, orderId, amount, txn_ref_no, transactionId, txn_type, pp_code, pp_message, status, url, created_at, updated_at, reverse_requested_at, 'transactions' as table_type");

        $archeive = ArcheiveTransaction::whereNotNull('reverse_requested_at')
            ->where('status', '!=', 'reverse')
            ->where('status', 'success')
            ->selectRaw("id, user_id, phone, orderId, amount, txn_ref_no, transactionId, txn_type, pp_code, pp_message, status, url, created_at, updated_at, reverse_requested_at, 'archeive_transactions' as table_type");

        $backup = BackupTransaction::whereNotNull('reverse_requested_at')
            ->where('status', '!=', 'reverse')
            ->where('status', 'success')
            ->selectRaw("id, user_id, phone, orderId, amount, txn_ref_no, transactionId, txn_type, pp_code, pp_message, status, url, created_at, updated_at, reverse_requested_at, 'backup_transactions' as table_type");

        $combined = $transactions->union($archeive)->union($backup);

        return $this->applyScopes($combined);
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('reversalDataTable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('<"row align-items-center"<"col-md-2" l><"col-md-6" B><"col-md-4"f>><"table-responsive" rt><"row align-items-center" <"col-md-6" i><"col-md-6" p>><"clear">')
            ->parameters([
                "buttons" => [
                    'excel',
                ],
                "processing" => true,
                "autoWidth" => false,
                "pageLength" => 50,
                'order' => [[13, 'desc']], // Order by reverse_requested_at descending
                'drawCallback' => "function () {
                    updateCountdownTimers();
                }",
            ]);
    }

    protected function getColumns()
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all-checkbox">', 'orderable' => false, 'searchable' => false, 'width' => '30px'],
            ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => 10],
            ['data' => 'orderId', 'name' => 'orderId', 'title' => 'Order Id', 'orderable' => true, 'searchable' => true, 'width' => 30],
            ['data' => 'client_name', 'name' => 'user.name', 'title' => 'Client Name', 'orderable' => false, 'searchable' => false, 'width' => 30],
            ['data' => 'transactionId', 'name' => 'transactionId', 'title' => 'Trans Id', 'orderable' => true, 'searchable' => true, 'width' => 30],
            ['data' => 'phone', 'name' => 'phone', 'title' => 'Phone', 'orderable' => true, 'searchable' => true, 'width' => 30],
            ['data' => 'txn_ref_no', 'name' => 'txn_ref_no', 'title' => 'Trans Ref No', 'orderable' => true, 'searchable' => true, 'width' => 30],
            ['data' => 'txn_type', 'name' => 'txn_type', 'title' => 'Trans type', 'orderable' => true, 'searchable' => true, 'width' => 30],
            ['data' => 'amount', 'name' => 'amount', 'title' => 'Amount', 'orderable' => true, 'searchable' => false, 'width' => 30],
            ['data' => 'status', 'name' => 'status', 'title' => 'Status', 'orderable' => true, 'searchable' => true, 'width' => 30],
            ['data' => 'reverse_requested_at', 'name' => 'reverse_requested_at', 'title' => 'Requested At', 'orderable' => true, 'searchable' => false, 'width' => 30],
            ['data' => 'remaining_time', 'name' => 'remaining_time', 'title' => 'Remaining Time', 'orderable' => false, 'searchable' => false, 'width' => 30],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'Created at', 'orderable' => true, 'searchable' => false, 'width' => 30],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'Actions', 'orderable' => false, 'searchable' => false, 'width' => '15%'],
        ];
    }

    protected function filename(): string
    {
        return 'Reversals_' . date('YmdHis');
    }
}
