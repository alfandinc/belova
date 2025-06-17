<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
class RadiologiHasil extends Model
{
    use HasFactory;

    protected $table = 'erm_radiologi_hasil';
    
    protected $fillable = [
        'visitation_id',
        'dokter_pengirim',
        'nama_pemeriksaan',
        'tanggal_pemeriksaan',
        'file_path',
        'deskripsi',
    ];
    
    protected $casts = [
        'tanggal_pemeriksaan' => 'date',
    ];
    
    public function visitation()
    {
        return $this->belongsTo(Visitation::class);
    }
}
