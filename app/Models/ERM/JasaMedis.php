<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

/** Tarif jasa medis of one tindakan (erm_kode_tindakan): one amount for the dokter, one for employees. */
class JasaMedis extends Model
{
    protected $table = 'erm_jasa_medis';

    protected $fillable = ['kode_tindakan_id', 'tarif_dokter', 'tarif_employee', 'keterangan', 'updated_by'];

    protected $casts = [
        'tarif_dokter' => 'float',
        'tarif_employee' => 'float',
    ];

    public function kodeTindakan()
    {
        return $this->belongsTo(KodeTindakan::class, 'kode_tindakan_id');
    }

    public function tarif(string $tipe): float
    {
        return $tipe === SpkPetugas::TIPE_DOKTER ? (float) $this->tarif_dokter : (float) $this->tarif_employee;
    }
}
