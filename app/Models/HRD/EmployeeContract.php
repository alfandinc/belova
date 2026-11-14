<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Model;

/**
 * One contract period of an employee. Only one is 'active' at a time; a contract past its end date stays
 * 'active' until HRD renews it (→ 'renewed') or ends it (→ 'expired' Selesai / 'terminated' Diputus).
 */
class EmployeeContract extends Model
{
    protected $table = 'hrd_employee_contracts';

    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'duration_months',
        'status',
        'notes',
        'contract_document',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id')->withInactive();
    }

    /** User who created the contract */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
