<?php

namespace App\Models\Workdoc;

use Illuminate\Database\Eloquent\Model;

class SuratJenis extends Model
{
    protected $table = 'workdoc_surat_jenis';

    protected $fillable = [
        'nama',
        'singkatan',
        'kode',
    ];
}
