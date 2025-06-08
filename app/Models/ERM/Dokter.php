<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class Dokter extends Model
{
    protected $table = 'erm_dokters';

    protected $fillable = ['user_id', 'spesialisasi_id', 'klinik_id', 'sip', 'ttd'];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function spesialisasi()
    {
        return $this->belongsTo(Spesialisasi::class);
    }
    public function klinik()
    {
        return $this->belongsTo(Klinik::class);
    }
}
