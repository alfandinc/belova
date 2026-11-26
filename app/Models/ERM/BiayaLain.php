<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

/**
 * Master Biaya Lain for Hitung Harga Paket: costs of a tindakan that are not BHP, jasa medis or alat
 * (linen, sterilisasi, listrik, ruangan, limbah medis, ...). nominal is the default Rp per tindakan;
 * each paket may use its own nominal (erm_tindakan_biaya_lain.nominal).
 */
class BiayaLain extends Model
{
    protected $table = 'erm_biaya_lain';

    protected $fillable = ['nama', 'nominal', 'keterangan', 'is_active', 'updated_by'];

    protected $casts = [
        'nominal' => 'float',
        'is_active' => 'boolean',
    ];

    public function tindakans()
    {
        return $this->belongsToMany(Tindakan::class, 'erm_tindakan_biaya_lain', 'biaya_lain_id', 'tindakan_id')
            ->withPivot('nominal')
            ->withTimestamps();
    }
}
