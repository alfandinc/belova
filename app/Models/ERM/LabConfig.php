<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class LabConfig extends Model
{
    protected $table = 'erm_lab_configs';
    protected $fillable = ['dokter_id'];

    public function dokter()
    {
        return $this->belongsTo(\App\Models\ERM\Dokter::class, 'dokter_id');
    }
}
