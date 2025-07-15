<?php

// App\Models\ERM\SuratDiagnosa.php
namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class SuratDiagnosa extends Model
{
    protected $table = 'erm_suratdiagnosa';

    protected $fillable = [
        'visitation_id',
        'keterangan',
    ];
    
    public function visitation()
    {
        return $this->belongsTo(Visitation::class, 'visitation_id', 'id');
    }
}
