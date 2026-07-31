<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $guarded = [];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function computers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Computer::class);
    }

    public function clientServices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ClientService::class);
    }

    public function resolutions()
    {
        return $this->hasMany(ClientResolution::class);
    }

    public function invoicingInfo(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(InvoicingInfo::class);
    }

    public function distributor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }

    public function licenseTransactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LicenseTransaction::class);
    }
}
