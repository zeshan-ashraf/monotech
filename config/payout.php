<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payout checkout rate limits (requests per minute, per IP)
    |--------------------------------------------------------------------------
    */
    'rate_limit' => [
        'default_per_minute' => (int) env('PAYOUT_RATE_LIMIT_DEFAULT', 60),
        'vip_per_minute' => (int) env('PAYOUT_RATE_LIMIT_VIP', 200),
        'vip_ips' => array_filter(array_map('trim', explode(',', env(
            'PAYOUT_RATE_LIMIT_VIP_IPS',
            '18.138.132.207'
        )))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payout amount & daily limits
    |--------------------------------------------------------------------------
    |
    | amount_min / amount_max: enforced per request by CheckPayoutAmount.
    | daily_default: combined successful payouts (all gateways) per Pakistan
    | business day. Overridden per user via users.payout_daily_limit when set.
    |
    | Daily check uses remaining capacity:
    | requestedAmount <= (dailyLimit - today'sSuccessfulTotal).
    |
    */
    'limits' => [
        'amount_min' => (float) env('PAYOUT_AMOUNT_MIN', 300),
        'amount_max' => (float) env('PAYOUT_AMOUNT_MAX', 50000),
        'daily_default' => (float) env('PAYOUT_DAILY_LIMIT_DEFAULT', 200000),
    ],

];
