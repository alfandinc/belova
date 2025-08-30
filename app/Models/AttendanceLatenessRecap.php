<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use App\Models\HRD\Employee;

class AttendanceLatenessRecap extends Model
{
    protected $table = 'attendance_lateness_recap';

    protected $fillable = [
        'employee_id',
        'month',
        'total_late_days',
        'total_late_minutes',
        'total_overtime_minutes',
        'total_late_minus_overtime',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
