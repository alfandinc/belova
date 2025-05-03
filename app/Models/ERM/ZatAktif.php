<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ERM\Alergi;

class ZatAktif extends Model
{
    use HasFactory;

    protected $table = 'erm_zataktif';

    protected $fillable = [
        'nama',
    ];

    // Relasi ke tabel erm_alergi
    public function alergi()
    {
        return $this->hasMany(Alergi::class, 'zataktif_id');
    }

    public function obats()
    {
        return $this->belongsToMany(Obat::class, 'erm_kandungan_obat');
    }
}
