<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class MultiVisitUsage extends Model
{
    protected $table = 'erm_multi_visit_usages';

    protected $fillable = [
        'pasien_id',
        'tindakan_id',
        'first_visitation_id',
        'total',
        'used'
    ];

    public function pasien()
    {
        return $this->belongsTo(\App\Models\Pasien::class, 'pasien_id');
    }

    public function tindakan()
    {
        return $this->belongsTo(Tindakan::class, 'tindakan_id');
    }
}
