<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rows per cron run, keyed by active ScheduleSetting type (easypaisa)
    |--------------------------------------------------------------------------
    */
    'chunk_by_schedule' => [
        'everyFiveSeconds' => ['check' => 4, 'recheck' => 4],
        'everyTenSeconds' => ['check' => 8, 'recheck' => 8],
        'everyThirtySeconds' => ['check' => 25, 'recheck' => 20],
        'everyMinute' => ['check' => 150, 'recheck' => 150],
        'everyFiveMinutes' => ['check' => 200, 'recheck' => 50],
        'everyTenMinutes' => ['check' => 400, 'recheck' => 50],
    ],

    'default_chunk' => [
        'check' => 50,
        'recheck' => 50,
    ],

    /*
    | Fallback cap when schedule type is unknown (explicit schedule values are not capped).
    | These values are ROW CHUNK SIZES for recheck/other crons. They are NOT worker counts.
    */
    'max_chunk' => 400,

    /*
    |--------------------------------------------------------------------------
    | EasyPaisa status-check WORKER allocation (not chunk size)
    |--------------------------------------------------------------------------
    |
    | 1–99     → 2 workers
    | 100–199  → 4 workers
    | 200–299  → 6 workers
    | 300–399  → 8 workers
    | 400+     → 10 workers
    |
    | Hard cap is 10. Scale-up only; never kill workers.
    | stop_at_pending is unused by this command (payin middleware owns that).
    */
    'status_workers' => [
        'pending_for_2' => 100,
        'pending_for_4' => 200,
        'pending_for_6' => 300,
        'pending_for_8' => 300,
        'pending_for_10' => 400,
        'stop_at_pending' => 500,
        'max_workers' => 10,
    ],

];
