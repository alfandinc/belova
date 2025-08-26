<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class InformConsent extends Model
{
    protected $table = 'erm_inform_consent';
    protected $fillable = [
        'visitation_id',
        'tindakan_id',
        'paket_id',
        'file_path',
        'before_image_path',
        'after_image_path',
        'riwayat_tindakan_id',
        'allow_post',
    ];

    public function tindakan()
    {
        return $this->belongsTo(Tindakan::class);
    }

    public function visitation()
    {
        return $this->belongsTo(Visitation::class);
    }

    public function riwayatTindakan()
    {
        return $this->belongsTo(RiwayatTindakan::class, 'riwayat_tindakan_id');
    }
}
