<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OrderTicket extends Model
{
    protected $fillable = [
        'order_id',
        'selected_numbers',
        'selected_play_types',
        'is_winner',
        'is_claimed',
        'risk_status',
        'risk_reason',
        'risk_hold_at',
        'risk_hold_by',
        'risk_release_at',
        'risk_release_by',
    ];

    protected $casts = [
        'selected_numbers' => 'array',
        'selected_play_types' => 'array',
        'risk_hold_at' => 'datetime',
        'risk_release_at' => 'datetime',
    ];

    public function scopeWithoutRiskHold(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->whereNull('risk_status')
                ->orWhereNotIn('risk_status', ['hold', 'cancelled']);
        });
    }

    public function scopeRiskHeld(Builder $query): Builder
    {
        return $query->where('risk_status', 'hold');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function riskHoldBy()
    {
        return $this->belongsTo(User::class, 'risk_hold_by');
    }

    public function riskReleaseBy()
    {
        return $this->belongsTo(User::class, 'risk_release_by');
    }
}
