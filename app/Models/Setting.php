<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;
    
    protected $table = 'settings';
    
    public $timestamps = false;
    
    protected $fillable =[
        'easypaisa',
        'jazzcash',
        'payout_balance',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
