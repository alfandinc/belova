<?php

namespace App\Models\Satusehat;

use Illuminate\Database\Eloquent\Model;

class Medication extends Model
{
    protected $table = 'satusehat_medications';

    protected $fillable = [
        'visitation_id', 'pasien_id', 'klinik_id', 'satusehat_medication_id', 'obat_list', 'payload', 'raw_response'
    ];

    protected $casts = [
        'obat_list' => 'array'
    ];
}
