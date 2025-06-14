<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResepDokter extends Model
{
    protected $table = 'erm_resepdokter';
    public $incrementing = false; // non auto-increment
    protected $keyType = 'string'; // jika ID-nya string (bukan integer)

    protected $fillable = [
        'id',
        'visitation_id',
        'obat_id',
        'jumlah',
        'dosis',
        'bungkus',
        'racikan_ke',
        'aturan_pakai',
        'wadah_id',

        'created_at',
    ];

    // Relasi ke Obat
    public function obat()
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }
    public function wadah()
    {
        return $this->belongsTo(WadahObat::class, 'wadah_id');
    }

    // Relasi ke Visitation
    public function visitation()
    {
        return $this->belongsTo(Visitation::class, 'visitation_id');
    }
}
