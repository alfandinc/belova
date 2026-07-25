<?php

namespace App\Models\Marketing;

use App\Models\ERM\Klinik;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingEvent extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'marketing_event';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'kode_event',
        'nama_event',
        'deskripsi_event',
        'tanggal_mulai',
        'tanggal_selesai',
        'klinik_id',
        'lokasi',
        'target_market',
        'status',
        'dokumen_proposal',
        'dokumen_laporan',
    ];

    /**
     * The attributes casts.
     *
     * @var array
     */
    protected $casts = [
        'tanggal_mulai' => 'datetime',
        'tanggal_selesai' => 'datetime',
    ];

    public function klinik()
    {
        return $this->belongsTo(Klinik::class, 'klinik_id');
    }

    public function promos()
    {
        return $this->belongsToMany(Promo::class, 'marketing_event_promos', 'event_id', 'promo_id')
            ->withTimestamps();
    }
}
