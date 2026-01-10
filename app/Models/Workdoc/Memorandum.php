<?php

namespace App\Models\Workdoc;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Memorandum extends Model
{
    use HasFactory;

    protected $table = 'workdoc_memorandums';

    protected $fillable = [
        'tanggal',
        'nomor_memo',
        'perihal',
        'dari_division_id',
        'kepada',
        'isi',
        'klinik_id',
        'user_id',
        'status',
        'dokumen_path',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function division()
    {
        return $this->belongsTo(\App\Models\HRD\Division::class, 'dari_division_id');
    }

    public function klinik()
    {
        return $this->belongsTo(\App\Models\ERM\Klinik::class, 'klinik_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
