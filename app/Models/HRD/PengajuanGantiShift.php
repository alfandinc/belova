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
        'tanggal_persetujuan_hrd',
        'is_tukar_shift',
        'target_employee_id',
        'target_employee_approval_status',
        'target_employee_approval_date',
        'target_employee_notes'
    ];

    protected $casts = [
        'tanggal_shift' => 'date',
        'tanggal_persetujuan_manager' => 'datetime',
        'tanggal_persetujuan_hrd' => 'datetime',
        'target_employee_approval_date' => 'datetime',
        'is_tukar_shift' => 'boolean',
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

    public function targetEmployee()
    {
        return $this->belongsTo(Employee::class, 'target_employee_id');
    }

    public function isFullyApproved()
    {
        if ($this->is_tukar_shift) {
            return $this->status_manager === 'disetujui' && 
                   $this->status_hrd === 'disetujui' && 
                   $this->target_employee_approval_status === 'disetujui';
        }
        
        return $this->status_manager === 'disetujui' && $this->status_hrd === 'disetujui';
    }
}
