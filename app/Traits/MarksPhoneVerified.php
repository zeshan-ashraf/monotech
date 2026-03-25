<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\PhoneVerificationService;

/**
 * Integration trait (single allowed touchpoint in business flow).
 *
 * How to call markPhoneAsVerified on transaction success:
 * - Use this trait in your transaction success handler/service/listener:
 *   use \App\Traits\MarksPhoneVerified;
 *
 * - Then, when (and only when) a transaction becomes "success":
 *   $this->markPhoneAsVerified($phone);
 */
trait MarksPhoneVerified
{
    public function markPhoneAsVerified(string $phone): void
    {
        /** @var PhoneVerificationService $service */
        $service = app(PhoneVerificationService::class);
        $service->markVerified($phone);
    }
}

