<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Model;

class PrInsentifOmset extends Model
{
    protected $table = 'pr_insentif_omset';
    protected $fillable = [
        'nama_penghasil',
        'omset_min',
        'omset_max',
        'insentif_normal',
        'insentif_up'
    ];
}
