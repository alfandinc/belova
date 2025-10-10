<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pemasok extends Model
{
    use HasFactory;
    protected $table = 'erm_pemasok';
    protected $fillable = [
        'nama', 'alamat', 'telepon', 'email'
    ];

    public function fakturBeli()
    {
        return $this->hasMany(\App\Models\ERM\FakturBeli::class, 'pemasok_id');
    }
}
