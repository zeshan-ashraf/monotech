<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurplusAmount extends Model
{
    use HasFactory;
    
    protected $table = 'surplus_amounts';
    
    public $timestamps = false;
    
    protected $fillable =[
        'easypaisa',
        'jazzcash',
        'temp_amount',
    ];
}
