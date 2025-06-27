<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class Spk extends Model
{
    protected $table = 'erm_spk';
    
    protected $fillable = [
        'visitation_id',
        'pasien_id',
        'tindakan_id',
        'dokter_id',
        'tanggal_tindakan'
    ];

    protected $casts = [
        'tanggal_tindakan' => 'date'
    ];

    public function visitation()
    {
        return $this->belongsTo(Visitation::class, 'visitation_id');
    }

    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'pasien_id');
    }

    public function tindakan()
    {
        return $this->belongsTo(Tindakan::class, 'tindakan_id');
    }

    public function dokter()
    {
        return $this->belongsTo(Dokter::class, 'dokter_id');
    }

    public function details()
    {
        return $this->hasMany(SpkDetail::class, 'spk_id');
    }
}
