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
        // $query is already a collection from getPendingReversals()
        return datatables()
            ->collection($query)
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
                if (!$query->reverse_requested_at) {
                    return 'N/A';
                }
                // Handle both Carbon instance and string
                $date = $query->reverse_requested_at instanceof Carbon ? $query->reverse_requested_at : Carbon::parse($query->reverse_requested_at);
                return $date->format('d-m-y H:i:s');
            })
            ->addColumn('remaining_time', function ($query) {
                if (!$query->reverse_requested_at) {
                    return 'N/A';
                }
                // Handle both Carbon instance and string
                $reverseRequestedAt = $query->reverse_requested_at instanceof Carbon ? $query->reverse_requested_at : Carbon::parse($query->reverse_requested_at);
                $remaining = $this->reversalService->getRemainingTime($reverseRequestedAt);
                $deadline = $reverseRequestedAt->copy()->addHours(6);
                $now = now();
                
                // Add data attribute for JavaScript countdown
                $isExpired = $now >= $deadline;
                $dataDeadline = $deadline->timestamp;
                
                return '<span class="countdown-timer" data-deadline="' . $dataDeadline . '" data-reverse-requested="' . $reverseRequestedAt->timestamp . '">' . $remaining . '</span>';
            })
            ->editColumn('amount', function ($query) {
                return number_format($query->amount, 2) . ' PKR';
            })
            ->addColumn('actions', function ($query) {
                $tableType = $query->table_type ?? 'transactions';
                $buttons = '';
                
                // Cancel Reversal button (only if not expired)
                if ($query->reverse_requested_at) {
                    $reverseRequestedAt = $query->reverse_requested_at instanceof Carbon ? $query->reverse_requested_at : Carbon::parse($query->reverse_requested_at);
                    $deadline = $reverseRequestedAt->copy()->addHours(6);
                    if (now() < $deadline) {
                        $buttons .= '<button class="btn btn-warning btn-sm cancel-reversal-btn" data-id="' . $query->id . '" data-table-type="' . $tableType . '">Cancel</button> ';
                    }
                }
                
                // Reverse Now button
                $buttons .= '<button class="btn btn-danger btn-sm reverse-now-btn" data-id="' . $query->id . '" data-table-type="' . $tableType . '">Reverse Now</button>';
                
                return $buttons;
            })
            ->rawColumns(['checkbox', 'remaining_time', 'actions']);
    }

    public function query()
    {
        // Get pending reversals using the service method which returns a collection
        $pending = $this->reversalService->getPendingReversals();
        
        // Return the collection directly (will be used with ->collection() in dataTable)
        return $pending;
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
                'order' => [[10, 'desc']], // Order by reverse_requested_at descending (column index 10)
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
