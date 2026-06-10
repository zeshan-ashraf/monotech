<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempAmountPayout extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable =[
        'jc_amount',
        'ep_amount',
    ];
}
