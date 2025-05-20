<?php

// App\Models\ERM\SuratIstirahat.php
namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class SuratIstirahat extends Model
{
    protected $table = 'erm_suratistirahat';

    protected $fillable = [
        'pasien_id',
        'dokter_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'jumlah_hari',
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
