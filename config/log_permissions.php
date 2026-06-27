<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Log Directory
    |--------------------------------------------------------------------------
    |
    | Directory containing daily rotated log files to monitor and repair.
    |
    */

    'directory' => storage_path('logs'),

    /*
    |--------------------------------------------------------------------------
    | Expected Ownership
    |--------------------------------------------------------------------------
    */

    'owner' => env('LOG_FILE_OWNER', 'www-data'),

    'group' => env('LOG_FILE_GROUP', 'www-data'),

    /*
    |--------------------------------------------------------------------------
    | Expected Permissions
    |--------------------------------------------------------------------------
    */

    'permissions' => 0644,

    /*
    |--------------------------------------------------------------------------
    | Daily Log Basenames
    |--------------------------------------------------------------------------
    |
    | Filenames are built as {basename}-{Y-m-d}.log for the current date only.
    |
    */

    'daily_log_basenames' => [
        'payin',
        'payout',
        'rejected_requests',
        'schedule_debug',
        'payin_diagnostics',
    ],

    'date_format' => 'Y-m-d',

];
