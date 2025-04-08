<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class PenyakitDalam extends Model
{
    protected $table = 'erm_penyakit_dalam';

    protected $fillable = [
        'visitation_id',
        'tekanan_darah',
        'suhu',
        'berat_badan',
        'tinggi_badan',
    ];

    public function visitation()
    {
        return $this->belongsTo(Visitation::class);
    }
}
