<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class Visitation extends Model
{
    protected $table = 'erm_visitations';
    public $incrementing = false; // non auto-increment
    protected $keyType = 'string'; // jika ID-nya string (bukan integer)

    protected $fillable = [
        'id',
        'pasien_id',
        'dokter_id',
        'metode_bayar_id',
        'progress',
        'status',
        'no_antrian',
        'tanggal_visitation',
    ];

    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'pasien_id');
    }

    public function metodeBayar()
    {
        return $this->belongsTo(MetodeBayar::class, 'metode_bayar_id');
    }
    public function dokter()
    {
        return $this->belongsTo(Dokter::class, 'dokter_id');
    }
    public function asesmenPerawat()
    {
        return $this->hasOne(AsesmenPerawat::class);
    }

    public function asesmenDalam()
    {
        return $this->hasOne(AsesmenDalam::class);
    }

    public function asesmenPenunjang()
    {
        return $this->hasOne(AsesmenPenunjang::class);
    }
}
