<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class KeluhanUtama extends Model
{
    protected $table = 'erm_keluhan_utama';

    protected $fillable = [
        'id',
        'keluhan',
        'spesialisasi_id',
    ];
}
