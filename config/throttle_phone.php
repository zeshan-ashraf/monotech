<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payin — excluded client emails
    |--------------------------------------------------------------------------
    |
    | Requests whose `client_email` matches one of these addresses (case-insensitive):
    | - Skip per-phone cooldown in ThrottlePhoneNumberMiddleware (no cache lock).
    | - Skip recent-transaction restriction in PayinController::checkRecentTransactionRestriction.
    |
    | Add or remove emails here as needed.
    |
    */

    'excluded_emails' => [
        'piqpay@monotech.com',
    ],

];
