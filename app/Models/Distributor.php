<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Distributor extends Model
{
    protected $guarded = [];

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function licenseTransactions()
    {
        return $this->hasMany(LicenseTransaction::class);
    }

    public function debts()
    {
        return $this->hasMany(Debt::class);
    }
}
