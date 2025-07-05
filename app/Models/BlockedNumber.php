<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BlockedNumber extends Model
{
    protected $fillable = [
        'user_id',
        'phone_number',
        'reason',
        'payment_method',
        'response_code',
        'response_desc',
        'attempt_count',
        'blocked_attempt_count',
        'block_until',
        'is_permanent',
        'first_insufficient_attempt',
        'last_cancellation_attempt',
        'cancellation_count'
    ];

    protected $casts = [
        'block_until' => 'datetime',
        'is_permanent' => 'boolean',
        'first_insufficient_attempt' => 'datetime',
        'last_cancellation_attempt' => 'datetime'
    ];

    /**
     * Get the user that owns the blocked number
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the blocking duration based on attempt count
     */
    public static function getBlockDuration(int $attemptCount): int
    {
        return match($attemptCount) {
            1 => 24, // 24 hours
            2, 3 => 7 * 24, // 7 days
            4 => PHP_INT_MAX, // Permanent
            default => 24
        };
    }

    /**
     * Check if a number is currently blocked
     */
    public static function isBlocked(string $phoneNumber, string $paymentMethod): bool
    {
        return self::where('phone_number', $phoneNumber)
            ->where('payment_method', $paymentMethod)
            ->where(function($query) {
                $query->where('is_permanent', true)
                    ->orWhere('block_until', '>', now());
            })
            ->exists();
    }

    /**
     * Handle insufficient balance blocking for JazzCash
     */
    public static function handleInsufficientBalance(
        string $phoneNumber,
        string $responseCode,
        string $responseDesc,
        ?int $userId = null
    ): void {
        $blocked = self::where('phone_number', $phoneNumber)->first();

        if (!$blocked) {
            // First insufficient balance attempt - Block for 180 seconds
            self::create([
                'user_id' => $userId,
                'phone_number' => $phoneNumber,
                'payment_method' => 'jazzcash',
                'response_code' => $responseCode,
                'response_desc' => $responseDesc,
                'reason' => 'Insufficient balance - First attempt',
                'block_until' => now()->addSeconds(180),
                'is_permanent' => false,
                'attempt_count' => 1,
                'blocked_attempt_count' => 0
            ]);
            return;
        }

        // If number is currently blocked
        if ($blocked->block_until && $blocked->block_until->isFuture()) {
            $blocked->update([
                'blocked_attempt_count' => $blocked->blocked_attempt_count + 1,
                'reason' => 'Insufficient balance - Attempt while blocked'
            ]);
            return;
        }

        // Handle attempts when not blocked
        $attemptCount = $blocked->attempt_count + 1;
        
        if ($attemptCount == 2) {
            // Second attempt - Block for 1 hour
            $blocked->update([
                'block_until' => now()->addHour(),
                'reason' => 'Insufficient balance - Second attempt',
                'attempt_count' => $attemptCount
            ]);
        } elseif ($attemptCount == 3) {
            // Third attempt - Block for 24 hours
            $blocked->update([
                'block_until' => now()->addHours(24),
                'reason' => 'Insufficient balance - Third attempt',
                'attempt_count' => $attemptCount
            ]);
        } elseif ($attemptCount > 3) {
            // After third attempt - Block for 24 hours for every attempt
            $blocked->update([
                'block_until' => now()->addHours(24),
                'reason' => 'Insufficient balance - Multiple attempts',
                'attempt_count' => $attemptCount
            ]);
        } else {
            // Update attempt count for other cases
            $blocked->update([
                'attempt_count' => $attemptCount
            ]);
        }
    }

    /**
     * Handle manual cancellation or late mpin input blocking for JazzCash
     */
    public static function handleManualCancellation(string $phoneNumber, string $responseCode, string $responseDesc, ?int $userId = null): void
    {
        $blocked = self::where('phone_number', $phoneNumber)
            ->where('payment_method', 'jazzcash')
            ->first();

        if (!$blocked) {
            // First cancellation attempt
            self::create([
                'user_id' => $userId,
                'phone_number' => $phoneNumber,
                'payment_method' => 'jazzcash',
                'response_code' => $responseCode,
                'response_desc' => $responseDesc,
                'reason' => 'Manual cancellation - First attempt',
                'last_cancellation_attempt' => now(),
                'cancellation_count' => 1,
                'block_until' => null,
                'is_permanent' => false
            ]);
            return;
        }

        // Check if last cancellation was today
        if ($blocked->last_cancellation_attempt && 
            $blocked->last_cancellation_attempt->isToday()) {
            
            // Increment cancellation count
            $blocked->cancellation_count++;
            
            // If this is the second cancellation today, block for 1 hour
            if ($blocked->cancellation_count >= 2) {
                $blocked->update([
                    'block_until' => now()->addHour(),
                    'reason' => 'Manual cancellation - Multiple attempts in same day',
                    'last_cancellation_attempt' => now()
                ]);
            } else {
                $blocked->update([
                    'last_cancellation_attempt' => now()
                ]);
            }
        } else {
            // Reset cancellation count if it's a new day
            $blocked->update([
                'cancellation_count' => 1,
                'last_cancellation_attempt' => now()
            ]);
        }
    }

    /**
     * Update or create a blocked number with progressive blocking
     */
    public static function updateOrCreateBlocked(string $phoneNumber, string $paymentMethod, string $responseCode, string $responseDesc, ?int $userId = null): self
    {
        $blocked = self::where('phone_number', $phoneNumber)
            ->where('payment_method', $paymentMethod)
            ->first();

        if (!$blocked) {
            // First attempt
            return self::create([
                'user_id' => $userId,
                'phone_number' => $phoneNumber,
                'payment_method' => $paymentMethod,
                'response_code' => $responseCode,
                'response_desc' => $responseDesc,
                'reason' => $responseDesc,
                'attempt_count' => 1,
                'block_until' => now()->addHours(24),
                'is_permanent' => false
            ]);
        }

        // Increment attempt count
        $attemptCount = $blocked->attempt_count + 1;
        $duration = self::getBlockDuration($attemptCount);

        $blocked->update([
            'attempt_count' => $attemptCount,
            'block_until' => $duration === PHP_INT_MAX ? null : now()->addHours($duration),
            'is_permanent' => $duration === PHP_INT_MAX,
            'response_code' => $responseCode,
            'response_desc' => $responseDesc,
            'reason' => $responseDesc
        ]);

        return $blocked;
    }
} 