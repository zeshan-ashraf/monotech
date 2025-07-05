<?php

namespace App\DataTables\Admin;

use App\Models\Payout;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class PayoutDataTable extends DataTable
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
                $reason = $query->message;
                $type = $query->status;
               return view('admin.transaction.badge',get_defined_vars());
            })
            ->editColumn('created_at', function ($query) {
                return $query->created_at ? $query->created_at->format('d-m-y H:i:s') : 'N/A';
            })
            ->editColumn('detail', function ($query) {
                // Check the payment method
                $buttons = '';
            
                if ($query->transaction_type === 'jazzcash' && $query->status === 'success') {
                    $buttons .= '
                        <a href="' . route('admin.payout.detail', $query->id) . '" class="btn btn-primary btn-sm">Detail</a>
                        <a href="' . route('admin.payout.jazz_receipt', $query->id) . '" class="btn btn-danger btn-sm mt-1">Receipt</a>
                    ';
                }
                elseif ($query->transaction_type != 'jazzcash' && $query->status === 'success') {
                    $buttons .= '
                        <a href="' . route('admin.payout.detail', $query->id) . '" class="btn btn-primary btn-sm">Detail</a>
                        <a href="' . route('admin.payout.easy_receipt', $query->id) . '" class="btn btn-success btn-sm mt-1">Receipt</a>
                    ';
                }
                else {
                    $buttons .= '
                        <a href="' . route('admin.payout.detail', $query->id) . '" class="btn btn-primary btn-sm">Detail</a>
                    ';
                }
            
                return $buttons;
            })->rawColumns(['detail']);
    }

     public function query()
    {
        $userRole = auth()->user()->user_role;
        $model = Payout::when($userRole !== 'Super Admin', function ($query) {
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
            $q->where('transaction_type', request()->txn_type);
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
                "responsive" => true, // Enable responsiveness
                "pageLength" => 50,
                'drawCallback' => "function () {}"
            ]);
    }

    protected function getColumns()
    {
        return [

            ['data' => 'DT_RowIndex', 'name' => 'iteration', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '5%'],
            // ['data' => 'user_id', 'name' => 'user_id', 'title' => 'Client', 'orderable' => true, 'searchable' => true, 'width' => '15%'],
            ['data' => 'orderId', 'name' => 'orderId', 'title' => 'OrderId', 'orderable' => true, 'searchable' => true, 'width' => '15%'],
            ['data' => 'transaction_reference', 'name' => 'transaction_reference', 'title' => 'Transaction Id', 'orderable' => true, 'searchable' => true, 'width' => '20%'],
            // ['data' => 'transaction_id', 'name' => 'transaction_id', 'title' => 'Transaction Id', 'orderable' => true, 'searchable' => true, 'width' => '20%'],
            ['data' => 'amount', 'name' => 'amount', 'title' => 'Amount', 'orderable' => true, 'searchable' => true, 'width' => '15%'],
            ['data' => 'phone', 'name' => 'phone', 'title' => 'Phone', 'orderable' => true, 'searchable' => true, 'width' => '15%'],
            ['data' => 'transaction_type', 'name' => 'transaction_type', 'title' => 'Trans Type', 'orderable' => true, 'searchable' => true, 'width' => '15%'],
            // ['data' => 'message', 'name' => 'message', 'title' => 'Message', 'orderable' => true, 'searchable' => true, 'width' => '15%'],
            ['data' => 'status', 'name' => 'status', 'title' => 'Status', 'orderable' => true, 'searchable' => true, 'width' => '10%'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'Created At', 'orderable' => true, 'searchable' => true, 'width' => '25%'],
            ['data' => 'detail', 'name' => 'detail', 'title' => 'Action', 'width' => '15%', 'orderable' => false, 'searchable' => false,],

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
