<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResepDokter extends Model
{
    protected $table = 'erm_resepdokter';

    protected $fillable = [
        'tanggal_input',
        'visitation_id',
        'obat_id',
        'jumlah',
        'dosis',
        'bungkus',
        'racikan_ke',
        'aturan_pakai',
        'wadah',
    ];

    // Relasi ke Obat
    public function obat()
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    // Relasi ke Visitation
    public function visitation()
    {
        return $this->belongsTo(Visitation::class, 'visitation_id');
    }

    // public $timestamps = false;
}
