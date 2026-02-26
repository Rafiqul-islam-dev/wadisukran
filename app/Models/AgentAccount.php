<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentAccount extends Model
{
    protected $fillable = ['user_id', 'type', 'transaction_type', 'amount', 'old_due', 'is_checked', 'created_by', 'order_id', 'payment_type', 'description', 'created_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
