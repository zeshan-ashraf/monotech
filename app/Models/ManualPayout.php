<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualPayout extends Model
{
    use HasFactory;

    protected $table = 'maual_payouts';

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
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
