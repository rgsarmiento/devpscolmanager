<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FolioRate extends Model
{
    protected $guarded = [];

    protected $casts = [
        'min_folios' => 'integer',
        'max_folios' => 'integer',
        'price' => 'decimal:2',
    ];
}
