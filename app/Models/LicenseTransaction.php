<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LicenseTransaction extends Model
{
    protected $guarded = [];

    public function computer()
    {
        return $this->belongsTo(Computer::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function distributor()
    {
        return $this->belongsTo(Distributor::class);
    }

    public function debt()
    {
        return $this->belongsTo(Debt::class);
    }
}
