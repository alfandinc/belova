<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PengajuanGantiShift extends Model
{
    use HasFactory;

    protected $table = 'hrd_pengajuan_ganti_shift';

    protected $fillable = [
        'employee_id',
        'tanggal_shift',
        'shift_lama_id',
        'shift_baru_id',
        'alasan',
        'status_manager',
        'notes_manager',
        'tanggal_persetujuan_manager',
        'status_hrd',
        'notes_hrd',
        'tanggal_persetujuan_hrd'
    ];

    protected $casts = [
        'tanggal_shift' => 'date',
        'tanggal_persetujuan_manager' => 'datetime',
        'tanggal_persetujuan_hrd' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function shiftLama()
    {
        return $this->belongsTo(Shift::class, 'shift_lama_id');
    }

    public function shiftBaru()
    {
        return $this->belongsTo(Shift::class, 'shift_baru_id');
    }
}
