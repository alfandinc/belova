<?php

namespace App\Models\ERM;

use App\Models\Finance\Billing;
use Illuminate\Database\Eloquent\Model;

class Tindakan extends Model
{
    /**
     * Many-to-many relationship to KodeTindakan
     */
    public function kodeTindakans()
    {
        return $this->belongsToMany(KodeTindakan::class, 'erm_tindakan_kode_tindakan', 'tindakan_id', 'kode_tindakan_id');
    }
    protected $table = 'erm_tindakan';
    protected $fillable = ['nama', 'deskripsi', 'harga', 'spesialis_id', 'harga_diskon', 'diskon_active', 'is_active', 'harga_3_kali'];
    protected $casts = [
        'is_active' => 'boolean',
        'harga_3_kali' => 'float',
    ];

    public function paketTindakan()
    {
        return $this->belongsToMany(PaketTindakan::class, 'erm_paket_tindakan_detail');
    }

    public function informConsent()
    {
        return $this->hasMany(InformConsent::class);
    }

    public function billing()
    {
        return $this->morphMany(Billing::class, 'billable');
    }
    
    public function spesialis()
    {
        return $this->belongsTo(\App\Models\ERM\Spesialisasi::class, 'spesialis_id');
    }
    
    public function sop()
    {
        return $this->hasMany(\App\Models\ERM\Sop::class, 'tindakan_id');
    }

        /**
         * The obat that belong to the tindakan (bundle).
         */
        public function obats()
        {
            return $this->belongsToMany(Obat::class, 'erm_tindakan_obat', 'tindakan_id', 'obat_id');
        }
}
