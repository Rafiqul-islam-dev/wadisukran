<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentBill extends Model
{
    protected $fillable = ['from_date', 'to_date', 'zip', 'created_by'];
    protected $appends = ['zip_link'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getZipLinkAttribute()
    {
        return $this->zip ? static_asset($this->zip) : null;
    }
}
