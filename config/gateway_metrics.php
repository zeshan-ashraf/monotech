<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Redis key TTL (seconds)
    |--------------------------------------------------------------------------
    |
    | Each per-minute metrics hash expires after this many seconds.
    |
    */

    'key_ttl_seconds' => 7200,

    /*
    |--------------------------------------------------------------------------
    | Slow request thresholds (milliseconds)
    |--------------------------------------------------------------------------
    */

    'slow_threshold_ms' => 3000,

    'very_slow_threshold_ms' => 5000,

    /*
    |--------------------------------------------------------------------------
    | Supported gateways
    |--------------------------------------------------------------------------
    */

    'gateways' => [
        'easypaisa',
        'jazzcash',
    ],

    /*
    |--------------------------------------------------------------------------
    | Gateway-level error mapping
    |--------------------------------------------------------------------------
    |
    | Match Easypaisa/JazzCash response codes and descriptions to metric fields.
    | Keys under response_codes are matched exactly; response_descriptions use
    | case-insensitive substring matching.
    |
    */

    'gateway_errors' => [
        'easypaisa' => [
            'response_codes' => [
                '0003' => 'invalid_order',
            ],
            'response_descriptions' => [
                'SYSTEM ERROR' => 'system_error',
                'INVALID ACCOUNT' => 'invalid_account',
                'ACCOUNT DOES NOT EXIST' => 'invalid_account',
                'INVALID ORDER' => 'invalid_order',
                'RULE VIOLATION' => 'rule_violation',
                'DUPLICATE ORDER' => 'duplicate_order',
            ],
        ],
        'jazzcash' => [
            'response_codes' => [],
            'response_descriptions' => [
                'SYSTEM ERROR' => 'system_error',
                'INVALID ACCOUNT' => 'invalid_account',
                'DOES NOT EXIST' => 'invalid_account',
                'INVALID ORDER' => 'invalid_order',
                'RULE VIOLATION' => 'rule_violation',
                'DUPLICATE ORDER' => 'duplicate_order',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Application-level error mapping
    |--------------------------------------------------------------------------
    |
    | Internal rejection reasons used by middleware and controllers.
    |
    */

    'application_errors' => [
        'validation_failed' => 'validation_errors',
        'merchant_disabled' => 'application_errors',
        'phone_not_verified' => 'application_errors',
        'duplicate_transaction' => 'duplicate_order',
        'duplicate_order' => 'duplicate_order',
        'unauthorized' => 'application_errors',
        'rule_violation' => 'rule_violations',
        'rate_limited' => 'rule_violations',
        'pending_backlog' => 'infrastructure_errors',
        'user_not_found' => 'application_errors',
    ],

    /*
    |--------------------------------------------------------------------------
    | Infrastructure error mapping
    |--------------------------------------------------------------------------
    */

    'infrastructure_errors' => [
        'connection_timeout' => 'timeouts',
        'http_500' => 'http_errors',
        'http_502' => 'http_errors',
        'http_503' => 'http_errors',
        'http_504' => 'http_errors',
        'ssl_error' => 'infrastructure_errors',
        'dns_error' => 'infrastructure_errors',
        'connection_error' => 'infrastructure_errors',
    ],

];
