<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    protected $fillable = ['user_id', 'win_id', 'invoice_no', 'amount', 'claimed_by'];

    public function claim_user(){
        return $this->belongsTo(User::class, 'claimed_by', 'id');
    }
}
