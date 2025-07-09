<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\{Settlement,SurplusAmount,Setting};
use Illuminate\Support\Facades\Cache;

class SurplusAddition extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'suplus:addition';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add surplus amount in wallet';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $userIds = [18, 19];
        
        foreach ($userIds as $user_id) {
            //Mega
            if($user_id == 18){
                $surplus_amount=SurplusAmount::find(1);
                $setting_amount=Setting::where('user_id',$user_id)->first();
                if($setting_amount->auto == 1){
                    if($setting_amount->easypaisa < $setting_amount->ep_assigned_value && $surplus_amount->easypaisa > 51000){
                        $epShortAmount=$setting_amount->ep_assigned_value - $setting_amount->easypaisa;
                        $surplus_amount->easypaisa -= $epShortAmount;
                        $surplus_amount->save();
                        $setting_amount->easypaisa += $epShortAmount;
                        $setting_amount->payout_balance += $epShortAmount;
                        $setting_amount->save();
                    }
                    if($setting_amount->jazzcash < $setting_amount->jc_assigned_value && $surplus_amount->jazzcash > 51000){
                        $jcShortAmount=$setting_amount->jc_assigned_value - $setting_amount->jazzcash;
                        $surplus_amount->jazzcash -= $jcShortAmount;
                        $surplus_amount->save();
                        $setting_amount->jazzcash += $jcShortAmount;
                        $setting_amount->payout_balance += $jcShortAmount;
                        $setting_amount->save();
                    }
                }
            }
        }
                    
        $this->info('Adding surplus amount in wallet successfully.');
    }
}