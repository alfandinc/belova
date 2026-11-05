<?php

namespace App\Models\ERM;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** One tarif version of a tindakan: applies to SPK with tanggal tindakan on / after berlaku_mulai. Also the change log. */
class JasaMedisTarif extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'erm_jasa_medis_tarif';

    protected $fillable = [
        'kode_tindakan_id', 'berlaku_mulai', 'tarif_dokter', 'tarif_petugas', 'tarif_dokter_lama', 'tarif_petugas_lama',
        'keterangan', 'sumber', 'spk_diperbarui', 'user_id',
    ];

    protected $casts = [
        'berlaku_mulai' => 'date',
        'tarif_dokter' => 'float',
        'tarif_petugas' => 'float',
        'tarif_dokter_lama' => 'float',
        'tarif_petugas_lama' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tarif(string $tipe): float
    {
        return $tipe === SpkPetugas::TIPE_DOKTER ? (float) $this->tarif_dokter : (float) $this->tarif_petugas;
    }
}
