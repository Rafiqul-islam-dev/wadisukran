<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'description', 'status', 'draw_type'];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
