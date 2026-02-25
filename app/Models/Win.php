<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Win extends Model
{
    protected $fillable = ['product_id', 'from_time', 'to_time', 'draw_number', 'win_number', 'draw_time'];

    protected $casts = [
        'win_number' => 'array',
    ];

    public function product(){
        return $this->belongsTo(Product::class);
    }
    public function claims(){
        return $this->hasMany(Claim::class);
    }
}
