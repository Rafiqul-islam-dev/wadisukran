<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderTicket extends Model
{
    protected $fillable = ['order_id', 'selected_numbers', 'selected_play_types'];

    protected $casts = [
        'selected_numbers' => 'array',
        'selected_play_types' => 'array',
    ];

    public function order(){
        return $this->belongsTo(Order::class);
    }
}
