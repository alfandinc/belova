<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class InformConsent extends Model
{
    protected $table = 'erm_inform_consent';
    protected $fillable = [
        'visitation_id',
        'tindakan_id',
        'paket_id',
        'file_path',
    ];

    public function tindakan()
    {
        return $this->belongsTo(Tindakan::class);
    }

    public function visitation()
    {
        return $this->belongsTo(Visitation::class);
    }
}
