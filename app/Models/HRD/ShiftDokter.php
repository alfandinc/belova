<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Model;

class ShiftDokter extends Model
{
    protected $table = 'hrd_shifts_dokter';

    protected $fillable = [
        'dokter_id',
        'jam_mulai',
        'jam_selesai',
    ];

    public function dokter()
    {
        return $this->belongsTo(\App\Models\ERM\Dokter::class);
    }
}
