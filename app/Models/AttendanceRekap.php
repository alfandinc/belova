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
        return $this->hasOne(\App\Models\HRD\EmployeeSchedule::class, 'employee_id', 'employee_id');
    }

    // Alternative method to get schedule for a specific date
    public function getScheduleForDate()
    {
        return \App\Models\HRD\EmployeeSchedule::where('employee_id', $this->employee_id)
            ->where('date', $this->date)
            ->with('shift')
            ->first();
    }

    // Returns true if employee was on time (jam_masuk <= shift_start)
    public function getOnTimeAttribute()
    {
        if (!$this->jam_masuk || !$this->shift_start) return null;
        return strtotime($this->jam_masuk) <= strtotime($this->shift_start);
    }

    // Returns overtime in minutes (jam_keluar > shift_end)
    public function getOvertimeAttribute()
    {
    if (!$this->isValidTime($this->jam_keluar) || !$this->isValidTime($this->shift_end)) return 0;
    $end = strtotime($this->shift_end);
    $out = strtotime($this->jam_keluar);
    if ($end === false || $out === false) return 0;
    return $out > $end ? round(($out - $end) / 60) : 0;
    }

    // Returns true if employee was late (jam_masuk > shift_start)
    public function getTerlambatAttribute()
    {
        if (!$this->jam_masuk || !$this->shift_start) return null;
        return strtotime($this->jam_masuk) > strtotime($this->shift_start);
    }

    // Returns minutes late (jam_masuk > shift_start)
    public function getMenitTerlambatAttribute()
    {
        if (!$this->isValidTime($this->jam_masuk) || !$this->isValidTime($this->shift_start)) return 0;
        $start = strtotime($this->shift_start);
        $in = strtotime($this->jam_masuk);
        if ($start === false || $in === false) return 0;
        if ($in > $start) {
            return round(($in - $start) / 60);
        }
        return 0;
    }

    // Helper to validate time string in H:i format and not '00:00'
    protected function isValidTime($time)
    {
        if (!$time || $time === '00:00') return false;
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time);
    }
}
