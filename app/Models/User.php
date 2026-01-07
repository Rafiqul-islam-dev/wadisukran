<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasPermissions, SoftDeletes, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'photo',
        'status',
        'role_id',
        'user_type',
        'join_date'
    ];

    protected $appends = ['avatar', 'is_active'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

   public function agent()
{
    return $this->hasOne(Agent::class, 'user_id');
}



    public function hasPermission($permission)
    {
        if (!$this->role) {
            return false;
        }

        return in_array($permission, $this->role->permissions ?? []);
    }

    public function getIsActiveAttribute(): bool
    {
        return Cache::has('user_active_' . $this->id);
    }

    public function getAvatarAttribute()
    {
        if ($this->photo) {
            return static_asset($this->photo);
        }
        return null;
    }
}
