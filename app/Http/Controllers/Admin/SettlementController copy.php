<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Transaction,Payout,Settlement};
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SettlementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:Settlement']);
    }
    
    public function okList()
    {
        $user = auth()->user();
        $results = Settlement::where('user_id', '2')
            ->orderBy('date', 'DESC')
            ->get();
        
        foreach ($results as $summary) {
            $date = $summary->date; // Use the date as is
            $transactionCount = Transaction::where('user_id', '2')
                ->whereDate('created_at', $date)
                ->whereIn('status', ['success', 'failed'])
                ->count();
            $summary->transaction_count = $transactionCount;
        }
        return view('admin.settlement.list', get_defined_vars());
    }
    
    public function piqList()
    {
        $user = auth()->user();
        $results = Settlement::where('user_id', '4')
            ->orderBy('date', 'DESC')
            ->get();
        
        foreach ($results as $summary) {
            $date = $summary->date; // Use the date as is
            $transactionCount = Transaction::where('user_id', '4')
                ->whereDate('created_at', $date)
                ->whereIn('status', ['success', 'failed'])
                ->count();
            $summary->transaction_count = $transactionCount;
        }
        return view('admin.settlement.list', get_defined_vars());
    }
    
    public function cspkrList()
    {
        $user = auth()->user();
        $results = Settlement::where('user_id', '9')
            ->orderBy('date', 'DESC')
            ->get();
        
        foreach ($results as $summary) {
            $date = $summary->date; // Use the date as is
            $transactionCount = Transaction::where('user_id', '9')
                ->whereDate('created_at', $date)
                ->whereIn('status', ['success', 'failed'])
                ->count();
            $summary->transaction_count = $transactionCount;
        }
        return view('admin.settlement.list', get_defined_vars());
    }
    
    public function toppayList()
    {
        $user = auth()->user();
        $results = Settlement::where('user_id', '10')
            ->orderBy('date', 'DESC')
            ->get();
        
        foreach ($results as $summary) {
            $date = $summary->date; // Use the date as is
            $transactionCount = Transaction::where('user_id', '10')
                ->whereDate('created_at', $date)
                ->whereIn('status', ['success', 'failed'])
                ->count();
            $summary->transaction_count = $transactionCount;
        }
        return view('admin.settlement.list', get_defined_vars());
    }
    
    public function pknList()
    {
        $user = auth()->user();
        $results = Settlement::where('user_id', '5')
            ->orderBy('date', 'DESC')
            ->get();
        
        foreach ($results as $summary) {
            $date = $summary->date; // Use the date as is
            $transactionCount = Transaction::where('user_id', '5')
                ->whereDate('created_at', $date)
                ->whereIn('status', ['success', 'failed'])
                ->count();
            $summary->transaction_count = $transactionCount;
        }
        return view('admin.settlement.list', get_defined_vars());
    }
    public function corepayList()
    {
        $user = auth()->user();
        $results = Settlement::where('user_id', '12')
            ->orderBy('date', 'DESC')
            ->get();
        
        foreach ($results as $summary) {
            $date = $summary->date; // Use the date as is
            $transactionCount = Transaction::where('user_id', '12')
                ->whereDate('created_at', $date)
                ->whereIn('status', ['success', 'failed'])
                ->count();
            $summary->transaction_count = $transactionCount;
        }
        return view('admin.settlement.list', get_defined_vars());
    }
    
    public function genxpayList()
    {
        $user = auth()->user();
        $results = Settlement::where('user_id', '13')
            ->orderBy('date', 'DESC')
            ->get();
        
        foreach ($results as $summary) {
            $date = $summary->date; // Use the date as is
            $transactionCount = Transaction::where('user_id', '13')
                ->whereDate('created_at', $date)
                ->whereIn('status', ['success', 'failed'])
                ->count();
            $summary->transaction_count = $transactionCount;
        }
        return view('admin.settlement.list', get_defined_vars());
    }
    
    public function moneypayList()
    {
        $user = auth()->user();
        $results = Settlement::where('user_id', '14')
            ->orderBy('date', 'DESC')
            ->get();
        
        foreach ($results as $summary) {
            $date = $summary->date; // Use the date as is
            $transactionCount = Transaction::where('user_id', '14')
                ->whereDate('created_at', $date)
                ->whereIn('status', ['success', 'failed'])
                ->count();
            $summary->transaction_count = $transactionCount;
        }
        return view('admin.settlement.list', get_defined_vars());
    }
    public function zigList()
    {
        $user = auth()->user();
        $results = Settlement::where('user_id', 4)
            ->whereDate('date', '>=', '2025-09-16')
            ->orderBy('date', 'DESC')
            ->get();
        
        foreach ($results as $summary) {
            $date = $summary->date; // Use the date as is
            $transactionCount = Transaction::where('user_id', '4')
                ->whereDate('created_at', $date)
                ->whereIn('status', ['success', 'failed'])
                ->count();
            $summary->transaction_count = $transactionCount;
        }
        return view('admin.settlement.zig_list', get_defined_vars());
    }
    public function modal(Request $request)
    {
        $id = $request->id;
        $item = DB::table('settlements')->where('id',$id)->first();
        $html = view('admin.settlement.modal',get_defined_vars())->render();
        return response()->json(['html'=>$html]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'usdt'=>'required',
        ]);
        $item = Settlement::findOrFail($request->id);
        $totalUsdt = $item->usdt+$request->usdt;
        $item->usdt = $totalUsdt;
        $item->settled = $item->settled+$totalUsdt;
        $item->save();
        $msg = "Summary Updated Successfully!";
        return redirect()->back()->with('message',$msg);
    }
}