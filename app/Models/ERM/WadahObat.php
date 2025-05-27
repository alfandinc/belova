<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WadahObat extends Model
{
    use HasFactory;
    protected $table = 'erm_wadah_obat';

    protected $fillable = ['nama', 'harga'];
}
