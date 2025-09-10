<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class SpkTindakanItem extends Model
{
    protected $table = 'erm_spk_tindakan_items';

    protected $fillable = [
        'spk_tindakan_id',
        'kode_tindakan_id',
        'penanggung_jawab',
        'sbk',
        'sba',
        'sdc',
        'sdk',
        'sdl',
        'notes',
    ];

    /**
     * Relationship to SpkTindakan
     */
    public function spkTindakan()
    {
        return $this->belongsTo(SpkTindakan::class, 'spk_tindakan_id');
    }

    /**
     * Relationship to KodeTindakan
     */
    public function kodeTindakan()
    {
        return $this->belongsTo(KodeTindakan::class, 'kode_tindakan_id');
    }
}
