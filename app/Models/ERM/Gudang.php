<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gudang extends Model
{
    use HasFactory;
    protected $table = 'erm_gudang';
    protected $fillable = [
        'nama', 'lokasi'
    ];
}
