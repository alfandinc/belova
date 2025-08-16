<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Cppt extends Model
{
    protected $table = 'erm_cppt';

    protected $fillable = [
        'visitation_id',
        'user_id',
        'jenis_dokumen',
        'jenis_kunjungan',
        's',
        'o',
        'a',
        'p',
        'instruksi',
        'icd_10',
        'dibaca',
        'waktu_baca',
        'handover',
        'perawat_handover',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reader()
    {
        return $this->belongsTo(User::class, 'dibaca');
    }
}
