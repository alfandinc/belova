<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KelasPasien extends Model
{
    use HasFactory;

    protected $table = 'erm_kelas_pasiens';
    protected $fillable = ['name'];

    public function pasiens()
    {
        return $this->hasMany(Pasien::class, 'kelas_pasien_id');
    }
}
