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

    /**
     * Dosis as a plain number in the obat's satuan: "250", "250.50", "250 mg" or "0,5"
     * => "250", "250.5", "250", "0.5" (older rows may still carry a unit).
     */
    public static function normalizeDosis($dosis): string
    {
        if (!preg_match('/\d+(?:[.,]\d+)?/', (string) $dosis, $m)) {
            return trim((string) $dosis);
        }
        $number = (float) str_replace(',', '.', $m[0]);

        return rtrim(rtrim(number_format($number, 4, '.', ''), '0'), '.');
    }

    public function paketRacikan()
    {
        return $this->belongsTo(PaketRacikan::class);
    }

    public function obat()
    {
        return $this->belongsTo(Obat::class);
    }
}
