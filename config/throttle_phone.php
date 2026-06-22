<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payin — excluded client emails
    |--------------------------------------------------------------------------
    |
    | Requests whose `client_email` matches one of these addresses (case-insensitive),
    | or whose `phone` matches excluded_phones (normalized to 03XXXXXXXXX), bypass:
    | - ThrottlePhoneNumberMiddleware (3-minute per-phone cache lock)
    | - ThrottlePayinCheckoutGlobalMiddleware (excluded_phones only; emails still count)
    | - PayinController::checkRecentTransactionRestriction (success/fail cooldown)
    | - HighValueTransactionRestriction (50k+ / 10-minute rule)
    | - CheckedBlockedNumbersMiddleware (blocked_numbers table)
    |
    | Phone formats accepted: 03XXXXXXXXX or 923XXXXXXXXX
    |
    */

    'excluded_emails' => [
        'piqpay@monotech.com',
        'bigpay@monotech.com',
        'jackpay@monotech.com',
    ],

    'excluded_phones' => [
        '03244361494',
        '03316215445',
        // '923001234567',
    ],

    /*
    |--------------------------------------------------------------------------
    | Global payin checkout rate limit (all IPs combined)
    |--------------------------------------------------------------------------
    |
    | Applies to POST /api/payin/checkout and POST /api/v1/payment-checkout.
    | One shared counter — not per IP. Excluded phones above do not count.
    |
    */

    'global_checkout_per_minute' => 200,

];
