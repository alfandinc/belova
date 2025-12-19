<?php

namespace App\Models\Satusehat;

use Illuminate\Database\Eloquent\Model;

class Encounter extends Model
{
    protected $table = 'satusehat_encounters';

    protected $fillable = [
        'visitation_id', 'pasien_id', 'klinik_id', 'satusehat_encounter_id', 'raw_response', 'status'
    ];
}
