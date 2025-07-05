<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArcheivePayout extends Model
{
    use HasFactory;

    protected $table = 'archeive_payouts';

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
    
    public $timestamps = true;
    
    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
