<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JatahLibur extends Model
{
    use HasFactory;

    protected $table = 'hrd_jatah_libur';

    protected $fillable = [
        'employee_id',
        'jatah_libur_tahunan',
        'jatah_ganti_libur'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
