<?php

namespace App\Models\Belova;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NgajiNilai extends Model
{
    use HasFactory;

    protected $table = 'ngaji_nilai';

    protected $fillable = [
        'employee_id',
        'test',
        'date',
        'nilai_makhroj',
        'nilai_tajwid',
        'nilai_panjang_pendek',
        'nilai_kelancaran',
        'total_nilai',
        'catatan'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(\App\Models\HRD\Employee::class, 'employee_id');
    }
}
