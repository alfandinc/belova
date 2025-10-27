<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class HasilSkincheck extends Model
{
    protected $table = 'erm_hasil_skinchecks';

    protected $fillable = [
        'visitation_id',
        'pasien_id',
        'qr_image',
        'url',
        'decoded_text',
    ];

    public function visitation()
    {
        return $this->belongsTo(Visitation::class, 'visitation_id');
    }

    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'pasien_id');
    }
}
