<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatatanKeluhan extends Model
{
    use HasFactory;
    protected $table = 'marketing_catatan_keluhan';
    protected $fillable = [
        'perusahaan',
        'pasien_id',
        'visit_date',
        'unit',
        'kategori',
        'keluhan',
        'penyelesaian',
        'rencana_perbaikan',
        'deadline_perbaikan',
        'status',
        'bukti',
    ];

    public function pasien()
    {
        return $this->belongsTo(\App\Models\ERM\Pasien::class, 'pasien_id');
    }
}
