<?php

namespace App\Support;

use App\Models\User;

class PayinAmountRules
{
    public static function forPaymentMethod(string $paymentMethod, ?User $user = null): string
    {
        if ($paymentMethod === 'easypaisa') {
            return self::forEasypaisa($user);
        }

        if ($paymentMethod === 'jazzcash') {
            return 'required|numeric|min:1|max:50000';
        }

        return 'required|numeric|min:1';
    }

    public static function forEasypaisa(?User $user): string
    {
        $rules = ['required', 'numeric'];

        $min = $user ? (float) $user->ep_min_amount : 0;
        $max = $user ? (float) $user->ep_max_amount : 0;

        $rules[] = $min > 0 ? 'min:' . $min : 'min:1';

        if ($max > 0) {
            $rules[] = 'max:' . $max;
        }

        return implode('|', $rules);
    }
}
