<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class AturanPakai extends Model
{
    protected $table = 'erm_aturan_pakai';

    protected $fillable = [
        'template',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
