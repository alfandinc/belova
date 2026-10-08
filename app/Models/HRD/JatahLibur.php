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
        'jatah_cuti_tahunan',
        'jatah_ganti_libur'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class)->withInactive();
    }

    /**
     * +1 / -1 jatah ganti libur (never below 0). Must run inside a transaction.
     */
    public static function adjustGantiLibur($employeeId, int $delta): void
    {
        static::firstOrCreate(['employee_id' => $employeeId], ['jatah_cuti_tahunan' => 0, 'jatah_ganti_libur' => 0]);
        $jatah = static::where('employee_id', $employeeId)->lockForUpdate()->first();
        $jatah->jatah_ganti_libur = max(0, (int) $jatah->jatah_ganti_libur + $delta);
        $jatah->save();
    }
}
