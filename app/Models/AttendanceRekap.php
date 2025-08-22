<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRekap extends Model
{
    protected $table = 'attendance_rekap';

    protected $fillable = [
        'employee_id',
        'finger_id',
        'date',
        'jam_masuk',
        'jam_keluar',
        'shift_start',
        'shift_end',
        'work_hour',
    ];

    public function employee()
    {
        return $this->belongsTo(\App\Models\HRD\Employee::class, 'employee_id');
    }

    public function employeeSchedule()
    {
        return $this->hasOne(\App\Models\HRD\EmployeeSchedule::class, 'employee_id', 'employee_id')
            ->whereColumn('date', 'attendance_rekap.date');
    }

    // Alternative method to get schedule for a specific date
    public function getScheduleForDate()
    {
        return \App\Models\HRD\EmployeeSchedule::where('employee_id', $this->employee_id)
            ->where('date', $this->date)
            ->with('shift')
            ->first();
    }
}
