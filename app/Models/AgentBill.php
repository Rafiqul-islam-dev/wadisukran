<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentBill extends Model
{
    protected $fillable = ['from_date', 'to_date', 'zip', 'created_by'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
