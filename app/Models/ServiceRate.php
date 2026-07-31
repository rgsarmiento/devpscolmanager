<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRate extends Model
{
    protected $guarded = [];

    protected $casts = [
        'annual_price' => 'decimal:2',
    ];
}
