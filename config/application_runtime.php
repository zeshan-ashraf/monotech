<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | General
    |--------------------------------------------------------------------------
    */

    'cache_ttl_seconds' => 5,

    // Redis connection used ONLY for dashboard metrics cache
    'redis_connection' => 'metrics',

    /*
    |--------------------------------------------------------------------------
    | PHP-FPM
    |--------------------------------------------------------------------------
    */

    'php_fpm' => [

        // Configure Nginx to expose this endpoint.
        // Currently it returns 404.
        'status_url'  => env('PHP_FPM_STATUS_URL', 'http://127.0.0.1/status'),
        'status_path' => env('PHP_FPM_STATUS_PATH', '/status'),

        // Worker utilization thresholds
        'slow_threshold_utilization'     => 70,
        'critical_threshold_utilization' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduler
    |--------------------------------------------------------------------------
    */

    'scheduler' => [

        // Scheduler heartbeat
        'tick_warning_seconds'  => 120,
        'tick_critical_seconds' => 300,

        // Long running scheduled command
        'command_warning_seconds' => 600,

        // Redis metadata TTL
        'metadata_ttl_seconds' => 86400,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    */

    'queue' => [

        // Current production queue driver
        'connection' => env('QUEUE_CONNECTION', 'database'),

        // Future-proof if you add more queues
        'queues' => array_filter(
            array_map(
                'trim',
                explode(',', env('RUNTIME_MONITOR_QUEUES', 'default'))
            )
        ),

        // Job running too long
        'job_warning_seconds' => 300,

        // Cache failed job count
        'failed_jobs_cache_seconds' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Process Monitoring
    |--------------------------------------------------------------------------
    */

    'process' => [

        // PHP request longer than 30 sec
        'php_request_seconds' => 30,

        // Queue job longer than 5 min
        'queue_job_seconds' => 300,

        // Scheduler command longer than 10 min
        'scheduler_command_seconds' => 600,

        // Gateway timeout threshold
        'gateway_request_seconds' => 60,

        // Linux ps command timeout
        'ps_timeout_seconds' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Supervisor
    |--------------------------------------------------------------------------
    */

    'supervisor' => [

        // Your server already uses Supervisor
        'enabled' => env('SUPERVISOR_STATUS_ENABLED', true),

        'status_command' => env(
            'SUPERVISOR_STATUS_COMMAND',
            'supervisorctl status'
        ),
    ],

];