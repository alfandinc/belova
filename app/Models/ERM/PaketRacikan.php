<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaketRacikan extends Model
{
    use HasFactory;

    protected $table = 'erm_paket_racikan';

    protected $fillable = [
        'nama_paket',
        'deskripsi',
        'wadah_id',
        'bungkus_default',
        'aturan_pakai_default',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function wadah()
    {
        return $this->belongsTo(WadahObat::class, 'wadah_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function details()
    {
        return $this->hasMany(PaketRacikanDetail::class);
    }
}
