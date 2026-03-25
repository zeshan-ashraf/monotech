<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Phone Verification Module (plug-and-play)
    |--------------------------------------------------------------------------
    |
    | Usage (route-based, optional middleware):
    | - Attach to any route or group:
    |   Route::post('/checkout', ...)->middleware('phone.verified');
    |
    | Usage (mark phone verified on transaction success):
    | - Use the trait on a service/controller/listener and call:
    |   $this->markPhoneAsVerified($phone);
    |
    | Reuse in another Laravel project:
    | - Copy:
    |   database/migrations/*create_verified_numbers_table.php
    |   config/phone_verification.php
    |   app/Services/PhoneVerificationService.php
    |   app/Http/Middleware/EnsurePhoneIsVerified.php
    |   app/Traits/MarksPhoneVerified.php
    |   app/Console/Commands/PopulateVerifiedNumbers.php
    | - Register middleware alias in app/Http/Kernel.php:
    |   'phone.verified' => \App\Http\Middleware\EnsurePhoneIsVerified::class,
    | - Run:
    |   php artisan migrate
    |   php artisan phone:populate-verified-numbers
    |
    */

    'phone_input_key' => 'phone',

    // Cache TTL for verified marker (hours).
    'cache_ttl_hours' => 2,

    // Lock duration to prevent concurrent duplicate processing (seconds).
    'lock_seconds' => 5,
];

