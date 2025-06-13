<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class EdukasiObat extends Model
{
    protected $table = 'erm_edukasi_obat';

    protected $fillable = [
        'visitation_id',
        'simpan_etiket_label',
        'simpan_suhu_kulkas',
        'simpan_tempat_kering',
        'hindarkan_jangkauan_anak',
        'insulin_brosur',
        'inhalasi_brosur',
        'apoteker_id',
        'total_pembayaran',
    ];

    protected $casts = [
        'simpan_etiket_label' => 'boolean',
        'simpan_suhu_kulkas' => 'boolean',
        'simpan_tempat_kering' => 'boolean',
        'hindarkan_jangkauan_anak' => 'boolean',
        'total_pembayaran' => 'decimal:2',
    ];

    public function visitation()
    {
        return $this->belongsTo(Visitation::class, 'visitation_id');
    }

    public function apoteker()
    {
        return $this->belongsTo(\App\Models\User::class, 'apoteker_id');
    }
}
