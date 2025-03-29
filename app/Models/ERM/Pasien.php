<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Area\Village;
use App\Models\ERM\KelasPasien;

class Pasien extends Model
{
    use HasFactory;

    protected $table = 'erm_pasiens';
    protected $fillable = [
        'nik',
        'nama',
        'tanggal_lahir',
        'gender',
        'marital_status',
        'pendidikan',
        'agama',
        'pekerjaan',
        'alamat',
        'village_id',
        'kelas_pasien_id',
        'penanggung_jawab',
        'no_hp_penanggung_jawab',
        'notes'
    ];

    public function village()
    {
        return $this->belongsTo(Village::class, 'village_id');
    }

    public function kelasPasien()
    {
        return $this->belongsTo(KelasPasien::class, 'kelas_pasien_id');
    }
}
