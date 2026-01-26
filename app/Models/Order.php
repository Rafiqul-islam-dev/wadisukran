<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $appends = ['qr_url'];

    protected $fillable = [
        'user_id',
        'product_id',
        'game_cards',
        'quantity',
        'total_price',
        'invoice_no',
        'sales_date',
        'draw_number',
        'commission',
        'vat',
        'commission_percentage',
        'vat_percentage',
        'is_winner',
        'is_claimed',
        'qr_code'
    ];

    protected $casts = [
        'game_cards' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }


    public  function tickets()
    {
        return $this->hasMany(OrderTicket::class, 'order_id', 'id');
    }

    public function getQrUrlAttribute(): ?string
    {
        if (!$this->qr_code) return null;

        return static_asset($this->qr_code);
    }
}
