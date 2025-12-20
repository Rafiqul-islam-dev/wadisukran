<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'price',
        'draw_date',
        'draw_time',
        'image',
        'type',
        'pick_number',
        'showing_type',
        'type_number',
        'prizes',
        'is_active',
        'is_daily',
    ];

    protected $casts = [
        'prizes' => 'array',
        'price' => 'decimal:2',
        'draw_date' => 'date',
        'draw_time' => 'datetime:H:i',
        'is_active' => 'boolean'
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if ($this->image) {
           return static_asset($this->image);
        }
        return null;
    }

    // Scope for active products
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope for products by type
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function getDrawTimeAttribute($value)
    {
        return $value instanceof Carbon ? $value->format('H:i') : $value;
    }
}
