<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Area\Village;
use App\Models\ERM\KelasPasien;

class Pasien extends Model
{
    use HasFactory;

    // Tambahkan properti berikut:
    protected $keyType = 'string';
    public $incrementing = false;

    protected $table = 'erm_pasiens';
    protected $fillable = [
        'id',
        'nik',
        'nama',
        'tanggal_lahir',
        'gender',
        'agama',
        'marital_status',
        'pendidikan',
        'pekerjaan',
        'gol_darah',
        'notes',
        'alamat',
        'village_id',
        'no_hp',
        'no_hp2',
        'email',
        'instagram',
        'user_id', // tambahkan ini
    ];

    public function village()
    {
        return $this->belongsTo(Village::class, 'village_id');
    }

    public function suratIstirahats()
    {
        return $this->hasMany(SuratIstirahat::class);
    }
}
