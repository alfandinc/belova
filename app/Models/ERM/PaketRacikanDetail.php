<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaketRacikanDetail extends Model
{
    use HasFactory;

    protected $table = 'erm_paket_racikan_detail';

    protected $fillable = [
        'paket_racikan_id',
        'obat_id',
        'dosis'
    ];

    public function paketRacikan()
    {
        return $this->belongsTo(PaketRacikan::class);
    }

    public function obat()
    {
        return $this->belongsTo(Obat::class);
    }
}
