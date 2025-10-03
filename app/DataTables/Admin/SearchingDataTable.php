<?php

namespace App\DataTables\Admin;

use App\Models\{User,Transaction,ArcheiveTransaction,BackupTransaction};
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class SearchingDataTable extends DataTable
{

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->editColumn('status',function ($query){
                $reason = $query->pp_message;
                $type = $query->status;
               return view('admin.transaction.badge',get_defined_vars());
            })
            ->editColumn('created_at',function ($query){
               return $query->created_at ? $query->created_at->format('d-m-y H:i:s') : 'N/A';
            })
            ->editColumn('amount',function ($query){
                return $query->amount.' PKR';
             })
            ->editColumn('detail', function ($query) {
                $buttons = '';
                return $buttons .= '
                <a href="' . route('admin.searching.callback.send', $query->id) . '" class="btn btn-success btn-sm">Send Callback</a>
                <a href="' . route('admin.jazzcash.status-inquiry', ['id' => $query->txn_ref_no, 'type' => $query->txn_type]) . '" class="btn btn-primary btn-sm mt-1">Inquiry</a>
                ';
                return $buttons;
            })->rawColumns(['detail'])
             ->editColumn('reverse', function ($query) {
                 $user = auth()->user(); // Get the logged-in user

                if ($user->user_role == "Super Admin" && $query->status == 'success') {
                    return '
                        <select class="form-control status-dropdown-reverse mt-1" data-id="' . $query->id . '">
                            <option value="" selected disabled>Select Option..</option>
                            <option value="reverse">Reverse</option>
                        </select>
                    ';
                }
                return ''; // Return empty if conditions are not met
            })->rawColumns(['detail', 'reverse']);
    }

    public function query()
    {
        $transactionQuery = Transaction::query()
            ->when(request()->transaction_Id, function ($q) {
                $q->where('transactionId', 'like', '%' . request()->transaction_Id . '%');
            })
            ->when(request()->phone, function ($q) {
                $q->where('phone', 'like', '%' . request()->phone . '%');
            })
            ->when(request()->client, function ($q) {
                $q->where('user_id', request()->client);
            })
            ->when(request()->order_id, function ($q) {
                $q->where('orderId', 'like', '%' . request()->order_id . '%');
            });
    
        $archiveTransactionQuery = ArcheiveTransaction::query()
            ->when(request()->transaction_Id, function ($q) {
                $q->where('transactionId', 'like', '%' . request()->transaction_Id . '%');
            })
            ->when(request()->phone, function ($q) {
                $q->where('phone', 'like', '%' . request()->phone . '%');
            })
            ->when(request()->client, function ($q) {
                $q->where('user_id', request()->client);
            })
            ->when(request()->order_id, function ($q) {
                $q->where('orderId', 'like', '%' . request()->order_id . '%');
            });
        $backupTransactionQuery = BackupTransaction::query()
            ->when(request()->transaction_Id, function ($q) {
                $q->where('transactionId', 'like', '%' . request()->transaction_Id . '%');
            })
            ->when(request()->phone, function ($q) {
                $q->where('phone', 'like', '%' . request()->phone . '%');
            })
            ->when(request()->client, function ($q) {
                $q->where('user_id', request()->client);
            })
            ->when(request()->order_id, function ($q) {
                $q->where('orderId', 'like', '%' . request()->order_id . '%');
            });
    
        $combinedQuery = $transactionQuery
        ->union($archiveTransactionQuery)
        ->union($backupTransactionQuery);
        return $this->applyScopes($combinedQuery);
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('dataTable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('<"row align-items-center"<"col-md-2" l><"col-md-6" B><"col-md-4"f>><"table-responsive my-3" rt><"row align-items-center" <"col-md-6" i><"col-md-6" p>><"clear">')
            ->parameters([
                "buttons" => [
                    'excel',
                ],
                "processing" => true,
                "autoWidth" => false,
                'lengthChange' => false, // Disable "Show Items" dropdown
                'searching' => false,    // Disable search box
                'drawCallback' => "function () {
                        }"
            ]);
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            ['data' => 'orderId', 'name' => 'orderId', 'title' => 'Order Id', 'orderable' => true,'searchable' => true,'width'=>30],
            ['data' => 'transactionId', 'name' => 'transactionId', 'title' => 'Trans Id', 'orderable' => true,'searchable' => true,'width'=>30],
            ['data' => 'phone', 'name' => 'phone', 'title' => 'Phone', 'orderable' => true, 'searchable' => true, 'width'=>30],
            ['data' => 'txn_ref_no', 'name' => 'txn_ref_no', 'title' => 'Trans Ref No', 'orderable' => true,'searchable' => true,'width'=>30],
            ['data' => 'txn_type', 'name' => 'txn_type', 'title' => 'Trans type', 'orderable' => true,'searchable' => true,'width'=>30],
            ['data' => 'amount', 'name' => 'amount', 'title' => 'Amount', 'orderable' => true,'searchable' => true,'width'=>30],
            ['data' => 'status', 'name' => 'status', 'title' => 'Status', 'orderable' => true,'searchable' => true,'width'=>30],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'Created at', 'orderable' => true,'searchable' => true,'width'=>30],
            ['data' => 'detail', 'name' => 'detail', 'title' => 'Action', 'orderable' => false, 'searchable' => false, 'width' => '15%'],
            ['data' => 'reverse', 'name' => 'reverse', 'title' => 'Change Status', 'orderable' => false, 'searchable' => false, 'width' => '15%'],
            
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
