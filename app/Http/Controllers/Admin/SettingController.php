<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{User, Summary, Setting,Settlement, ScheduleSetting,SurplusAmount,Transaction,PayoutSetting};
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
            ->join('users', 'users.id', '=', 'transactions.user_id')
            ->select('transactions.*', 'users.name as user_name')
            ->where('transactions.status', 'reverse')
            ->when($txn_type && $txn_type !== 'all', fn($q) => $q->where('transactions.txn_type', $txn_type))
            ->when($client && $client !== 'all', fn($q) => $q->where('transactions.user_id', $client))
            ->when($start && $end, fn($q) =>
                $q->whereBetween('transactions.updated_at', ["$start 00:00:00", "$end 23:59:59"])
            );

        $query2 = DB::table('archeive_transactions')
            ->join('users', 'users.id', '=', 'archeive_transactions.user_id')
            ->select('archeive_transactions.*', 'users.name as user_name')
            ->where('archeive_transactions.status', 'reverse')
            ->when($txn_type && $txn_type !== 'all', fn($q) => $q->where('archeive_transactions.txn_type', $txn_type))
            ->when($client && $client !== 'all', fn($q) => $q->where('archeive_transactions.user_id', $client))
            ->when($start && $end, fn($q) =>
                $q->whereBetween('archeive_transactions.updated_at', ["$start 00:00:00", "$end 23:59:59"])
            );

        $query3 = DB::table('backup_transactions')
            ->join('users', 'users.id', '=', 'backup_transactions.user_id')
            ->select('backup_transactions.*', 'users.name as user_name')
            ->where('backup_transactions.status', 'reverse')
            ->when($txn_type && $txn_type !== 'all', fn($q) => $q->where('backup_transactions.txn_type', $txn_type))
            ->when($client && $client !== 'all', fn($q) => $q->where('backup_transactions.user_id', $client))
            ->when($start && $end, fn($q) =>
                $q->whereBetween('backup_transactions.updated_at', ["$start 00:00:00", "$end 23:59:59"])
            );

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
        $targetUserId = ($userid == 18) ? $userid : $request->id;
        
        // Calculate unsettled_amount_balance
        $prevBal = Settlement::where('user_id', $targetUserId)->whereDate('date', today()->subDay())->value('closing_bal') ?? 0;
        $settlement = Settlement::where('user_id', $targetUserId)->whereDate('date', today())->first();
        
        if (!$settlement) {
            if ($request->ajax()) {
                return response()->json(['error' => 'Settlement record not found for today.']);
            }
            return redirect()->back()->with('error', 'Settlement record not found for today.');
        }
        
        $client = User::find($targetUserId);
        if (!$client) {
            if ($request->ajax()) {
                return response()->json(['error' => 'User not found.']);
            }
            return redirect()->back()->with('error', 'User not found.');
        }
        
        $epPayinAmount = $settlement->ep_payin ?? 0;
        $jcPayinAmount = $settlement->jc_payin ?? 0;
        $epPayoutAmount = $settlement->ep_payout ?? 0;
        $jcPayoutAmount = $settlement->jc_payout ?? 0;
        $payinSuccess = $epPayinAmount + $jcPayinAmount;
        $payoutSuccess = $epPayoutAmount + $jcPayoutAmount;
        $prevUsdt = $settlement->usdt ?? 0;
        $payinFee = $client->payin_fee ?? 0;
        $payoutFee = $client->payout_fee ?? 0;
        
        // Calculate unsettled amount
        $unsettletdAmount = $prevBal + $payinSuccess - ($payinSuccess * $payinFee + $payoutSuccess + $payoutSuccess * $payoutFee + $prevUsdt);
        
        // Get current assigned amount
        $currentSetting = Setting::where('user_id', $targetUserId)->first();
        if (!$currentSetting) {
            if ($request->ajax()) {
                return response()->json(['error' => 'Setting record not found.']);
            }
            return redirect()->back()->with('error', 'Setting record not found.');
        }
        
        $currentAssignedAmount = $currentSetting->payout_balance ?? 0;
        
        // Calculate unsettled_amount_balance
        $unsettledAmountBalance = $unsettletdAmount - $currentAssignedAmount;
        
        // Get and validate submitted amounts
        $submittedEasypaisa = floatval($request->easypaisa ?? 0);
        // $submittedJazzcash = floatval($request->jazzcash ?? 0);
        
        // $submittedTotal = $submittedEasypaisa + $submittedJazzcash;
        $submittedTotal = $submittedEasypaisa;
       // dd($submittedTotal,$currentSetting->easypaisa ,  $currentSetting->jazzcash,( $submittedTotal +$currentSetting->easypaisa +  $currentSetting->jazzcash),$unsettletdAmount);
        // Validate: submitted amount should not be greater than unsettled_amount_balance
        // Skip this validation for Admin and Super Admin
        $userRole = auth()->user()->user_role ?? '';
        if ($userRole !== "Admin" && $userRole !== "Super Admin" && $userRole !== "Manager") {// this couold be Client role only
            if ( ( $submittedTotal +$currentSetting->payout_balance) > $unsettletdAmount) {
                $errorMsg = 'Submitted wallet amount  cannot be greater than unsettled amount balance. Available balance: ' . number_format(round($unsettledAmountBalance, 0));
                if ($request->ajax()) {
                    return response()->json(['error' => $errorMsg]);
                }
                return redirect()->back()->with('error', $errorMsg);
            }
        }
        
        // $surplus = SurplusAmount::find(1);
        // $payout_setting = PayoutSetting::find(1);
        $setting = $currentSetting;
        // $setting->easypaisa += $submittedEasypaisa;
        // $setting->jazzcash += $submittedJazzcash;
        $setting->payout_balance += $submittedEasypaisa;
        $setting->save();
        // $surplus->jazzcash -= $submittedJazzcash;
        // if($payout_setting->type == 0){
        //     $surplus->easypaisa -= $submittedEasypaisa;
        //     $surplus->save();
        // }else{
            
        //     $surplus->jazzcash -= $submittedEasypaisa;
        //     $surplus->save();
        // }
        
        $successMsg = 'Amount assigned successfully.';
        if ($request->ajax()) {
            return response()->json(['success' => $successMsg]);
        }
        return redirect()->back()->with('success', $successMsg);
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
        $surplus->jazzcash=$surplus->jazzcash+$request->jazzcash;
        $surplus->easypaisa=$surplus->easypaisa+$request->easypaisa;
        $surplus->save();
        return redirect()->back();
    }
    public function getSuspendSetting()
    {
        $list = User::where('user_role','client')->where('active',1)->get();
        $list2 = ScheduleSetting::where('txns_type','jazzcash')->get();
        $list3 = ScheduleSetting::where('txns_type','easypaisa')->get();
        $payout_setting = PayoutSetting::first();
        $verificationUsers = User::where('user_role', 'client')
            ->select('id', 'name', 'email', 'new_user_verification')
            ->orderBy('name')
            ->get();
        $metricsClients = $this->canManageDbMetrics()
            ? User::query()
                ->whereRaw('LOWER(user_role) = ?', ['client'])
                ->where('active', 1)
                ->orderBy('db_metrics_order')
                ->orderBy('name')
                ->get(['id', 'name', 'enable_db_metrics', 'db_metrics_order'])
            : collect();
        return view("admin.setting.api_setting",get_defined_vars());
    }

    public function toggleDbMetrics(Request $request)
    {
        $this->authorizeDbMetricsManagement();

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'status' => 'required|in:0,1',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $user->enable_db_metrics = (int) $validated['status'];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard metrics visibility updated successfully.',
        ]);
    }

    public function saveDbMetricsOrder(Request $request)
    {
        $this->authorizeDbMetricsManagement();

        $validated = $request->validate([
            'order' => 'required|array|min:1',
            'order.*' => 'integer|exists:users,id',
        ]);

        foreach ($validated['order'] as $position => $userId) {
            User::where('id', $userId)->update([
                'db_metrics_order' => $position + 1,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dashboard metrics order saved successfully.',
        ]);
    }

    private function canManageDbMetrics(): bool
    {
        return in_array(auth()->user()->user_role, ['Super Admin', 'Admin', 'Manager'], true);
    }

    private function authorizeDbMetricsManagement(): void
    {
        if (!$this->canManageDbMetrics()) {
            abort(403);
        }
    }

    public function toggleNewUserVerification(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'status' => 'required|in:0,1',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $user->new_user_verification = (int) $validated['status'];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'New user verification updated successfully.',
        ]);
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

    public function saveEpAmountLimits(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'ep_min_amount' => 'required|numeric|min:0',
            'ep_max_amount' => 'required|numeric|min:0',
        ]);

        if (
            $validated['ep_min_amount'] > 0
            && $validated['ep_max_amount'] > 0
            && $validated['ep_min_amount'] > $validated['ep_max_amount']
        ) {
            return response()->json([
                'status' => 'error',
                'message' => 'EP min amount cannot be greater than EP max amount.',
            ], 422);
        }

        User::where('id', $validated['user_id'])->update([
            'ep_min_amount' => $validated['ep_min_amount'],
            'ep_max_amount' => $validated['ep_max_amount'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Easypaisa amount limits updated successfully',
        ]);
    }

    public function resetEpAmountLimits(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        User::where('id', $request->user_id)->update([
            'ep_min_amount' => 0,
            'ep_max_amount' => 0,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Easypaisa amount limits reset successfully',
        ]);
    }

    public function payoutSetting(Request $request)
    {
        $setting = PayoutSetting::first();

        $setting->type = $request->type;
        $setting->save();

        return response()->json([
            'success' => true
        ]);
    }
}
