<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'category_id',
        'price',
        'draw_type',
        'draw_date',
        'draw_time',
        'image',
        'pick_number',
        'prize_type',
        'type_number',
        'is_active',
    ];

    protected $casts = [
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
        return $query->where('is_active', true)
            ->whereHas('category', function ($q) {
                $q->where('status', 1);
            });
    }


    // Scope for products by type
    public function scopeByType($query, $type)
    {
        return $query->where('draw_type', $type);
    }

    public function getDrawTimeAttribute($value)
    {
        return $value instanceof Carbon ? $value->format('H:i') : $value;
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function prizes()
    {
        return $this->hasMany(ProductPrize::class);
    }
}
