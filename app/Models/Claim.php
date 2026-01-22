<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    protected $fillable = ['user_id', 'win_id', 'invoice_no', 'amount', 'claimed_by'];
}
