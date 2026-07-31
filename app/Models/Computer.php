<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Computer extends Model
{
    protected $guarded = [];

    protected $casts = [
        'expiration_date' => 'date',
        'is_active' => 'boolean',
    ];

    protected $appends = ['dias_restantes'];

    public function client(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function licenseTransactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LicenseTransaction::class);
    }

    public function getDiasRestantesAttribute()
    {
        if (!$this->expiration_date) return null;
        return max(0, now()->startOfDay()->diffInDays($this->expiration_date, false));
    }
}
