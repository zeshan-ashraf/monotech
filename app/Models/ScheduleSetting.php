<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleSetting extends Model
{
    use HasFactory;
    
    protected $table = 'schedule_settings';
    
    public $timestamps = false;
    
    protected $fillable =[
        'type',
        'value',
    ];
}
