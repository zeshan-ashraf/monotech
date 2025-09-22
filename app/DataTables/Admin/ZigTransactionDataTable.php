<?php

namespace App\DataTables\Admin;

use App\Models\Transaction;
use App\Models\User;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;

class ZigTransactionDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addIndexColumn()
            ->editColumn('status',function ($query){
                $reason = $query->pp_message;
                $type = $query->status;
               return view('admin.transaction.badge',get_defined_vars());
            })
            ->editColumn('created_at', function ($query) {
                return $query->created_at ? $query->created_at->format('d-m-y H:i:s') : 'N/A';
            })
            ->editColumn('status-inqury', function ($query) {
                $inquiryButton = '
                    <a href="' . route('admin.jazzcash.status-inquiry', ['id' => $query->txn_ref_no, 'type' => $query->txn_type]) . '" 
                    class="btn btn-primary btn-sm">Inquiry</a>
                ';
            
                // Show dropdown only if status is 'pending'
                if ($query->status == 'pending') {
                    $dropdown = '
                        <select class="form-control status-dropdown mt-1" data-id="' . $query->id . '">
                            <option value="pending" selected>Pending</option>
                            <option value="success">Success</option>
                            <option value="failed">Failed</option>
                        </select>
                    ';
                    return $inquiryButton . $dropdown;
                }
            
                return $inquiryButton;
            })
            ->editColumn('amount',function ($query){
                return $query->amount;
             }) ->rawColumns(['status-inqury']);
    }

    public function query()
    {
        $model = Transaction::where('user_id', 4)
            ->where('txn_type', 'jazzcash')
            ->when(request()->status && request()->status !== 'all', function($q) {
                $q->where('status', request()->status);
            })
            ->when(request()->start_date && request()->end_date, function($q) {
                $q->whereDate('created_at', '>=', request()->start_date)
                ->whereDate('created_at', '<=', request()->end_date);
            });

        $model->orderBy('created_at', 'desc');

        return $this->applyScopes($model);
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('dataTable')
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
                'drawCallback' => "function () {
                        }",
            ]);
    }
    protected function getColumns()
    {
        return [
            ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => 10],
            ['data' => 'orderId', 'name' => 'orderId', 'title' => 'Merchant Id', 'orderable' => true,'searchable' => true,'width'=>30],
            ['data' => 'phone', 'name' => 'phone', 'title' => 'Phone', 'orderable' => true,'searchable' => true,'width'=>30],
            ['data' => 'transactionId', 'name' => 'transactionId', 'title' => 'Trans Id', 'orderable' => true,'searchable' => true,'width'=>30],
            ['data' => 'txn_ref_no', 'name' => 'txn_ref_no', 'title' => 'Trans Ref No', 'orderable' => true,'searchable' => true,'width'=>30],
            ['data' => 'txn_type', 'name' => 'txn_type', 'title' => 'Trans type', 'orderable' => true,'searchable' => true,'width'=>30],
            ['data' => 'amount', 'name' => 'amount', 'title' => 'Amount (PKR)', 'orderable' => true,'searchable' => true,'width'=>30, ],
            ['data' => 'status', 'name' => 'status', 'title' => 'Status', 'orderable' => true,'searchable' => true,'width'=>30],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'Created at', 'orderable' => true,'searchable' => true,'width'=>30,],
            ['data' => 'status-inqury', 'name' => 'status-inqury', 'title' => 'Inquiry', 'orderable' => true,'searchable' => false,'width'=>30],

        ];
    }

    protected function filename(): string
    {
        return 'Export_' . date('YmdHis');
    }

    protected function sheetName() : string
    {
        return "Yearly Report";
    }
}