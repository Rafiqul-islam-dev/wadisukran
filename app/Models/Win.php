<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Win extends Model
{
    protected $fillable = ['product_id', 'win_date', 'win_time', 'draw_number', 'win_number'];

    protected $casts = [
        'win_number' => 'array',
    ];
}
