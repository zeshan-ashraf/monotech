<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\Admin\ArchiveDataTable;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\ArcheiveTransaction;
use Carbon\Carbon;

class ArchiveController extends Controller
{
    private $archiveDatatable;
    
    public function __construct()
    {
        $this->middleware(['permission:Archive Transactions']);
        $this->archiveDatatable = new ArchiveDataTable();
    }

    public function list()
    {
        $status = null;
        $assets = ['data-table'];
        $start = request()->start_date;
        $end = request()->end_date;
        $txn_type = request()->txn_type;
        $client = request()->client;
        // dd($client);
        $userRole = auth()->user()->user_role;

        $totalPayinSuccessCount = ArcheiveTransaction::when($userRole !== 'Super Admin', function ($query) {
            $query->where('user_id', auth()->id());
        })
        ->when($client, function ($query) use ($client) {
            $query->where('user_id', $client);
        })
        ->when($txn_type && $txn_type !== 'all', function ($query) use ($txn_type) {
            $query->where('txn_type', $txn_type);
        })
        ->when($start && $end, function ($query) use ($start, $end) {
            $query->whereBetween('created_at', ["$start 00:00:00", "$end 23:59:59"]);
        }, function ($query) { // Fallback when $start and $end are not provided
            $query->whereDate('created_at', Carbon::today()->subDays(1));
        })
        ->where('status', 'success')
        ->count();

        $totalPayinSuccessAmount = ArcheiveTransaction::when($userRole !== 'Super Admin', function ($query) {
            $query->where('user_id', auth()->id());
        })
        ->when($client, function ($query) use ($client) {
            $query->where('user_id', $client);
        })
        ->when($txn_type && $txn_type !== 'all', function ($query) use ($txn_type) {
            $query->where('txn_type', $txn_type);
        })
        ->when($start && $end, function ($query) use ($start, $end) {
            $query->whereBetween('created_at', ["$start 00:00:00", "$end 23:59:59"]);
        }, function ($query) {
            $query->whereDate('created_at', Carbon::today()->subDays(1));
        })
        ->where('status', 'success')
        ->sum('amount');

        $totalPayinFailedCount = ArcheiveTransaction::when($userRole !== 'Super Admin', function ($query) {
            $query->where('user_id', auth()->id());
        })
        ->when($client, function ($query) use ($client) {
            $query->where('user_id', $client);
        })
        ->when($txn_type && $txn_type !== 'all', function ($query) use ($txn_type) {
            $query->where('txn_type', $txn_type);
        })
        ->when($start && $end, function ($query) use ($start, $end) {
            $query->whereBetween('created_at', ["$start 00:00:00", "$end 23:59:59"]);
        }, function ($query) {
            $query->whereDate('created_at', Carbon::today()->subDays(1));
        })
        ->where('status', 'failed')
        ->count();

        $totalPayinTransactionsCount = $totalPayinSuccessCount + $totalPayinFailedCount;

        $payinSuccessRate = $totalPayinTransactionsCount > 0
            ? ($totalPayinSuccessCount / $totalPayinTransactionsCount) * 100
            : 0;
        
        return $this->archiveDatatable->render('admin.archive_transaction.list', get_defined_vars());
    }
}
