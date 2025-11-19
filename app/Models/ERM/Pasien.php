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
        'status_pasien',
        'status_akses',
        'status_review',
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

    public function suratMondoks()
    {
        return $this->hasMany(SuratMondok::class);
    }
    
    public function visitations()
    {
        return $this->hasMany(Visitation::class, 'pasien_id');
    }

    /**
     * Pasien merchandise receipts (pivot records)
     */
    public function pasienMerchandises()
    {
        return $this->hasMany(PasienMerchandise::class, 'pasien_id');
    }

    /**
     * Convenience relation to get merchandises through pivot
     */
    public function merchandises()
    {
        return $this->belongsToMany(Merchandise::class, 'erm_pasien_merchandises', 'pasien_id', 'merchandise_id')
                    ->withPivot(['id', 'quantity', 'notes', 'given_by_user_id', 'given_at'])
                    ->withTimestamps();
    }
}
