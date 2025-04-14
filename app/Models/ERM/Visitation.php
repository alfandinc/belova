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
    public function asesmenPerawat()
    {
        return $this->hasOne(AsesmenPerawat::class);
    }

    public function penyakitDalam()
    {
        return $this->hasOne(PenyakitDalam::class);
    }

    public function diagnosa()
    {
        return $this->hasOne(Diagnosa::class);
    }
}
