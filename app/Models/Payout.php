<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    use HasFactory;

    protected $table = 'payouts';

    protected $fillable = [
        'user_id',
        'orderId',
        'code',
        'message',
        'transaction_reference',
        'amount',
        'fee',
        'phone',
        'transaction_type',
        'status',
        'url',
        'request_detail',
        'transaction_id',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
