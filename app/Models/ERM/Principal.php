<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Principal extends Model
{
    use HasFactory;

    protected $table = 'erm_principals';

    protected $fillable = [
        'nama',
        'alamat',
        'telepon',
        'email',
        'status_aktif',
    ];

    /** Obat from this principal (erm_obat.principal_id, set in Master Obat). */
    public function obats()
    {
        return $this->hasMany(Obat::class, 'principal_id');
    }
}
