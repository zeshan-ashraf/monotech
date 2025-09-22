<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\Admin\ArchivePayoutDataTable;
use App\DataTables\Admin\ArchivePayoutZigDataTable;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\ArcheivePayout;
use Carbon\Carbon;

class ArchivePayoutController extends Controller
{
    private $archivePayoutDatatable;
    private $archivePayoutZigDatatable;
    public function __construct()
    {
        $this->middleware(['permission:Archive Transactions']);
        $this->archivePayoutDatatable = new ArchivePayoutDataTable();
        $this->archivePayoutZigDatatable = new ArchivePayoutZigDataTable();
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

        $totalPayinSuccessCount = ArcheivePayout::when($userRole !== 'Super Admin', function ($query) {
            $query->where('user_id', auth()->id());
        })
        ->when($client, function ($query) use ($client) {
            $query->where('user_id', $client);
        })
        ->when($txn_type && $txn_type !== 'all', function ($query) use ($txn_type) {
            $query->where('transaction_type', $txn_type);
        })
        ->when($start && $end, function ($query) use ($start, $end) {
            $query->whereBetween('created_at', ["$start 00:00:00", "$end 23:59:59"]);
        }, function ($query) { // Fallback when $start and $end are not provided
            $query->whereDate('created_at', Carbon::today()->subDays(1));
        })
        ->where('status', 'success')
        ->count();

        $totalPayinSuccessAmount = ArcheivePayout::when($userRole !== 'Super Admin', function ($query) {
            $query->where('user_id', auth()->id());
        })
        ->when($client, function ($query) use ($client) {
            $query->where('user_id', $client);
        })
        ->when($txn_type && $txn_type !== 'all', function ($query) use ($txn_type) {
            $query->where('transaction_type', $txn_type);
        })
        ->when($start && $end, function ($query) use ($start, $end) {
            $query->whereBetween('created_at', ["$start 00:00:00", "$end 23:59:59"]);
        }, function ($query) {
            $query->whereDate('created_at', Carbon::today()->subDays(1));
        })
        ->where('status', 'success')
        ->sum('amount');

        $totalPayinFailedCount = ArcheivePayout::when($userRole !== 'Super Admin', function ($query) {
            $query->where('user_id', auth()->id());
        })
        ->when($client, function ($query) use ($client) {
            $query->where('user_id', $client);
        })
        ->when($txn_type && $txn_type !== 'all', function ($query) use ($txn_type) {
            $query->where('transaction_type', $txn_type);
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
        
        return $this->archivePayoutDatatable->render('admin.archive_transaction.payout_list', get_defined_vars());
    }
    public function zigList()
    {
        $status = null;
        $assets = ['data-table'];

        // Default start date is 16-09-2025
        $start = request()->start_date ?? '2025-09-16';
        $end   = request()->end_date ?? Carbon::now()->toDateString();

        // Base query
        $baseQuery = ArcheivePayout::where('user_id', 4)
            ->where('transaction_type', 'jazzcash')
            ->when($start && $end, function ($query) use ($start, $end) {
                $query->whereBetween('created_at', ["$start 00:00:00", "$end 23:59:59"]);
            });

        // Success count
        $totalPayinSuccessCount = (clone $baseQuery)
            ->where('status', 'success')
            ->count();

        // Success amount
        $totalPayinSuccessAmount = (clone $baseQuery)
            ->where('status', 'success')
            ->sum('amount');

        // Failed count
        $totalPayinFailedCount = (clone $baseQuery)
            ->where('status', 'failed')
            ->count();

        // Total transactions
        $totalPayinTransactionsCount = $totalPayinSuccessCount + $totalPayinFailedCount;

        // Success rate %
        $payinSuccessRate = $totalPayinTransactionsCount > 0
            ? ($totalPayinSuccessCount / $totalPayinTransactionsCount) * 100
            : 0;

        return $this->archivePayoutZigDatatable->render(
            'admin.archive_transaction.payout_zig_list',
            get_defined_vars()
        );
    }

}
