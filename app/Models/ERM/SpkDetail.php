<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class SpkDetail extends Model
{
    protected $table = 'erm_spk_details';
    
    protected $fillable = [
        'spk_id',
        'sop_id',
        'penanggung_jawab',
        'sbk',
        'sba',
        'sdc',
        'sdk',
        'sdl',
        'waktu_mulai',
        'waktu_selesai',
        'notes'
    ];

    protected $casts = [
        'sbk' => 'boolean',
        'sba' => 'boolean',
        'sdc' => 'boolean',
        'sdk' => 'boolean',
        'sdl' => 'boolean',
    ];

    public function spk()
    {
        return $this->belongsTo(Spk::class, 'spk_id');
    }

    public function sop()
    {
        return $this->belongsTo(Sop::class, 'sop_id');
    }
}
