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
    */
    'max_chunk' => 400,

];
