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

    /**
     * Obats related to this principal
     */
    public function obats()
    {
        return $this->belongsToMany(Obat::class, 'erm_obat_principal', 'principal_id', 'obat_id');
    }
}
