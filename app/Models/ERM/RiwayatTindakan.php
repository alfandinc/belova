<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class RiwayatTindakan extends Model
{
    protected $table = 'erm_riwayat_tindakan';
    
    protected $fillable = [
        'visitation_id',
        'tanggal_tindakan',
        'tindakan_id',
        'paket_tindakan_id'
    ];

    protected $casts = [
        'tanggal_tindakan' => 'date'
    ];

    public function visitation()
    {
        return $this->belongsTo(Visitation::class, 'visitation_id');
    }

    public function tindakan()
    {
        return $this->belongsTo(Tindakan::class, 'tindakan_id');
    }

    public function paketTindakan()
    {
        return $this->belongsTo(PaketTindakan::class, 'paket_tindakan_id');
    }

    public function informConsent()
    {
        return $this->hasOne(InformConsent::class, 'riwayat_tindakan_id');
    }

    public function spk()
    {
        return $this->hasOne(Spk::class, 'riwayat_tindakan_id');
    }
}
