<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class Diagnosa extends Model
{
    protected $table = 'erm_diagnosas';

    protected $fillable = [
        'visitation_id',
        'diagnosa',
        'tindakan',
    ];

    public function visitation()
    {
        return $this->belongsTo(Visitation::class);
    }
}
