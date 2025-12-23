<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KunjunganMarketing extends Model
{
    use HasFactory;

    protected $table = 'marketing_kunjungans';

    protected $fillable = [
        'instansi_tujuan',
        'tanggal_kunjungan',
        'pic',
        'no_hp',
        'instansi',
        'status',
        'bukti_kunjungan',
        'hasil_kunjungan',
    ];
}
