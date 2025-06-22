<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResepDetail extends Model
{
    use HasFactory;
    protected $table = 'erm_resepdetail';
    protected $fillable = [
        'visitation_id',
        'no_resep',
        'catatan_dokter',
    ];
}
