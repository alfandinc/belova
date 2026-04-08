<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Dokter extends Model
{
    protected $table = 'erm_dokters';

    protected $fillable = [
        'user_id',
        'spesialisasi_id',
        'klinik_id',
        'sip',
        'ttd',
        'due_date_sip',
        'photo',
        'nik',
        'alamat',
        'no_hp',
        'status',
        'str',
        'due_date_str',
    ];

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

    public function kliniks(): BelongsToMany
    {
        return $this->belongsToMany(Klinik::class, 'erm_dokter_kliniks', 'dokter_id', 'klinik_id')
            ->withTimestamps();
    }

    public function mapping()
    {
        return $this->hasOne(\App\Models\Satusehat\DokterMapping::class, 'dokter_id');
    }
}
