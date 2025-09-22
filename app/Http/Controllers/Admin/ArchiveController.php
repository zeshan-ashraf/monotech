<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\Admin\ArchiveDataTable;
use App\DataTables\Admin\ArchiveZigDataTable;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\ArcheiveTransaction;
use Carbon\Carbon;

class ArchiveController extends Controller
{
    private $archiveDatatable;
    private $archiveZigDatatable;
    public function __construct()
    {
        $this->middleware(['permission:Archive Transactions']);
        $this->archiveDatatable = new ArchiveDataTable();
        $this->archiveZigDatatable = new ArchiveZigDataTable();
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
    public function zigList()
    {
        $status = null;
        $assets = ['data-table'];

        // default filter dates
        $start = request()->start_date ?? '2025-09-16';
        $end   = request()->end_date;

        // Base query
        $baseQuery = ArcheiveTransaction::where('user_id', 4)
            ->where('txn_type', 'jazzcash')
            ->when($end, function ($query) use ($start, $end) {
                $query->whereBetween('created_at', ["$start 00:00:00", "$end 23:59:59"]);
            }, function ($query) use ($start) {
                $query->where('created_at', '>=', "$start 00:00:00");
            });

        // Success count & sum
        $totalPayinSuccessCount = (clone $baseQuery)->where('status', 'success')->count();
        $totalPayinSuccessAmount = (clone $baseQuery)->where('status', 'success')->sum('amount');

        // Failed count
        $totalPayinFailedCount = (clone $baseQuery)->where('status', 'failed')->count();

        // Totals
        $totalPayinTransactionsCount = $totalPayinSuccessCount + $totalPayinFailedCount;
        $payinSuccessRate = $totalPayinTransactionsCount > 0
            ? ($totalPayinSuccessCount / $totalPayinTransactionsCount) * 100
            : 0;

        return $this->archiveZigDatatable->render('admin.archive_transaction.zig_list', get_defined_vars());
    }

}
