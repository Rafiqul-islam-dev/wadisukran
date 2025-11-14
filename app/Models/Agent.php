<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Agent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'join_date',
        'address',
        'trn',
        'username',
        'email',
        'photo',
    ];

    protected $dates = ['join_date'];
}
