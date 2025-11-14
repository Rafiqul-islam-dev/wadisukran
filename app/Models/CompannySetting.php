<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CompannySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'whatsapp',
        'trn_no',
        'currency',
        'email',
        'website',
        'licence_no',
        'bank_account',
        'details',
        'logo',
    ];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute()
    {
        return $this->logo ? Storage::url($this->logo) : null;
    }
}
