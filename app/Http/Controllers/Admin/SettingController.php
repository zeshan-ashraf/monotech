<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{User, Summary, Setting, ScheduleSetting,SurplusAmount,Transaction,ManualPayout};
use Illuminate\Http\Request;
use DB;

class SettingController extends Controller
{
    public function addSetting()
    {
        $list = DB::table('transactions')
        ->select('*')->where('status', 'reverse') // Select all columns
        ->union(
            DB::table('archeive_transactions')->select('*')->where('status', 'reverse')
        )
        ->union(
            DB::table('backup_transactions')->select('*')->where('status', 'reverse')
        )
        ->orderBy('created_at', 'desc')
        ->get();
        return view("admin.setting.list",get_defined_vars());
    }
    public function okList()
    {
        $list = DB::table(DB::raw("(
            SELECT * FROM transactions WHERE user_id = 2 AND status = 'reverse'
            UNION ALL
            SELECT * FROM archeive_transactions WHERE user_id = 2 AND status = 'reverse'
            UNION ALL
            SELECT * FROM backup_transactions 
                WHERE user_id = 2 AND status = 'reverse' AND created_at >= '2025-05-01 00:00:00'
        ) as all_transactions"))
        ->orderBy('updated_at', 'desc')
        ->get();
        
        return view("admin.setting.list", get_defined_vars());
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
        $setting=Setting::where('user_id',$request->id)->first();
        $setting->easypaisa = $setting->easypaisa+$request->easypaisa;
        $setting->jazzcash = $setting->jazzcash+$request->jazzcash;
        $setting->payout_balance = $setting->easypaisa+$setting->jazzcash;
        $setting->save();
        
        $surplus=SurplusAmount::where('id','1')->first();
        $surplus->jazzcash=$surplus->jazzcash - $request->jazzcash;
        $surplus->easypaisa=$surplus->easypaisa - $request->easypaisa;
        $surplus->save();
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
        $list = User::where('user_role','client')->get();
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
}
