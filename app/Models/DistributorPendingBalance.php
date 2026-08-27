<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DistributorPendingBalance extends Model
{
    protected $guarded = [];

    protected $casts = [
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function distributor()
    {
        return $this->belongsTo(Distributor::class);
    }
}
