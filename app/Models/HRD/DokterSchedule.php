<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Model;

class DokterSchedule extends Model
{
    protected $table = 'hrd_dokter_schedules';

    protected $fillable = [
        'dokter_id',
        'date',
        'jam_mulai',
        'jam_selesai',
    ];

    public function dokter()
    {
        return $this->belongsTo(\App\Models\ERM\Dokter::class);
    }
}
