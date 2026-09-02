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
    | Pending < 300      → 2 workers
    | Pending 300–399    → 4 workers
    | Pending 400–499    → 6 workers
    | Pending >= 500     → 0 workers (stop new EasyPaisa API requests)
    |
    | Hard cap is always 6. Never spawn from transaction count.
    */
    'status_workers' => [
        'pending_for_4' => 300,
        'pending_for_6' => 400,
        'stop_at_pending' => 500,
        'max_workers' => 6,
    ],

];
