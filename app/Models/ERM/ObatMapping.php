<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class ObatMapping extends Model
{
    protected $table = 'erm_obat_mappings';

    protected $fillable = [
        'visitation_metode_bayar_id',
        'obat_metode_bayar_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
