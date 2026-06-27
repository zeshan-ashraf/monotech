<?php

return [

    'key_ttl_seconds' => 7200,

    'aggregation_window_minutes' => 60,

    'slow_threshold_ms' => 3000,

    'very_slow_threshold_ms' => 5000,

    'default_flow' => 'payin',

    'gateways' => [
        'easypaisa',
        'jazzcash',
    ],

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

    'application_errors' => [
        'validation_failed' => 'validation_failed',
        'merchant_disabled' => 'application_errors',
        'phone_not_verified' => 'application_errors',
        'duplicate_transaction' => 'duplicate_order',
        'duplicate_order' => 'duplicate_order',
        'unauthorized' => 'application_errors',
        'rule_violation' => 'rule_violation',
        'rate_limited' => 'rule_violation',
        'pending_backlog' => 'infrastructure_errors',
        'user_not_found' => 'application_errors',
    ],

    'infrastructure_errors' => [
        'timeout' => 'timeouts',
        'connection_timeout' => 'timeouts',
        'http_500' => 'infrastructure_errors',
        'http_502' => 'infrastructure_errors',
        'http_503' => 'infrastructure_errors',
        'http_504' => 'infrastructure_errors',
        'ssl_error' => 'infrastructure_errors',
        'dns_error' => 'infrastructure_errors',
        'connection_error' => 'infrastructure_errors',
    ],

];
