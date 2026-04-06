<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payin phone throttle — excluded client emails
    |--------------------------------------------------------------------------
    |
    | Requests whose `client_email` matches one of these addresses (case-insensitive)
    | skip the per-phone cooldown in ThrottlePhoneNumberMiddleware (no cache lock).
    |
    | Add or remove emails here as needed.
    |
    */

    'excluded_emails' => [
        'piqpay@monotech.com',
    ],

];
