<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected $guarded = ['*'];

    protected $table = 'permissions';
    protected $guard_name = 'user';

    protected $fillable = [
        'name', 'key', 'parent_id', 'guard_name',
    ];

    public function childs()
    {
        return $this->hasMany('App\Models\Permission', 'parent_id', 'id');
    }
}
