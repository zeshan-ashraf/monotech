<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{User, Summary, Setting,Settlement, ScheduleSetting,SurplusAmount,Transaction,ManualPayout};
use Illuminate\Http\Request;
use DB;

class SettingController extends Controller
{
    public function list()
    {
        $start = request()->start_date;
        $end = request()->end_date;
        $txn_type = request()->txn_type;
        $client = request()->client;

        $query1 = DB::table('transactions')
            ->select('*')
            ->where('status', 'reverse')
            ->when($txn_type && $txn_type !== 'all', fn($q) => $q->where('txn_type', $txn_type))
            ->when($client && $client !== 'all', fn($q) => $q->where('user_id', $client))
            ->when($start && $end, fn($q) => $q->whereBetween('updated_at', ["$start 00:00:00", "$end 23:59:59"]));

        $query2 = DB::table('archeive_transactions')
            ->select('*')
            ->where('status', 'reverse')
            ->when($txn_type && $txn_type !== 'all', fn($q) => $q->where('txn_type', $txn_type))
            ->when($client && $client !== 'all', fn($q) => $q->where('user_id', $client))
            ->when($start && $end, fn($q) => $q->whereBetween('updated_at', ["$start 00:00:00", "$end 23:59:59"]));

        $query3 = DB::table('backup_transactions')
            ->select('*')
            ->where('status', 'reverse')
            ->when($txn_type && $txn_type !== 'all', fn($q) => $q->where('txn_type', $txn_type))
            ->when($client && $client !== 'all', fn($q) => $q->where('user_id', $client))
            ->when($start && $end, fn($q) => $q->whereBetween('updated_at', ["$start 00:00:00", "$end 23:59:59"]));

        // Combine queries using union
        $unioned = $query1->unionAll($query2)->unionAll($query3);

        // Use fromSub to treat the union as a subquery
        $finalQuery = DB::query()
            ->fromSub($unioned, 'combined_transactions');

        // Get the full list
        $list = $finalQuery
            ->orderByDesc('updated_at')
            ->get();

        // Get reverse count and total amount
        $summary = DB::query()
            ->fromSub($unioned, 'combined_transactions')
            ->selectRaw('COUNT(*) as reverse_count, SUM(amount) as total_reverse_amount')
            ->first();

        // Get all active users for the dropdown (only for Super Admin and Admin)
        $users = collect();
        if (auth()->user()->user_role == "Super Admin" || auth()->user()->user_role == "Admin") {
            $users = User::where('user_role', 'Client')
                ->where('active', '1')
                ->orderBy('name')
                ->get();
        }

        return view("admin.setting.list", [
            'list' => $list,
            'reverse_count' => $summary->reverse_count,
            'total_reverse_amount' => $summary->total_reverse_amount,
            'users' => $users,
        ]);
    }

   // public function okList()
    public function reversedPayinList(){
        $start = request()->start_date;
        $end = request()->end_date;
        $txn_type = request()->txn_type;
        $currentUserId = auth()->user()->id;

        $query1 = DB::table('transactions')
            ->select('*')
            ->where('status', 'reverse')
            ->where('user_id', $currentUserId)
            ->when($txn_type && $txn_type !== 'all', fn($q) => $q->where('txn_type', $txn_type))
            ->when($start && $end, fn($q) => $q->whereBetween('updated_at', ["$start 00:00:00", "$end 23:59:59"]));

        $query2 = DB::table('archeive_transactions')
            ->select('*')
            ->where('status', 'reverse')
            ->where('user_id', $currentUserId)
            ->when($txn_type && $txn_type !== 'all', fn($q) => $q->where('txn_type', $txn_type))
            ->when($start && $end, fn($q) => $q->whereBetween('updated_at', ["$start 00:00:00", "$end 23:59:59"]));

        $query3 = DB::table('backup_transactions')
            ->select('*')
            ->where('status', 'reverse')
            ->where('user_id', $currentUserId)
            ->when($txn_type && $txn_type !== 'all', fn($q) => $q->where('txn_type', $txn_type))
            ->when($start && $end, fn($q) => $q->whereBetween('updated_at', ["$start 00:00:00", "$end 23:59:59"]));

        // Combine queries using union
        $unioned = $query1->unionAll($query2)->unionAll($query3);

        // Use fromSub to treat the union as a subquery
        $finalQuery = DB::query()
            ->fromSub($unioned, 'combined_transactions');

        // Get the full list
        $list = $finalQuery
            ->orderByDesc('updated_at')
            ->get();

        // Get reverse count and total amount
        $summary = DB::query()
            ->fromSub($unioned, 'combined_transactions')
            ->selectRaw('COUNT(*) as reverse_count, SUM(amount) as total_reverse_amount')
            ->first();

        return view("admin.setting.list", [
            'list' => $list,
            'reverse_count' => $summary->reverse_count,
            'total_reverse_amount' => $summary->total_reverse_amount,
        ]);
    }
    public function modal(Request $request)
    {
        $id = $request->id;
        $item1 = Setting::where('id',$id)->first();
        $user=User::find($id);
        $html = view('admin.setting.modal',get_defined_vars())->render();
        return response()->json(['html'=>$html]);
    }
    public function modalSec()
    {
        $html = view('admin.setting.modal_sec')->render();
        return response()->json(['html'=>$html]);
    }
    public function modalThird(Request $request)
    {
        $id = $request->id;
        $setting = Setting::where('user_id',$id)->first();
        $user=User::find($id);
        $html = view('admin.setting.modal_third',get_defined_vars())->render();
        return response()->json(['html'=>$html]);
    }
    public function saveSetting(Request $request)
    {
        $userid = auth()->user()->id;
        if(auth()->user()->id == 18){// copay
            $settlement=Settlement::where('user_id',$userid)->whereDate('date', today())->first();
            $prevBal=Settlement::where('user_id', $userid)->whereDate('date', today()->subDay())->value('closing_bal') ?? 0;
            $client=User::find($userid);
            $epPayinAmount = $settlement->ep_payin;
            $jcPayinAmount = $settlement->jc_payin;
            $epPayoutAmount = $settlement->ep_payout;
            $jcPayoutAmount = $settlement->jc_payout;
            $payinSuccess= $epPayinAmount + $jcPayinAmount;
            $payoutSuccess= $epPayoutAmount + $jcPayoutAmount;
            $prevUsdt= $settlement->usdt;
            $payinFee=$client->payin_fee;
            $payoutFee=$client->payout_fee;
            $unsettletdAmount=$prevBal + $payinSuccess - ($payinSuccess*$payinFee + $payoutSuccess + $payoutSuccess*$payoutFee + $prevUsdt);

            //$setting=Setting::where('user_id',$request->id)->first();// can be manipulated
            $setting=Setting::where('user_id',$userid)->first();
            $setting->easypaisa += $request->easypaisa;
            $setting->jazzcash += $request->jazzcash;
            $newAmount=$setting->easypaisa + $setting->jazzcash;
            if ($newAmount > $unsettletdAmount) {
                return redirect()->back()->with('error', 'Assigned amount cannot be greater than unsettled amount.');
            }
            else{
                $setting->payout_balance = $setting->easypaisa+$setting->jazzcash;
                $setting->save();
                
                $surplus=SurplusAmount::where('id','1')->first();
                $surplus->jazzcash -= $request->jazzcash;
                $surplus->easypaisa -= $request->easypaisa;
                $surplus->save();
            }
        } else {
            $setting=Setting::where('user_id',$request->id)->first();
            $setting->easypaisa += $request->easypaisa;
            $setting->jazzcash += $request->jazzcash;
            $setting->payout_balance = $setting->easypaisa+$setting->jazzcash;
            $setting->save();
            
            $surplus=SurplusAmount::where('id','1')->first();
            $surplus->jazzcash -= $request->jazzcash;
            $surplus->easypaisa -= $request->easypaisa;
            $surplus->save();
        }
        return redirect()->back();
    }
    public function saveScheduleSetting(Request $request)
    {
        $selectedId = $request->id;
        // Find the selected setting
        $setting = ScheduleSetting::findOrFail($selectedId);
        // Update only the records with the same type as the selected setting
        ScheduleSetting::where('txns_type', $setting->txns_type)->update(['value' => 0]);
        $setting->value = 1;
        $setting->save();
    
        return redirect()->back();
    }
    public function saveSurplus(Request $request)
    {
        $surplus=SurplusAmount::where('id','1')->first();
        $surplus->jazzcash=$surplus->jazzcash+$request->jazzcash  * 0.995;
        $surplus->easypaisa=$surplus->easypaisa+$request->easypaisa * 0.9925;
        $surplus->save();
        return redirect()->back();
    }
    public function getSuspendSetting()
    {
        $list = User::where('user_role','client')->where('active',1)->get();
        $list2 = ScheduleSetting::where('txns_type','jazzcash')->get();
        $list3 = ScheduleSetting::where('txns_type','easypaisa')->get();
        return view("admin.setting.api_setting",get_defined_vars());
    }
    public function apiSuspendSetting(Request $request)
    {
        if($request->type == "auto"){
            Setting::where('user_id',$request->id)->update(['auto' => $request->status]);
        }else{
            User::where('id',$request->id)->update([$request->type => $request->status]);
        }
        return redirect()->back();
    }
    public function saveAssignedAmount(Request $request)
    {
        Setting::where('user_id',$request->id)->update(['jc_assigned_value' => $request->jc_assigned_value,'ep_assigned_value'=>$request->ep_assigned_value]);
        return redirect()->back();
    }

    public function savePayinLimits(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'jc_payin_limit' => 'required|numeric|min:0',
            'ep_payin_limit' => 'required|numeric|min:0',
        ]);

        User::where('id', $request->user_id)->update([
            'jc_payin_limit' => $request->jc_payin_limit,
            'ep_payin_limit' => $request->ep_payin_limit,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Payin limits updated successfully'
        ]);
    }

    public function resetPayinLimits(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        User::where('id', $request->user_id)->update([
            'jc_payin_limit' => 0,
            'ep_payin_limit' => 0,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Payin limits reset to 0 successfully'
        ]);
    }
}
