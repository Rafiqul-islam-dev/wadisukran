<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiskCapGroup extends Model
{
    protected $fillable = [
        'product_id',
        'draw_number',
        'user_id',
        'status',
        'cap_percent',
        'total_sale',
        'commission_percent',
        'commission_amount',
        'net_sale',
        'max_payable_amount',
        'reason',
        'applied_by',
        'released_by',
        'applied_at',
        'released_at',
    ];

    protected $casts = [
        'cap_percent' => 'decimal:2',
        'total_sale' => 'decimal:2',
        'commission_percent' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'net_sale' => 'decimal:2',
        'max_payable_amount' => 'decimal:2',
        'applied_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appliedBy()
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function releasedBy()
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'capped');
    }
}
