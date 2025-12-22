<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPrize extends Model
{
    protected $fillable = ['product_id', 'type', 'name', 'prize'];
}
