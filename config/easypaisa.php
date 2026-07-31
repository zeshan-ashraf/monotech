<?php

return [
    'mode'=>env('EASYPAISA_MODE'),
    'type'=>env('EASYPAISA_TYPE'),
    'callback'=> env('EASYPAISA_CALLBACK_URL'),

    'sandbox_url'=>env('EASYPAISA_SANDBOX_URL'),
    'sandbox_username'=> env('EASYPAISA_SANDBOX_USERNAME'),
    'sandbox_password'=>env('EASYPAISA_SANDBOX_PASSWORD'),
    'sandbox_storeid'=>env('EASYPAISA_SANDBOX_STOREID'),
    'sandbox_hashkey'=> env('EASYPAISA_SANDBOX_HASHKEY'),

    'prod_username'=> env('EASYPAISA_PRODUCTION_USERNAME'),
    'prod_password'=>env('EASYPAISA_PRODUCTION_PASSWORD'),
    'prod_storeid'=> env('EASYPAISA_PRODUCTION_STOREID'),
    
    'prod_gym_username'=> env('EASYPAISA_GYM_PRODUCTION_USERNAME'),
    'prod_gym_password'=> env('EASYPAISA_GYM_PRODUCTION_PASSWORD'),
    'prod_gym_storeid'=> env('EASYPAISA_GYM_PRODUCTION_STOREID'),

    'prod_pixelpush_username'=> env('EASYPAISA_PIXELPUSH_PRODUCTION_USERNAME'),
    'prod_pixelpush_password'=> env('EASYPAISA_PIXELPUSH_PRODUCTION_PASSWORD'),
    'prod_pixelpush_storeid'=> env('EASYPAISA_PIXELPUSH_PRODUCTION_STOREID'),

    'prod_adlearn_username'=> env('EASYPAISA_ADLEARN_PRODUCTION_USERNAME'),
    'prod_adlearn_password'=> env('EASYPAISA_ADLEARN_PRODUCTION_PASSWORD'),
    'prod_adlearn_storeid'=> env('EASYPAISA_ADLEARN_PRODUCTION_STOREID'),

    'prod_bsalonx_username'=> env('EASYPAISA_BSALONX_PRODUCTION_USERNAME'),
    'prod_bsalonx_password'=> env('EASYPAISA_BSALONX_PRODUCTION_PASSWORD'),
    'prod_bsalonx_storeid'=> env('EASYPAISA_BSALONX_PRODUCTION_STOREID'),

    'prod_wosparlex_username'=> env('EASYPAISA_WOSPARLEX_PRODUCTION_USERNAME'),
    'prod_wosparlex_password'=> env('EASYPAISA_WOSPARLEX_PRODUCTION_PASSWORD'),
    'prod_wosparlex_storeid'=> env('EASYPAISA_WOSPARLEX_PRODUCTION_STOREID'),

    'prod_digimart_username'=> env('EASYPAISA_DIGIMART_PRODUCTION_USERNAME'),
    'prod_digimart_password'=> env('EASYPAISA_DIGIMART_PRODUCTION_PASSWORD'),
    'prod_digimart_storeid'=> env('EASYPAISA_DIGIMART_PRODUCTION_STOREID'),

    'prod_megakit_username'=> env('EASYPAISA_MEGAKIT_PRODUCTION_USERNAME'),
    'prod_megakit_password'=> env('EASYPAISA_MEGAKIT_PRODUCTION_PASSWORD'),
    'prod_megakit_storeid'=> env('EASYPAISA_MEGAKIT_PRODUCTION_STOREID'),

    'active_ep_substore_username'=> env('EASYPAISA_PRODUCTION_WOSPARLEX_USERNAME'),
    'active_ep_substore_password'=>env('EASYPAISA_PRODUCTION_WOSPARLEX_PASSWORD'),
    'active_ep_substore_storeid'=> env('EASYPAISA_PRODUCTION_WOSPARLEX_STOREID'),

    // 'active_ep_substore_username'=> env('EASYPAISA_PRODUCTION_CODEBASE_USERNAME'),
    // 'active_ep_substore_password'=>env('EASYPAISA_PRODUCTION_CODEBASE_PASSWORD'),
    // 'active_ep_substore_storeid'=> env('EASYPAISA_PRODUCTION_CODEBASE_STOREID'),
    
    'prod_hashkey'=> env('EASYPAISA_PRODUCTION_HASHKEY'),
    'prod_url'=> env('EASYPAISA_PRODUCTION_URL'),
    'status_inquiry_url' => env(
        'EASYPAISA_STATUS_INQUIRY',
        'https://easypay.easypaisa.com.pk/easypay-service/rest/v4/inquire-transaction'
    ),
    'account_num' => env('EASYPAISA_ACCOUNT_NUM'),

    'hosted'=> env('EASYPAISA_HOSTED_CHECKOUT'),

    /*
    |--------------------------------------------------------------------------
    | Easypaisa status crons — minimum transaction age before inquiry/recheck
    |--------------------------------------------------------------------------
    | Used by pending-status and recheck-status commands. Avoids racing checkout.
    */
    'cron_pending_min_age_minutes' => (int) env('EASYPAISA_CRON_PENDING_MIN_AGE_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | Easypaisa payin pending-queue throttle (easypaisa.pending.limit middleware)
    |--------------------------------------------------------------------------
    | Block new Easypaisa payins when pending count >= block threshold.
    | While blocked, requests stay rejected until pending count <= resume threshold.
    */
    'pending_block_threshold' => (int) env('EASYPAISA_PENDING_BLOCK_THRESHOLD', 500),
    'pending_resume_threshold' => (int) env('EASYPAISA_PENDING_RESUME_THRESHOLD', 100),
    'pending_count_cache_minutes' => (int) env('EASYPAISA_PENDING_COUNT_CACHE_MINUTES', 1),
];