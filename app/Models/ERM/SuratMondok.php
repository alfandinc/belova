<?php

// App\Models\ERM\SuratMondok.php
namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class SuratMondok extends Model
{
    protected $table = 'erm_suratmondok';

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'pasien_id',
        'dokter_id',
        'tujuan_igd',
        'diagnosa',
        'instruksi_terapi',
        'created_at',
        'updated_at',
    ];

    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'pasien_id');
    }
    
    public function dokter()
    {
        return $this->belongsTo(Dokter::class, 'dokter_id');
    }
}
