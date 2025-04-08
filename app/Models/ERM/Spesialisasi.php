<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spesialisasi extends Model
{
    use HasFactory;

    protected $table = 'erm_spesialisasis';

    protected $fillable = ['nama'];


    public function dokters()
    {
        return $this->hasMany(Dokter::class);
    }
}
