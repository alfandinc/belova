<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ERM\Obat;

class Supplier extends Model
{
    use HasFactory;

    protected $table = 'erm_supplier';

    protected $fillable = [
        'nama',
    ];

    public function obat()
    {
        return $this->hasMany(Obat::class, 'supplier_id');
    }
}
