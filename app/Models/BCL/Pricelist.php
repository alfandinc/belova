<?php

namespace App\Models\BCL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pricelist extends Model
{
    use HasFactory;
    protected $fillable = [
        'price',
        'jangka_waktu',
        'jangka_sewa',
        'bonus_waktu',
        'bonus_sewa',
        'room_category'
    ];
    protected $table = 'bcl_pricelist';
}
