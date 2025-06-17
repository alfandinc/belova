<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class LabHasil extends Model
{
    protected $table = 'erm_lab_hasil';
    
    protected $fillable = [
        'visitation_id',
        'asal_lab',
        'nama_pemeriksaan',
        'tanggal_pemeriksaan',
        'dokter',
        'catatan',
        'file_path',
        'hasil_detail',
    ];
    
    protected $casts = [
        'tanggal_pemeriksaan' => 'date',
        'hasil_detail' => 'array',
    ];
    
    public function visitation()
    {
        return $this->belongsTo(Visitation::class, 'visitation_id');
    }
}
