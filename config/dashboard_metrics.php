<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Poll interval (seconds)
    |--------------------------------------------------------------------------
    | How often the dashboard metrics charts refresh via AJAX.
    | Set to 0 to disable polling.
    */
    'poll_interval_seconds' => (int) env('DASHBOARD_METRICS_POLL_SECONDS', 20),

    /*
    |--------------------------------------------------------------------------
    | Success rate window (minutes)
    |--------------------------------------------------------------------------
    | Rolling window for success / (success + failed) payin rate.
    */
    'success_rate_window_minutes' => (int) env('DASHBOARD_METRICS_SR_WINDOW', 5),

    /*
    |--------------------------------------------------------------------------
    | Excluded client user IDs
    |--------------------------------------------------------------------------
    | Active clients in this list will not appear on the dashboard metrics panel.
    */
    'exclude_user_ids' => [
        // 5,  // example: PK9
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles that can see all client metric rows
    |--------------------------------------------------------------------------
    */
    'viewer_roles_all_clients' => [
        'Super Admin',
        'Admin',
        'Manager',
    ],

];
