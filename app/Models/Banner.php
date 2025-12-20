<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'image_url',
        'status',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the full URL for the banner image.
     */
    public function getImageUrlAttribute($value)
    {
        if ($value) {
            return static_asset($value);
        }
        return null;
    }


    /**
     * Get only active banners.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get only inactive banners.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Search banners by title.
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('title', 'like', "%{$term}%");
    }

    /**
     * Boot method to handle model events.
     */
    protected static function boot()
    {
        parent::boot();

        // Delete image file when banner is permanently deleted
        static::deleting(function ($banner) {
            if ($banner->isForceDeleting() && $banner->image_url) {
                $imagePath = str_replace(Storage::url(''), '', $banner->image_url);
                if (Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                }
            }
        });
    }
}
