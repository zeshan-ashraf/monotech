<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupTransaction extends Model
{
    use HasFactory;

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
        'src',
        'url',
        'reverse_requested_at',
    ];
    
    public $timestamps = true;
    
    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}