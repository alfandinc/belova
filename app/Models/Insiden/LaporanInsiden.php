<?php

namespace App\Models\Insiden;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanInsiden extends Model
{
    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'Dibuat',
    ];
    use HasFactory;

    protected $table = 'laporan_insiden';

    protected $fillable = [
        'pasien_id',
        'penanggung_biaya',
        'tanggal_masuk',
        'tanggal_insiden',
        'insiden',
        'kronologi_insiden',
        'jenis_insiden',
        'pertama_lapor',
        'insiden_pada',
        'jenis_pasien',
        'lokasi_insiden',
        'spesialisasi_id',
        'unit_penyebab',
        'akibat_insiden',
        'tindakan_dilakukan',
        'tindakan_oleh',
        'pernah_terjadi',
        'langkah_diambil',
        'pencegahan',
        'pembuat_laporan',
        'penerima_laporan',
        'tanggal_lapor',
        'tanggal_diterima',
        'grading_resiko',
        'status',
    ];

    // Relationships
    public function pasien()
    {
        return $this->belongsTo(\App\Models\ERM\Pasien::class, 'pasien_id');
    }

    public function spesialisasi()
    {
        return $this->belongsTo(\App\Models\ERM\Spesialisasi::class, 'spesialisasi_id');
    }

    public function unitPenyebab()
    {
        return $this->belongsTo(\App\Models\HRD\Division::class, 'unit_penyebab');
    }

    public function pembuatLaporan()
    {
        return $this->belongsTo(\App\Models\User::class, 'pembuat_laporan');
    }

    public function penerimaLaporan()
    {
        return $this->belongsTo(\App\Models\User::class, 'penerima_laporan');
    }
}
