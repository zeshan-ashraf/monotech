<?php

return [

    'key_ttl_seconds' => 7200,

    'default_window_minutes' => 5,

    'allowed_windows' => [1, 5, 10, 30, 60],

    'slow_threshold_ms' => 3000,

    'very_slow_threshold_ms' => 5000,

  /**
   * Classify APIs by Laravel route name (fnmatch patterns).
   * More specific patterns must appear before broader ones.
   */
    'route_name_patterns' => [
        'payin.status.*' => 'payin_status',
        'payout.status.*' => 'payout_status',
        'payin.*' => 'payin',
        'payout.*' => 'payout',
        'dashboard.*' => 'dashboard',
        'webhook.*' => 'webhook',
    ],

  /**
   * Fallback classification by request path when route names are unavailable.
   * Patterns are regular expressions matched against the request path.
   */
    'path_patterns' => [
        '#^api/payin-status-check$#i' => 'payin_status',
        '#^api/payout-status-check$#i' => 'payout_status',
        '#^api/(v1/)?get-dashboard-data$#i' => 'dashboard',
        '#^api/jazzcash/callback$#i' => 'webhook',
        '#^api/v1/payment-checkout$#i' => 'payin',
        '#^api/v1/payin-checkout$#i' => 'payin',
        '#^api/payin/#i' => 'payin',
        '#^api/v1/payout/#i' => 'payout',
        '#^api/payout/#i' => 'payout',
        '#^api/ibft-payout/#i' => 'payout',
    ],

    'default_api_type' => 'other',

    'api_types' => [
        'payin',
        'payout',
        'payin_status',
        'payout_status',
        'dashboard',
        'webhook',
        'other',
    ],

    'api_labels' => [
        'payin' => 'PayIn',
        'payout' => 'Payout',
        'payin_status' => 'PayIn Status',
        'payout_status' => 'Payout Status',
        'dashboard' => 'Dashboard',
        'webhook' => 'Webhook',
        'other' => 'Other',
    ],

  /**
   * Map normalized gateway/infrastructure error types to Redis hash fields.
   */
    'gateway_errors' => [
        'system_error' => 'system_error',
        'invalid_account' => 'invalid_account',
        'invalid_order' => 'invalid_order',
        'rule_violation' => 'rule_violation',
        'duplicate_order' => 'duplicate_order',
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
        'blocked_number' => 'rule_violation',
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
