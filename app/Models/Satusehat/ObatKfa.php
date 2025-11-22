<?php

namespace App\Models\Satusehat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObatKfa extends Model
{
    use HasFactory;

    protected $table = 'satusehat_obat_kfa_mapping';

    protected $fillable = [
        'obat_id',
        'kfa_code',
    ];

    public function obat()
    {
        return $this->belongsTo(\App\Models\ERM\Obat::class, 'obat_id');
    }
}
