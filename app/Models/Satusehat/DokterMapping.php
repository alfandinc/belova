<?php

namespace App\Models\Satusehat;

use Illuminate\Database\Eloquent\Model;

class DokterMapping extends Model
{
    protected $table = 'satusehat_dokter_mappings';

    protected $fillable = [
        'dokter_id',
        'mapping_code',
    ];

    public function dokter()
    {
        return $this->belongsTo(\App\Models\ERM\Dokter::class, 'dokter_id');
    }
}
