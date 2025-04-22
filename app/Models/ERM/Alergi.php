<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\ERM\ZatAktif;
use App\Models\ERM\Pasien;

class Alergi extends Model
{
    use HasFactory;

    protected $table = 'erm_alergi';

    protected $fillable = [
        'pasien_id',
        'status',
        'katakunci',
        'zataktif_id',
        'verifikasi_status',
        'varifikator_id',
        'user_id',
    ];

    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'pasien_id');
    }

    public function zataktif()
    {
        return $this->belongsTo(ZatAktif::class, 'zataktif_id');
    }

    public function varifikator()
    {
        return $this->belongsTo(User::class, 'varifikator_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
