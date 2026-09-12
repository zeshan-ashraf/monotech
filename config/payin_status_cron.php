<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Minimum transaction age before status crons may process it
    |--------------------------------------------------------------------------
    |
    | Prevents race conditions where checkout is still in flight and a status
    | inquiry returns a false negative (e.g. Easypaisa 0003 INVALID ORDER ID).
    |
    */
    'min_age_minutes' => (int) env('PAYIN_STATUS_CRON_MIN_AGE_MINUTES', 2),

];
