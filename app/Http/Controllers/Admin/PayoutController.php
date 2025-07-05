<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\Admin\PayoutDataTable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Payout,ArcheivePayout};
use App\Models\Setting;
use Carbon\Carbon;

class PayoutController extends Controller
{
    private $payoutDatatable;

    public function __construct() 
    {
        $this->middleware(['permission:Payouts'])->except('detail','easyReceipt','jazzReceipt');
        $this->payoutDatatable = new PayoutDataTable();
    }

    public function list()
    {
        $status = null;
        $assets = ['data-table'];
        $start = request()->start_date;
        $end = request()->end_date;
        $txn_type = request()->txn_type;
        $client = request()->client;
        $userRole = auth()->user()->user_role;
        
        $totalPayoutSuccessCount = Payout::when($userRole !== 'Super Admin', function ($query) {
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
            $query->whereDate('created_at', Carbon::today());
        })
        ->where('status', 'success')
        ->count();

        $totalPayoutSuccessAmount = Payout::when($userRole !== 'Super Admin', function ($query) {
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
            $query->whereDate('created_at', Carbon::today());
        })
        ->where('status', 'success')
        ->sum('amount');

        $totalPayoutFailedCount = Payout::when($userRole !== 'Super Admin', function ($query) {
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
            $query->whereDate('created_at', Carbon::today());
        })
        ->where('status', 'failed')
        ->count();
        $totalPayoutTransactionsCount = $totalPayoutSuccessCount + $totalPayoutFailedCount;

        $payoutSuccessRate = $totalPayoutTransactionsCount > 0
            ? ($totalPayoutSuccessCount / $totalPayoutTransactionsCount) * 100
            : 0;
            
        return $this->payoutDatatable->render('admin.payout.list', get_defined_vars());
    }
    public function detail($id)
    {
        $item=Payout::find($id);
        if (!$item) {
            $item = ArcheivePayout::find($id);
        }
        return view('admin.payout.detail',get_defined_vars());
    }
    public function easyReceipt($id)
    {
        $item=Payout::find($id);
        if (!$item) {
            $item = ArcheivePayout::find($id);
        }
        return view('admin.receipt.easypaisa',get_defined_vars());
    }
    public function jazzReceipt($id)
    {
        $item=Payout::find($id);
        if (!$item) {
            $item = ArcheivePayout::find($id);
        }
        return view('admin.receipt.jazzcash',get_defined_vars());
    }
}