<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesPointRegistrationDocument extends Model
{
    protected $fillable = ['registration_id', 'type', 'path', 'original_name', 'mime', 'size'];

    protected $hidden = ['path'];

    public function registration()
    {
        return $this->belongsTo(SalesPointRegistration::class, 'registration_id');
    }
}
