<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class AsesmenPerawat extends Model
{
    protected $table = 'erm_asesmen_perawats';

    protected $fillable = [
        'visitation_id',
        'keluhan_utama',
        'alergi',
    ];

    public function visitation()
    {
        return $this->belongsTo(Visitation::class);
    }
}
