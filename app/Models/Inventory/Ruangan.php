<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Ruangan extends Model
{
    use HasFactory;

    protected $table = 'inv_ruangan';
    protected $fillable = [
        'name',
        'gedung_id',
        'description',
    ];
    
    public function gedung()
    {
        return $this->belongsTo(Gedung::class, 'gedung_id');
    }
    
    public function barangs()
    {
        return $this->hasMany(Barang::class, 'ruangan_id');
    }
}
