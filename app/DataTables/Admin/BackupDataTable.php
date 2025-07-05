<?php

namespace App\DataTables\Admin;

use App\Models\BackupTransaction;
use App\Models\User;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;

class BackupDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addIndexColumn()
            ->editColumn('user_id', function ($query) {
                return $query->user ? $query->user->name : 'N/A';
            })
            ->editColumn('status',function ($query){
                $reason = $query->pp_message;
                $type = $query->status;
               return view('admin.archive_transaction.badge',get_defined_vars());
            })
            ->editColumn('created_at', function ($query) {
                return $query->created_at ? $query->created_at->format('d-m-y H:i:s') : 'N/A';
            })->editColumn('status-inqury', function ($query) {
                return '<a href="' . route('admin.jazzcash.status-inquiry', ['id' => $query->txn_ref_no, 'type' => $query->txn_type]) . '" class="btn btn-primary btn-sm">Inquiry</a>';
            })->rawColumns(['status-inqury']);
    }

    public function query()
    {
        $userRole = auth()->user()->user_role;
        $model=BackupTransaction::when($userRole !== 'Super Admin' && $userRole !== 'Manager', function ($query) {
            $query->where('user_id', auth()->id());
        })
        ->when(auth()->user()->user_role == 'Client' , function($q){
            $q->where('user_id',auth()->user()->id);
        })
        ->when(request()->status, function($q) {
            $q->where('status', request()->status);
        })
        ->when(request()->client, function($q) {
            $q->where('user_id', request()->client);
        })
        ->when(request()->txn_type, function($q) {
            $q->where('txn_type', request()->txn_type);
        })
        ->when(request()->start_date && request()->end_date, function ($query) {
            $start = Carbon::parse(request()->start_date);
            $end = Carbon::parse(request()->end_date);
            $query->whereDate('created_at', '>=' ,$start);
            $query->whereDate('created_at', '<=', $end);
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
            ['data' => 'transactionId', 'name' => 'transactionId', 'title' => 'Trans Id', 'orderable' => true,'searchable' => true,'width'=>30],
            ['data' => 'txn_ref_no', 'name' => 'txn_ref_no', 'title' => 'Trans Ref No', 'orderable' => true,'searchable' => true,'width'=>30],
            ['data' => 'txn_type', 'name' => 'txn_type', 'title' => 'Trans type', 'orderable' => true,'searchable' => true,'width'=>30],
            ['data' => 'amount', 'name' => 'amount', 'title' => 'Amount (PKR)', 'orderable' => true,'searchable' => true,'width'=>30, ],
            ['data' => 'status', 'name' => 'status', 'title' => 'Status', 'orderable' => true,'searchable' => true,'width'=>30],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'Created at', 'orderable' => true,'searchable' => true,'width'=>30],
            ['data' => 'status-inqury', 'name' => 'status-inqury', 'title' => 'Inquiry', 'orderable' => true,'searchable' => false,'width'=>30],

        ];
    }

      /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'Export_' . date('YmdHis');
    }

   /**
    * Get filename for export.
    *
    * @return string
    */
    protected function sheetName() : string
    {
        return "Yearly Report";
    }

    // public function excel()
    // {
    //     // TODO: Implement excel() method.
    // }

    // public function csv()
    // {
    //     // TODO: Implement csv() method.
    // }

    // public function pdf()
    // {
    //     // TODO: Implement pdf() method.
    // }
}
