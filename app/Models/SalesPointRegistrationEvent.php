<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Append-only audit trail entry of a Sales Point registration. */
class SalesPointRegistrationEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['registration_id', 'user_id', 'action', 'meta', 'ip_address'];

    protected $casts = ['meta' => 'array', 'created_at' => 'datetime'];

    public function registration()
    {
        return $this->belongsTo(SalesPointRegistration::class, 'registration_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
