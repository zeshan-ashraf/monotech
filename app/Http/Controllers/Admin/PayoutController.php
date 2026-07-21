<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\Admin\PayoutDataTable;
use App\DataTables\Admin\PayoutZigDataTable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Payout,ArcheivePayout};
use App\Models\Setting;
use Carbon\Carbon;

class PayoutController extends Controller
{
    private $payoutDatatable;
    private $payoutZigDatatable;

    public function __construct() 
    {
        $this->middleware(['permission:Payouts'])->except('detail','easyReceipt','jazzReceipt','settle','unsettle');
        $this->payoutDatatable = new PayoutDataTable();
        $this->payoutZigDatatable = new PayoutZigDataTable();
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
    public function zigList()
    {
        $status = null;
        $assets = ['data-table'];
        $start = request()->start_date;
        $end = request()->end_date;
        
        $totalPayoutSuccessCount = Payout::where('user_id', 4)
        ->where('transaction_type', 'jazzcash')
        ->when($start && $end, function ($query) use ($start, $end) {
            $query->whereBetween('created_at', ["$start 00:00:00", "$end 23:59:59"]);
        }, function ($query) { // Fallback when $start and $end are not provided
            $query->whereDate('created_at', Carbon::today());
        })
        ->where('status', 'success')
        ->count();

        $totalPayoutSuccessAmount = Payout::where('user_id', 4)
        ->where('transaction_type', 'jazzcash')
        ->when($start && $end, function ($query) use ($start, $end) {
            $query->whereBetween('created_at', ["$start 00:00:00", "$end 23:59:59"]);
        }, function ($query) { // Fallback when $start and $end are not provided
            $query->whereDate('created_at', Carbon::today());
        })
        ->where('status', 'success')
        ->sum('amount');

        $totalPayoutFailedCount = Payout::where('user_id', 4)
        ->where('transaction_type', 'jazzcash')
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
            
        return $this->payoutZigDatatable->render('admin.payout.zig_list', get_defined_vars());
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

    public function settle(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required',
            'table_name' => 'required|in:payouts,archeive_payouts',
        ]);

        $model = $this->resolvePayoutModel($validated['table_name']);

        $updated = $model::where('orderId', $validated['order_id'])
            ->limit(1)
            ->update([
                'is_settled' => 'yes',
                'settled_date' => now(),
            ]);

        if (! $updated) {
            return response()->json(['success' => false, 'message' => 'Payout not found'], 404);
        }

        return response()->json(['success' => true]);
    }

    public function unsettle(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required',
            'table_name' => 'required|in:payouts,archeive_payouts',
        ]);

        $model = $this->resolvePayoutModel($validated['table_name']);

        $updated = $model::where('orderId', $validated['order_id'])
            ->limit(1)
            ->update([
                'is_settled' => 'no',
                'settled_date' => null,
            ]);

        if (! $updated) {
            return response()->json(['success' => false, 'message' => 'Payout not found'], 404);
        }

        return response()->json(['success' => true]);
    }

    private function resolvePayoutModel(string $tableName): string
    {
        $models = [
            'payouts' => Payout::class,
            'archeive_payouts' => ArcheivePayout::class,
        ];

        return $models[$tableName];
    }
}