<?php

namespace App\Models\Workdoc;

use Illuminate\Database\Eloquent\Model;

class SuratKeluar extends Model
{
    protected $table = 'workdoc_surat_keluars';

    protected $fillable = [
        'no_surat',
        'instansi',
        'jenis_surat',
        'deskripsi',
        'status',
        'jenis_tujuan',
        'kepada',
        'diajukan_for',
        'created_by',
        'tgl_dibuat',
        'tgl_diajukan',
        'tgl_disetujui',
        'disetujui_by',
        'lampiran',
    ];
}
