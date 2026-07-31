<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientResolution extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'resolution_date' => 'date',
        'date_from' => 'date',
        'date_to' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
