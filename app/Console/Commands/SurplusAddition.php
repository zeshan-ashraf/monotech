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
            //Ok
            // if($user_id == 19){
            //     $surplus_amount = SurplusAmount::find(1);
            //     $setting_amount = Setting::where('user_id', 19)->first();
            //     if($setting_amount->auto == 1){
            //         // Retrieve previous unSettledAmountPayable (store this value in session/cache/database)
            //         $previousUnsettledAmountPayable = Cache::get('unsettled_amount_19', 0);
                    
            //         // Get current unSettledAmountPayable
            //         $currentUnsettledAmountPayable = getUnsettlementPayable(19);
                
            //         $jcPercentage = ($currentUnsettledAmountPayable * $setting_amount->jc_assigned_value) / 100;
            //         $epPercentage = ($currentUnsettledAmountPayable * $setting_amount->ep_assigned_value) / 100;
                
            //         // Check if amount has decreased
            //         if ($currentUnsettledAmountPayable < $previousUnsettledAmountPayable) {
            //             $decreasedAmount = $previousUnsettledAmountPayable - $currentUnsettledAmountPayable;
                
            //             // Calculate amounts to be added back
            //             $jcReturnAmount = ($decreasedAmount * $setting_amount->jc_assigned_value) / 100;
            //             $epReturnAmount = ($decreasedAmount * $setting_amount->ep_assigned_value) / 100;
                
            //             // Add back to surplus
            //             $surplus_amount->easypaisa += $epReturnAmount;
            //             $surplus_amount->jazzcash += $jcReturnAmount;
            //             $surplus_amount->save();
            //         }
                
            //         // Update cache with the new unsettled amount
            //         Cache::put('unsettled_amount_19', $currentUnsettledAmountPayable);
                
            //         // Proceed with usual logic
            //         if ($surplus_amount->easypaisa > 51000) {
            //             $epShortAmount = $epPercentage - $setting_amount->easypaisa;
            //             $surplus_amount->easypaisa -= $epShortAmount;
            //             $surplus_amount->save();
            //             $setting_amount->easypaisa += $epShortAmount;
            //             $setting_amount->payout_balance += $epShortAmount;
            //             $setting_amount->save();
            //         }
                
            //         if ($surplus_amount->jazzcash > 51000) {
            //             $jcShortAmount = $jcPercentage - $setting_amount->jazzcash;
            //             $surplus_amount->jazzcash -= $jcShortAmount;
            //             $surplus_amount->save();
            //             $setting_amount->jazzcash += $jcShortAmount;
            //             $setting_amount->payout_balance += $jcShortAmount;
            //             $setting_amount->save();
            //         }
            //     }
            // }
        }
                    
        $this->info('Adding surplus amount in wallet successfully.');
    }
}