<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable , HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'visible_password',
        'payin_fee',
        'payout_fee',
        'per_payin_fee',
        'per_payout_fee',
        'role',
        'user_role',
        'payout_jc_api',
        'payout_ep_api',
        'jc_payin_limit',
        'ep_payin_limit',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];


    public function setting()
    {
        return $this->hasOne(Setting::class);
    }

    /**
     * Get the blocked numbers for the user
     */
    public function blockedNumbers()
    {
        return $this->hasMany(BlockedNumber::class);
    }

    /**
     * Get the settlements for the user
     */
    public function settlements()
    {
        return $this->hasMany(Settlement::class);
    }

    /**
     * Get all active users who have settlements
     */
    public static function getActiveSettlementUsers()
    {
        //for admin sub dropdown menu for settlement
        return self::whereHas('settlements')
            ->where('user_role', 'Client')
            ->where('active', 1)
            ->where('email', 'not like', '%test@%')
            ->select('id', 'name', 'user_role')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get settlement users for sidebar (excluding Super Admin)
     */
    public static function getSettlementUsersForSidebar()
    {
        return self::whereHas('settlements')
            ->where('user_role', 'Client')
            ->where('active', 1)
            ->where('email', 'not like', '%test@%')
            ->select('id', 'name', 'user_role')
            ->orderBy('name')
            ->get();
    }
}
