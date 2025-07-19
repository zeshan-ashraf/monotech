<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Payout;
use App\Models\Transaction;
use App\Models\ArcheiveTransaction;
use App\Models\{User,Settlement, Setting, BackupTransaction, SurplusAmount};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{

    public function index()
    {
        $today=today()->format('d-m-Y');
        $clients = User::where('user_role', 'Client')->where('active',1)->get();
        $data = [];
        
        foreach ($clients as $client) {
            $userId = $client->id;
            
            $prevBal=Settlement::where('user_id', $userId)->whereDate('date', today()->subDay())->value('closing_bal') ?? 0;
            $payinSuccess= Transaction::where('user_id', $userId)->where('status', 'success')->whereDate('created_at', today())->sum('amount');
            $payoutSuccess= Payout::where('user_id', $userId)->where('status', 'success')->whereDate('created_at', today())->sum('amount');
            $prevUsdt= Settlement::where('user_id', $userId)->whereDate('date', today())->value('usdt') ?? 0;

            $epPayinAmount = Settlement::where('user_id', $userId)->whereDate('date', today())->value('ep_payin') ?? 0;
            $payinFee=$client->payin_fee;
            $payoutFee=$client->payout_fee;
            //getUnsettlement
            if ($userId == 2) {
                $payinSuccess = $epPayinAmount;
            } 
            $unsettletdAmount=$prevBal + $payinSuccess - ($payinSuccess*$payinFee + $payoutSuccess + $payoutSuccess*$payoutFee + $prevUsdt);
            $data[] = [
                'user' => $client,
                'prev_balance' => $prevBal,
                'jc_payin' => payinJCFunc($userId),
                'ep_payin' => $epPayinAmount,
                'total_payin' => $payinSuccess,
                'jc_payout' => payoutJCFunc($userId),
                'ep_payout' => payoutEPFunc($userId),
                'total_payout' => $payoutSuccess,
                'prev_usdt' => $prevUsdt,
                'unsettled_amount' => $unsettletdAmount,
                'unsettled_amount_balance' => getUnsettlement($userId),
                'assigned_amount' => Setting::where('user_id', $userId)->first(),
                'setting' => Setting::where('user_id', $userId)->first(),
            ];
        }
        $totals = [
            'prev_balance' => 0,
            'jc_payin' => 0,
            'ep_payin' => 0,
            'total_payin' => 0,
            'jc_payout' => 0,
            'ep_payout' => 0,
            'total_payout' => 0,
            'prev_usdt' => 0,
            'unsettled_amount' => 0,
            'unsettled_amount_balance'=>0,
            'assigned_jc' => 0,
            'assigned_ep' => 0,
            'assigned_payout' => 0,
        ];
        
        foreach ($data as $item) {
            $totals['prev_balance'] += $item['prev_balance'];
            $totals['jc_payin'] += $item['jc_payin'];
            $totals['ep_payin'] += $item['ep_payin'];
            $totals['total_payin'] += $item['total_payin'];
            $totals['jc_payout'] += $item['jc_payout'];
            $totals['ep_payout'] += $item['ep_payout'];
            $totals['total_payout'] += $item['total_payout'];
            $totals['prev_usdt'] += $item['prev_usdt'];
            $totals['unsettled_amount'] += $item['unsettled_amount'];
            $totals['unsettled_amount_balance'] += $item['unsettled_amount_balance'];
            $totals['assigned_jc'] += $item['assigned_amount']->jazzcash ?? 0;
            $totals['assigned_ep'] += $item['assigned_amount']->easypaisa ?? 0;
            $totals['assigned_payout'] += $item['assigned_amount']->payout_balance ?? 0;
        }
        
        $jcOkPendingOrder = Transaction::where([
            ['status', 'pending'],
            ['user_id', '2'],
            ['txn_type', 'jazzcash']
        ])->count();
        
        $epOkPendingOrder = Transaction::where([
            ['status', 'pending'],
            ['user_id', '2'],
            ['txn_type', 'easypaisa']
        ])->count();
        
        $jcPiqPendingOrder = Transaction::where([
            ['status', 'pending'],
            ['user_id', '4'],
            ['txn_type', 'jazzcash']
        ])->count();
        
        $epPiqPendingOrder = Transaction::where([
            ['status', 'pending'],
            ['user_id', '4'],
            ['txn_type', 'easypaisa']
        ])->count();
        
        $jcPkNPendingOrder = Transaction::where([
            ['status', 'pending'],
            ['user_id', '5'],
            ['txn_type', 'jazzcash']
        ])->count();
        
        $epPkNPendingOrder = Transaction::where([
            ['status', 'pending'],
            ['user_id', '5'],
            ['txn_type', 'easypaisa']
        ])->count();
        
        $jcMoneyPendingOrder = Transaction::where([
            ['status', 'pending'],
            ['user_id', '14'],
            ['txn_type', 'jazzcash']
        ])->count();
        
        $epMoneyPendingOrder = Transaction::where([
            ['status', 'pending'],
            ['user_id', '14'],
            ['txn_type', 'easypaisa']
        ])->count();
        
        $list = Setting::all();
        $surplusAmount=SurplusAmount::where('id','1')->first();
        return view('admin.index', get_defined_vars());

    }

    public function profile()
    {
        $user = auth()->user();
        return view('admin.security.profile',get_defined_vars());
    }
    public function accountSetting()
    {
        $user = auth()->user();
        return view('admin.security.password',get_defined_vars());
    }
    public function securityUpdate(Request $request)
    {
        $request->validate([
            'password' => ['required', 'confirmed','min:8','max:20']
        ]);
        User::where('id' , auth()->user()->id)->update(['password' => Hash::make($request->password)]);

        return redirect()->route('admin.account.settings')->with('message','Updated Successfully!');
    }
    public function testing()
    {
        $yesterday = \Carbon\Carbon::yesterday()->toDateString();

        $transactionReverse = DB::table('transactions')
            ->where('user_id', '2')
            ->where('status', 'reverse')
            ->whereDate('updated_at', Carbon::today())
            ->sum('amount');

        $archiveReverse = DB::table('archeive_transactions')
            ->where('user_id', '2')
            ->where('status', 'reverse')
            ->whereDate('updated_at', Carbon::today())
            ->sum('amount');

        $backupReverse = DB::table('backup_transactions')
            ->where('user_id', '2')
            ->where('status', 'reverse')
            ->whereDate('updated_at', Carbon::today())
            ->sum('amount');

        $totalReverseAmount = $transactionReverse + $archiveReverse + $backupReverse;
        
        // if($user->id == 2){
        //     $transactionReverseHalf = $totalReverseAmount * 0.5;
        // }
        // else{
            $transactionReverseHalf = $totalReverseAmount;
        // }
        dd($transactionReverseHalf);
    }
}
