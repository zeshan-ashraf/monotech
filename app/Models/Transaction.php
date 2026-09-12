<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class Transaction extends Model
{
    use HasFactory;

    public const CRON_STATUS_AVAILABLE = 0;

    public const CRON_STATUS_IN_PROGRESS = 1;

    public const CRON_STATUS_DONE = 2;

    protected $fillable = [
        'phone',
        'orderId',
        'user_id',
        'amount',
        'txn_ref_no',
        'txn_type',
        'transactionId',
        'pp_code',
        'pp_message',
        'status',
        'cron_status',
        'cron_claim_token',
        'cron_claimed_at',
        'src',
        'url',
        'callback_sent',
        'callback_response',
        'callback_sent_at',
        'callback_response_at',
        'reverse_requested_at',
    ];

    protected $casts = [
        'cron_claimed_at' => 'datetime',
    ];

    /**
     * Get validation rules for creating a transaction
     */
    public static function getValidationRules($ignoreId = null)
    {
        $rules = [
            'phone' => 'required|string|max:191',
            'orderId' => [
                'required',
                'string',
                'max:50',
                Rule::unique('transactions')->ignore($ignoreId)
            ],
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'txn_ref_no' => 'required|string|max:191',
            'txn_type' => 'nullable|string|max:191',
            'transactionId' => 'nullable|string|max:191',
            'pp_code' => 'nullable|string|max:191',
            'pp_message' => 'nullable|string',
            'status' => 'required|string|max:191',
            'url' => 'nullable|string|max:255',
        ];

        return $rules;
    }

    /**
     * Get validation messages
     */
    public static function getValidationMessages()
    {
        return [
            'orderId.unique' => 'This order ID has already been used.',
            'user_id.exists' => 'The selected user is invalid.',
            'amount.min' => 'Amount must be greater than 0.',
        ];
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
