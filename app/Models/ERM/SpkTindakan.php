<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class SpkTindakan extends Model
{
    protected $table = 'erm_spk_tindakan';

    protected $fillable = [
        'riwayat_tindakan_id',
        'tanggal_tindakan',
        'waktu_mulai',
        'waktu_selesai',
        'status',
    ];

    protected $casts = [
        'tanggal_tindakan' => 'date',
        'waktu_mulai' => 'datetime:H:i',
        'waktu_selesai' => 'datetime:H:i',
    ];

    /**
     * Relationship to RiwayatTindakan
     */
    public function riwayatTindakan()
    {
        return $this->belongsTo(RiwayatTindakan::class, 'riwayat_tindakan_id');
    }

    /**
     * Relationship to Visitation through RiwayatTindakan
     */
    public function visitation()
    {
        return $this->hasOneThrough(Visitation::class, RiwayatTindakan::class, 'id', 'id', 'riwayat_tindakan_id', 'visitation_id');
    }

    /**
     * Relationship to Pasien through RiwayatTindakan and Visitation
     */
    public function pasien()
    {
        return $this->hasOneThrough(Pasien::class, Visitation::class, 'id', 'id', 'riwayat_tindakan_id', 'pasien_id')
            ->through(RiwayatTindakan::class);
    }

    /**
     * Relationship to Tindakan through RiwayatTindakan
     */
    public function tindakan()
    {
        return $this->hasOneThrough(Tindakan::class, RiwayatTindakan::class, 'id', 'id', 'riwayat_tindakan_id', 'tindakan_id');
    }

    /**
     * Relationship to Dokter through RiwayatTindakan and Visitation
     */
    public function dokter()
    {
        return $this->hasOneThrough(Dokter::class, Visitation::class, 'id', 'id', 'riwayat_tindakan_id', 'dokter_id')
            ->through(RiwayatTindakan::class);
    }

    /**
     * Relationship to SpkTindakanItems
     */
    public function items()
    {
        return $this->hasMany(SpkTindakanItem::class, 'spk_tindakan_id');
    }
}
