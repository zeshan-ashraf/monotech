<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settlement extends Model
{
    use HasFactory;
    
    protected $table = 'settlements';
 
    protected $fillable =[
        'date',
        'user_id',
        'opening_bal',
        'jc_payin',
        'ep_payin',
        'jc_payin_fee',
        'ep_payin_fee',
        'payin_bal',
        'jc_payout',
        'ep_payout',
        'jc_payout_fee',
        'ep_payout_fee',
        'usdt',
        'settled',
        'closing_bal',
    ];
    
    protected $casts = [
        'date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
