<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Gedung extends Model
{
    use HasFactory;

    protected $table = 'inv_gedung';
    protected $fillable = [
        'name',
        'address',
    ];
    
    public function ruangans()
    {
        return $this->hasMany(Ruangan::class, 'gedung_id');
    }
    
    public function pembelianBarangs()
    {
        return $this->hasMany(PembelianBarang::class, 'gedung_id');
    }
}
