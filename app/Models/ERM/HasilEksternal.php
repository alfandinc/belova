<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class HasilEksternal extends Model
{
    protected $table = 'erm_hasil_eksternal';
    
    protected $fillable = [
        'visitation_id',
        'asal_lab',
        'nama_pemeriksaan',
        'tanggal_pemeriksaan',
        'dokter',
        'catatan',
        'file_path',
    ];
    
    protected $casts = [
        'tanggal_pemeriksaan' => 'date',
    ];
    
    public function visitation()
    {
        return $this->belongsTo(Visitation::class, 'visitation_id');
    }
}
