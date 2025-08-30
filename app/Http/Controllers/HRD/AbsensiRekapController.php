<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRD\Employee;
use App\Models\HRD\EmployeeSchedule;
use App\Models\AttendanceRekap;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

    // /**
    //  * AJAX: Get schedule and shift info for employee and date
    //  */
    // public function getSchedule(Request $request)
    // {
    //     $employeeId = $request->input('employee_id');
    //     $date = $request->input('date');
    //     $schedule = EmployeeSchedule::where('employee_id', $employeeId)
    //         ->where('date', $date)
    //         ->with('shift')
    //         ->first();
    //     if ($schedule && $schedule->shift) {
    //         return response()->json([
    //             'shift' => [
    //                 'name' => $schedule->shift->name ?? '',
    //                 'start' => $schedule->shift->start_time ?? '',
    //                 'end' => $schedule->shift->end_time ?? '',
    //             ]
    //         ]);
    //     }
    //     return response()->json(['shift' => null]);
    // }

    // /**
    //  * Store new absensi record
    //  */
    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'employee_id' => 'required|exists:hrd_employee,id',
    //         'date' => 'required|date',
    //         'jam_masuk' => 'required',
    //         'jam_keluar' => 'required',
    //         'shift' => 'nullable|string',
    //         'work_hour' => 'required|numeric',
    //     ]);
    //     $employee = Employee::find($validated['employee_id']);
    //     $rekap = new AttendanceRekap();
    //     $rekap->employee_id = $validated['employee_id'];
    //     $rekap->finger_id = $employee ? $employee->finger_id : null;
    //     $rekap->date = $validated['date'];
    //     $rekap->jam_masuk = $validated['jam_masuk'];
    //     $rekap->jam_keluar = $validated['jam_keluar'];
    //     $rekap->shift = $validated['shift'] ?? null;
    //     $rekap->work_hour = $validated['work_hour'];
    //     $rekap->save();
    //     return response()->json(['success' => true]);
    // }

class AbsensiRekapController extends Controller
{
    /**
     * Check if a shift crosses midnight (overnight shift)
     */
    private function isOvernightShift($startTime, $endTime)
    {
        // If end time is 00:00:00 or earlier than start time, it's overnight
        return $endTime === '00:00:00' || $startTime > $endTime;
    }

    /**
     * AJAX: Get schedule and shift info for employee and date
     */
    public function getSchedule(Request $request)
    {
        $employeeId = $request->input('employee_id');
        $date = $request->input('date');
        $schedule = EmployeeSchedule::where('employee_id', $employeeId)
            ->where('date', $date)
            ->with('shift')
            ->first();
        if ($schedule && $schedule->shift) {
            return response()->json([
                'shift' => [
                    'name' => $schedule->shift->name ?? '',
                    'start' => $schedule->shift->start_time ?? '',
                    'end' => $schedule->shift->end_time ?? '',
                ]
            ]);
        }
        return response()->json(['shift' => null]);
    }

    /**
     * Store new absensi record
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:hrd_employee,id',
            'date' => 'required|date',
            'jam_masuk' => 'required',
            'jam_keluar' => 'required',
            'shift_start' => 'nullable|string',
            'shift_end' => 'nullable|string',
            'work_hour' => 'required|numeric',
        ]);
        $employee = Employee::find($validated['employee_id']);
        AttendanceRekap::updateOrCreate(
            [
                'finger_id' => $employee ? $employee->finger_id : null,
                'date' => $validated['date'],
            ],
            [
                'employee_id' => $validated['employee_id'],
                'jam_masuk' => $validated['jam_masuk'],
                'jam_keluar' => $validated['jam_keluar'],
                'shift_start' => $validated['shift_start'] ?? null,
                'shift_end' => $validated['shift_end'] ?? null,
                'work_hour' => $validated['work_hour'],
            ]
        );
        return response()->json(['success' => true]);
    }

    /**
     * Calculate work hours considering overnight shifts
     */
    private function calculateWorkHours($jamMasuk, $jamKeluar, $date)
    {
        if (!$jamMasuk || !$jamKeluar) {
            return 0;
        }

        // Parse times - handle both "d/m/Y H:i" and "Y-m-d H:i:s" formats
        $jamMasukTime = $this->parseTime($jamMasuk);
        $jamKeluarTime = $this->parseTime($jamKeluar);
        
        if (!$jamMasukTime || !$jamKeluarTime) {
            return 0;
        }

        // Calculate difference in seconds
        $diff = $jamKeluarTime - $jamMasukTime;

        // If jam_keluar is on the next day, handle overnight shift
        if ($diff < 0) {
            // Try to detect if jam_keluar is on the next day
            // Parse date part from both jam_masuk and jam_keluar
            $dateMasuk = null;
            $dateKeluar = null;
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $jamMasuk, $mMasuk)) {
                $dateMasuk = $mMasuk[3] . '-' . $mMasuk[2] . '-' . $mMasuk[1];
            }
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $jamKeluar, $mKeluar)) {
                $dateKeluar = $mKeluar[3] . '-' . $mKeluar[2] . '-' . $mKeluar[1];
            }
            if ($dateMasuk && $dateKeluar && $dateKeluar > $dateMasuk) {
                // jam_keluar is on the next day, so calculate the true difference
                $diff = $jamKeluarTime - $jamMasukTime;
            } else {
                $diff += 24 * 3600; // fallback: add 24 hours
            }
        }
        
        $workHours = $diff / 3600;
        
        // Ensure reasonable work hours (0-24 hours)
        $workHours = max(0, min(24, $workHours));
        
        return round($workHours, 2);
    }

    /**
     * Parse time from various formats
     */
    private function parseTime($timeString)
    {
        if (!$timeString) return false;
        
        // Handle "d/m/Y H:i" format (e.g., "13/08/2025 15:58")
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})\s+(\d{1,2}):(\d{2})$/', $timeString, $matches)) {
            $day = $matches[1];
            $month = $matches[2];
            $year = $matches[3];
            $hour = $matches[4];
            $minute = $matches[5];
            return strtotime("{$year}-{$month}-{$day} {$hour}:{$minute}:00");
        }
        
        // Handle standard formats
        return strtotime($timeString);
    }

    /**
     * Check if employee is late considering overnight shifts
     */
    private function isLate($jamMasuk, $shiftStart, $date)
    {
        if (!$jamMasuk || !$shiftStart) {
            return false;
        }

        $jamMasukTime = strpos($jamMasuk, ' ') !== false ? 
            strtotime($jamMasuk) : 
            strtotime($date . ' ' . $jamMasuk);
        
        $shiftStartTime = strtotime($date . ' ' . $shiftStart);

        return $jamMasukTime > $shiftStartTime;
    }

    /**
     * Calculate minutes late for an employee
     */
    private function calculateMinutesLate($jamMasuk, $shiftStart, $date)
    {
        if (!$jamMasuk || !$shiftStart) {
            return 0;
        }

        $jamMasukTime = strpos($jamMasuk, ' ') !== false ? 
            strtotime($jamMasuk) : 
            strtotime($date . ' ' . $jamMasuk);
        
        $shiftStartTime = strtotime($date . ' ' . $shiftStart);

        if ($jamMasukTime > $shiftStartTime) {
            return round(($jamMasukTime - $shiftStartTime) / 60);
        }

        return 0;
    }

    /**
     * Find the best jam masuk and jam keluar based on shift schedule
     * Supports cross-date selection for overnight shifts
     */
    private function findBestAttendanceTimes($times, $shiftStart, $shiftEnd, $date)
    {
        if (empty($times)) {
            return [null, null];
        }

        // If no shift schedule, fall back to min/max
        if (!$shiftStart || !$shiftEnd) {
            return [min($times), max($times)];
        }

        // Determine if this is an overnight shift
        $isOvernightShift = $this->isOvernightShift($shiftStart, $shiftEnd);
        
        // Parse all available times with their full datetime info
        $timeRecords = [];
        foreach ($times as $time) {
            $timestamp = strtotime($time);
            $timeOnly = date('H:i:s', $timestamp);
            $dateOnly = date('Y-m-d', $timestamp);
            
            $timeRecords[] = [
                'original' => $time,
                'timestamp' => $timestamp,
                'time_only' => $timeOnly,
                'date_only' => $dateOnly,
                'formatted_datetime' => date('Y-m-d H:i:s', $timestamp)
            ];
        }

        // Sort by timestamp
        usort($timeRecords, function($a, $b) {
            return $a['timestamp'] - $b['timestamp'];
        });

        $bestMasuk = null;
        $bestKeluar = null;
        
        if ($isOvernightShift) {
            // For overnight shifts, find jam masuk from shift date and jam keluar from next date
            $nextDate = date('Y-m-d', strtotime($date . ' +1 day'));
            
            // Find best jam masuk (from shift date, closest to shift start)
            $shiftDateTimes = array_filter($timeRecords, function($record) use ($date) {
                return $record['date_only'] === $date;
            });
            
            if (!empty($shiftDateTimes)) {
                $shiftStartTarget = strtotime($date . ' ' . $shiftStart);
                $minMasukDiff = PHP_INT_MAX;
                
                foreach ($shiftDateTimes as $record) {
                    $diff = abs($record['timestamp'] - $shiftStartTarget);
                    if ($diff < $minMasukDiff) {
                        $minMasukDiff = $diff;
                        $bestMasuk = $record['original'];
                    }
                }
            }
            
            // Find best jam keluar (from next date, closest to shift end)
            $nextDateTimes = array_filter($timeRecords, function($record) use ($nextDate) {
                return $record['date_only'] === $nextDate;
            });
            
            if (!empty($nextDateTimes)) {
                $shiftEndTarget = strtotime($nextDate . ' ' . $shiftEnd);
                $minKeluarDiff = PHP_INT_MAX;
                
                foreach ($nextDateTimes as $record) {
                    $diff = abs($record['timestamp'] - $shiftEndTarget);
                    if ($diff < $minKeluarDiff) {
                        $minKeluarDiff = $diff;
                        $bestKeluar = $record['original'];
                    }
                }
            }
            
            // Fallbacks for overnight shifts
            if (!$bestMasuk && !empty($shiftDateTimes)) {
                // Use latest time from shift date as fallback for masuk
                $bestMasuk = end($shiftDateTimes)['original'];
            }
            
            if (!$bestKeluar && !empty($nextDateTimes)) {
                // Use earliest time from next date as fallback for keluar
                $bestKeluar = reset($nextDateTimes)['original'];
            }
            
            // Ultimate fallback for overnight shifts
            if (!$bestMasuk) {
                $bestMasuk = reset($timeRecords)['original'];
            }
            if (!$bestKeluar) {
                $bestKeluar = end($timeRecords)['original'];
            }
            
        } else {
            // For regular shifts, find both times from the same date
            $sameDateTimes = array_filter($timeRecords, function($record) use ($date) {
                return $record['date_only'] === $date;
            });
            
            if (!empty($sameDateTimes)) {
                $shiftStartTarget = strtotime($date . ' ' . $shiftStart);
                $shiftEndTarget = strtotime($date . ' ' . $shiftEnd);
                
                $minMasukDiff = PHP_INT_MAX;
                $minKeluarDiff = PHP_INT_MAX;
                
                foreach ($sameDateTimes as $record) {
                    // Check for jam masuk
                    $masukDiff = abs($record['timestamp'] - $shiftStartTarget);
                    if ($masukDiff < $minMasukDiff) {
                        $minMasukDiff = $masukDiff;
                        $bestMasuk = $record['original'];
                    }
                    
                    // Check for jam keluar
                    $keluarDiff = abs($record['timestamp'] - $shiftEndTarget);
                    if ($keluarDiff < $minKeluarDiff) {
                        $minKeluarDiff = $keluarDiff;
                        $bestKeluar = $record['original'];
                    }
                }
            }
            
            // Fallbacks for regular shifts
            if (!$bestMasuk || !$bestKeluar) {
                $allSameDateTimes = array_filter($timeRecords, function($record) use ($date) {
                    return $record['date_only'] === $date;
                });
                
                if (!empty($allSameDateTimes)) {
                    if (!$bestMasuk) {
                        $bestMasuk = reset($allSameDateTimes)['original'];
                    }
                    if (!$bestKeluar) {
                        $bestKeluar = end($allSameDateTimes)['original'];
                    }
                }
            }
        }

        // Log the selection process for debugging
        Log::info("Smart time selection process", [
            'date' => $date,
            'shift' => $shiftStart . '-' . $shiftEnd,
            'is_overnight' => $isOvernightShift,
            'available_times' => array_column($timeRecords, 'formatted_datetime'),
            'selected_masuk' => $bestMasuk,
            'selected_keluar' => $bestKeluar
        ]);

        return [$bestMasuk, $bestKeluar];
    }

    /**
     * Check if employee has overtime considering overnight shifts
     */
    private function hasOvertime($jamKeluar, $shiftEnd, $date)
    {
        if (!$jamKeluar || !$shiftEnd) {
            return false;
        }

        $jamKeluarTime = strpos($jamKeluar, ' ') !== false ? 
            strtotime($jamKeluar) : 
            strtotime($date . ' ' . $jamKeluar);
        
        $shiftEndTime = strtotime($date . ' ' . $shiftEnd);

        // For overnight shifts (ending at 00:00:00 or early hours), shift end is next day
        if ($shiftEnd === '00:00:00' || ($shiftEnd < '12:00:00' && $shiftEnd !== '00:00:00')) {
            $shiftEndTime += 24 * 3600; // Add 24 hours for next day
        }

        // Extract time parts
        $jamKeluarTimeOnly = date('H:i:s', $jamKeluarTime);
        $shiftEndTimeOnly = $shiftEnd;

        // If jam_keluar is early morning and shift ends late (overnight shift)
        if ($jamKeluarTimeOnly < '12:00:00' && $shiftEndTimeOnly > '12:00:00') {
            $jamKeluarTime += 24 * 3600; // jam_keluar is next day
        }
        // If shift ends at midnight and jam_keluar is after midnight
        elseif ($shiftEnd === '00:00:00' && $jamKeluarTimeOnly > '00:00:00' && $jamKeluarTimeOnly < '12:00:00') {
            $jamKeluarTime += 24 * 3600; // jam_keluar is next day
        }

        return $jamKeluarTime > $shiftEndTime;
    }
    public function update(Request $request, $id)
    {
        Log::info('AbsensiRekapController@update called', ['id' => $id, 'data' => $request->all()]);
        $rekap = AttendanceRekap::findOrFail($id);
        $request->validate([
            'jam_masuk' => 'required',
            'jam_keluar' => 'required',
        ]);
        // Format jam masuk/keluar as time only
        $date = $rekap->date;
        $jamMasuk = $date . ' ' . $request->jam_masuk;
        $jamKeluar = $date . ' ' . $request->jam_keluar;
        
        // Recalculate work hour using the new helper function
        $workHour = $this->calculateWorkHours($jamMasuk, $jamKeluar, $date);
        
        $rekap->jam_masuk = $jamMasuk;
        $rekap->jam_keluar = $jamKeluar;
        $rekap->work_hour = $workHour;
        $rekap->save();
        return response()->json(['success' => true]);
    }
    public function data(Request $request)
    {
    $query = AttendanceRekap::with(['employee']);
        $dateRange = $request->input('date_range');
        if ($dateRange) {
            $dates = explode(' - ', $dateRange);
            if (count($dates) === 2) {
                $start = $dates[0];
                $end = $dates[1];
                $query->whereBetween('date', [$start, $end]);
            }
        }
        $employeeIds = $request->input('employee_ids');
        if ($employeeIds && is_array($employeeIds)) {
            $query->whereIn('employee_id', $employeeIds);
        } elseif ($employeeIds && !is_array($employeeIds)) {
            $query->where('employee_id', $employeeIds);
        }
        return datatables()->of($query)
            ->addColumn('employee_name', function($row) {
                return $row->employee ? $row->employee->nama : '';
            })
            ->editColumn('jam_masuk', function($row) {
                if ($row->jam_masuk) {
                    $parts = explode(' ', $row->jam_masuk);
                    return isset($parts[1]) ? $parts[1] : $row->jam_masuk;
                }
                return '';
            })
            ->editColumn('jam_keluar', function($row) {
                if ($row->jam_keluar) {
                    $parts = explode(' ', $row->jam_keluar);
                    return isset($parts[1]) ? $parts[1] : $row->jam_keluar;
                }
                return '';
            })
                ->addColumn('status', function($row) {
                    $status = [];
                    
                    // Compare only time part
                    $jamMasuk = $row->jam_masuk ? (explode(' ', $row->jam_masuk)[1] ?? $row->jam_masuk) : null;
                    $jamKeluar = $row->jam_keluar ? (explode(' ', $row->jam_keluar)[1] ?? $row->jam_keluar) : null;
                    $shiftStart = $row->shift_start ?? null;
                    $shiftEnd = $row->shift_end ?? null;
                    
                    // Calculate Terlambat using new helper function
                    if ($this->isLate($jamMasuk, $shiftStart, $row->date)) {
                        $jamMasukTime = strtotime($row->date . ' ' . $jamMasuk);
                        $shiftStartTime = strtotime($row->date . ' ' . $shiftStart);
                        $diff = $jamMasukTime - $shiftStartTime;
                        $minutes = round($diff / 60);
                        
                        if ($minutes >= 60) {
                            $hours = floor($minutes / 60);
                            $mins = $minutes % 60;
                            $label = 'Terlambat ' . $hours . ' jam';
                            if ($mins > 0) $label .= ' ' . $mins . ' menit';
                        } else {
                            $label = 'Terlambat ' . $minutes . ' menit';
                        }
                        $status[] = $label;
                    }
                    
                    // Calculate Over Time using new helper function
                    if ($this->hasOvertime($jamKeluar, $shiftEnd, $row->date)) {
                        $jamKeluarTime = strpos($jamKeluar, ' ') !== false ? 
                            strtotime($jamKeluar) : 
                            strtotime($row->date . ' ' . $jamKeluar);
                        
                        $shiftEndTime = strtotime($row->date . ' ' . $shiftEnd);
                        
                        // For overnight shifts (ending at 00:00:00 or early hours), shift end is next day
                        if ($shiftEnd === '00:00:00' || ($shiftEnd < '12:00:00' && $shiftEnd !== '00:00:00')) {
                            $shiftEndTime += 24 * 3600; // Add 24 hours for next day
                        }

                        // Extract time parts
                        $jamKeluarTimeOnly = date('H:i:s', $jamKeluarTime);

                        // If jam_keluar is early morning and shift ends late (overnight shift)
                        if ($jamKeluarTimeOnly < '12:00:00' && $shiftEnd > '12:00:00') {
                            $jamKeluarTime += 24 * 3600; // jam_keluar is next day
                        }
                        // If shift ends at midnight and jam_keluar is after midnight
                        elseif ($shiftEnd === '00:00:00' && $jamKeluarTimeOnly > '00:00:00' && $jamKeluarTimeOnly < '12:00:00') {
                            $jamKeluarTime += 24 * 3600; // jam_keluar is next day
                        }
                        
                        $diff = $jamKeluarTime - $shiftEndTime;
                        $minutes = round($diff / 60);
                        
                        if ($minutes > 0) {
                            if ($minutes >= 60) {
                                $hours = floor($minutes / 60);
                                $mins = $minutes % 60;
                                $label = 'Over Time ' . $hours . ' jam';
                                if ($mins > 0) $label .= ' ' . $mins . ' menit';
                            } else {
                                $label = 'Over Time ' . $minutes . ' menit';
                            }
                            $status[] = $label;
                        }
                    }
                    
                    return implode(' & ', $status);
                })
            ->addColumn('shift', function($row) {
                $shiftName = '';
                $start = $row->shift_start ?? '';
                $end = $row->shift_end ?? '';
                
                // Try to get the actual schedule for this date
                $schedule = \App\Models\HRD\EmployeeSchedule::where('employee_id', $row->employee_id)
                    ->where('date', $row->date)
                    ->with('shift')
                    ->first();
                
                if ($schedule && $schedule->shift) {
                    $shiftName = $schedule->shift->name;
                    // Use the actual shift times from the shift definition
                    $actualStart = $schedule->shift->start_time ?? $start;
                    $actualEnd = $schedule->shift->end_time ?? $end;
                    
                    // Update the attendance_rekap record if shift times don't match
                    if ($actualStart !== $start || $actualEnd !== $end) {
                        try {
                            AttendanceRekap::where('id', $row->id)->update([
                                'shift_start' => $actualStart,
                                'shift_end' => $actualEnd
                            ]);
                            $start = $actualStart;
                            $end = $actualEnd;
                        } catch (\Exception $e) {
                            Log::error("Failed to update shift times for attendance ID {$row->id}: " . $e->getMessage());
                        }
                    }
                    
                    return $shiftName . ' (' . $start . '-' . $end . ')';
                } elseif ($start && $end) {
                    // Fallback to stored times if no schedule found
                    return '<span class="text-warning">No Schedule</span> (' . $start . '-' . $end . ')';
                } else {
                    // No shift data available
                    return '<span class="text-danger">No Shift Data</span>';
                }
            })
            ->rawColumns(['shift']) // Allow HTML in shift column
            ->make(true);
    }

    public function index()
    {
        return view('hrd.absensi_rekap.index');
    }

    public function statistics(Request $request)
    {
        $query = AttendanceRekap::with(['employee']);
        
        // Apply same filters as data method
        $dateRange = $request->input('date_range');
        if ($dateRange) {
            $dates = explode(' - ', $dateRange);
            if (count($dates) === 2) {
                $start = $dates[0];
                $end = $dates[1];
                $query->whereBetween('date', [$start, $end]);
            }
        }
        
        $employeeIds = $request->input('employee_ids');
        if ($employeeIds && !empty($employeeIds) && $employeeIds !== '') {
            if (is_array($employeeIds)) {
                $query->whereIn('employee_id', $employeeIds);
            } else {
                $query->where('employee_id', $employeeIds);
            }
        }
        
        $records = $query->get();
        
        $totalEmployees = $records->count();
        $lateCount = 0;
        $overtimeCount = 0;
        $onTimeCount = 0;
        $employeeLateMinutes = [];
        
        foreach ($records as $record) {
            $isLate = false;
            $hasOvertimeFlag = false;
            
            // Check if late using new helper function
            $jamMasuk = $record->jam_masuk ? (explode(' ', $record->jam_masuk)[1] ?? $record->jam_masuk) : null;
            $shiftStart = $record->shift_start ?? null;
            
            if ($this->isLate($jamMasuk, $shiftStart, $record->date)) {
                $lateCount++;
                $isLate = true;
                
                // Calculate minutes late
                $minutesLate = $this->calculateMinutesLate($jamMasuk, $shiftStart, $record->date);
                
                // Track employee late minutes
                $employeeId = $record->employee_id;
                $employeeName = $record->employee ? $record->employee->nama : 'Unknown';
                
                if (!isset($employeeLateMinutes[$employeeId])) {
                    $employeeLateMinutes[$employeeId] = [
                        'employee_id' => $employeeId,
                        'employee_name' => $employeeName,
                        'total_late_minutes' => 0,
                        'late_instances' => 0
                    ];
                }
                
                $employeeLateMinutes[$employeeId]['total_late_minutes'] += $minutesLate;
                $employeeLateMinutes[$employeeId]['late_instances']++;
            }
            
            // Check if overtime using new helper function
            $jamKeluar = $record->jam_keluar ? (explode(' ', $record->jam_keluar)[1] ?? $record->jam_keluar) : null;
            $shiftEnd = $record->shift_end ?? null;
            
            if ($this->hasOvertime($jamKeluar, $shiftEnd, $record->date)) {
                $overtimeCount++;
                $hasOvertimeFlag = true;
            }
            
            // Count on-time (not late and has both jam_masuk and jam_keluar)
            if (!$isLate && $jamMasuk && $jamKeluar) {
                $onTimeCount++;
            }
        }
        
        // Get top 5 most late employees
        $top5LateEmployees = collect($employeeLateMinutes)
            ->sortByDesc('total_late_minutes')
            ->take(5)
            ->map(function($employee) {
                $avgMinutesLate = $employee['late_instances'] > 0 ? 
                    round($employee['total_late_minutes'] / $employee['late_instances'], 1) : 0;
                
                return [
                    'employee_name' => $employee['employee_name'],
                    'total_late_minutes' => $employee['total_late_minutes'],
                    'late_instances' => $employee['late_instances'],
                    'avg_minutes_late' => $avgMinutesLate,
                    'formatted_total' => $this->formatMinutes($employee['total_late_minutes']),
                    'formatted_avg' => $this->formatMinutes($avgMinutesLate)
                ];
            })
            ->values();
        
        return response()->json([
            'total_records' => $totalEmployees,
            'late_count' => $lateCount,
            'overtime_count' => $overtimeCount,
            'on_time_count' => $onTimeCount,
            'late_percentage' => $totalEmployees > 0 ? round(($lateCount / $totalEmployees) * 100, 1) : 0,
            'overtime_percentage' => $totalEmployees > 0 ? round(($overtimeCount / $totalEmployees) * 100, 1) : 0,
            'on_time_percentage' => $totalEmployees > 0 ? round(($onTimeCount / $totalEmployees) * 100, 1) : 0,
            'top_5_late_employees' => $top5LateEmployees
        ]);
    }

    /**
     * Format minutes to human readable format
     */
    private function formatMinutes($minutes)
    {
        if ($minutes < 60) {
            return $minutes . ' menit';
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($remainingMinutes > 0) {
            return $hours . ' jam ' . $remainingMinutes . ' menit';
        }
        
        return $hours . ' jam';
    }

    /**
     * Upload attendance data from Excel file
     * Uses smart time selection to pick the best jam masuk/keluar based on shift schedule
     * 
     * For overnight shifts (e.g., 16:00-00:00):
     * - Jam masuk: picks time closest to shift start on the shift date
     * - Jam keluar: picks time closest to shift end, which may be on the next date
     * 
     * Example: Sofia's shift malam (16:00-00:00) on 2025-08-13
     * Available times: [08:59, 15:58, 17:44, 14/08 00:00, 14/08 17:29]
     * Selected: Masuk = 15:58 (closest to 16:00), Keluar = 14/08 00:00 (exact match)
     */
    public function upload(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xls,xlsx',
            ]);
            if (!$request->hasFile('file')) {
                return response()->json(['error' => 'File not uploaded'], 422);
            }
            $path = $request->file('file')->getRealPath();
            $data = Excel::toArray([], $path);
            if (empty($data) || empty($data[0])) {
                return response()->json(['error' => 'File is empty or format is invalid'], 422);
            }
            $rows = array_slice($data[0], 1);
            $grouped = [];
            foreach ($rows as $row) {
                $fingerId = $row[0] ?? null;
                $nama = $row[1] ?? null;
                $waktu = $row[2] ?? null;
                if (!$fingerId || !$waktu) continue;
                $dateRaw = substr($waktu, 0, 10);
                $dateParts = explode('/', $dateRaw);
                if (count($dateParts) === 3) {
                    $date = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0];
                } else {
                    $date = $dateRaw;
                }
                $grouped[$fingerId][$date][] = $waktu;
            }
            foreach ($grouped as $fingerId => $dates) {
                $employee = Employee::where('finger_id', $fingerId)->first();
                
                if (!$employee) {
                    Log::warning("Employee not found for finger_id: {$fingerId}");
                    continue; // Skip if employee not found
                }
                
                // STEP 1: Get ALL schedules for this employee across all dates
                $dateKeys = array_keys($dates);
                $allSchedules = EmployeeSchedule::where('employee_id', $employee->id)
                    ->whereIn('date', $dateKeys)
                    ->with('shift')
                    ->get()
                    ->keyBy('date');
                
                // STEP 2: Collect ALL attendance times with full context
                $allTimes = [];
                foreach ($dates as $date => $times) {
                    foreach ($times as $time) {
                        // Parse the time carefully - format is "d/m/Y H:i"
                        $timeParts = explode(' ', $time);
                        if (count($timeParts) >= 2) {
                            $datePart = $timeParts[0]; // "13/08/2025"
                            $timePart = $timeParts[1]; // "15:58"
                            
                            // Convert date from d/m/Y to Y-m-d
                            $dateComponents = explode('/', $datePart);
                            if (count($dateComponents) === 3) {
                                $properDate = $dateComponents[2] . '-' . $dateComponents[1] . '-' . $dateComponents[0];
                                $fullDateTime = $properDate . ' ' . $timePart . ':00';
                                
                                $allTimes[] = [
                                    'datetime' => $time,
                                    'date' => $properDate,
                                    'time' => $timePart . ':00',
                                    'timestamp' => strtotime($fullDateTime),
                                    'original_date' => $date // The date this time was grouped under
                                ];
                            }
                        }
                    }
                }
                
                // Sort all times chronologically
                usort($allTimes, function($a, $b) {
                    return $a['timestamp'] - $b['timestamp'];
                });
                
                Log::info("Processing employee {$employee->nama}", [
                    'finger_id' => $fingerId,
                    'dates_with_schedules' => $dateKeys,
                    'all_times' => array_column($allTimes, 'datetime'),
                    'schedules' => $allSchedules->map(function($s) {
                        return $s->shift ? $s->shift->start_time . '-' . $s->shift->end_time : 'No shift';
                    })->toArray()
                ]);
                
                // STEP 3: Process each scheduled date and intelligently assign times
                foreach ($allSchedules as $date => $schedule) {
                    if (!$schedule->shift) {
                        Log::info("No shift for {$employee->nama} on {$date}");
                        continue;
                    }
                    
                    $shiftStart = $schedule->shift->start_time;
                    $shiftEnd = $schedule->shift->end_time;
                    $isOvernightShift = $this->isOvernightShift($shiftStart, $shiftEnd);
                    
                    $jamMasuk = null;
                    $jamKeluar = null;
                    
                    if ($isOvernightShift) {
                        // OVERNIGHT SHIFT LOGIC
                        $nextDate = date('Y-m-d', strtotime($date . ' +1 day'));
                        
                        // Find jam masuk: times on shift date, closest to shift start
                        $shiftStartTarget = strtotime($date . ' ' . $shiftStart);
                        $masukCandidates = array_filter($allTimes, function($timeData) use ($date) {
                            return $timeData['date'] === $date;
                        });
                        
                        Log::info("Overnight masuk search for {$employee->nama} on {$date}", [
                            'shift_start_target' => date('Y-m-d H:i:s', $shiftStartTarget),
                            'all_times_count' => count($allTimes),
                            'masuk_candidates_count' => count($masukCandidates),
                            'masuk_candidates' => array_column($masukCandidates, 'datetime')
                        ]);
                        
                        if (!empty($masukCandidates)) {
                            $bestMasukDiff = PHP_INT_MAX;
                            foreach ($masukCandidates as $candidate) {
                                $diff = abs($candidate['timestamp'] - $shiftStartTarget);
                                if ($diff < $bestMasukDiff) {
                                    $bestMasukDiff = $diff;
                                    $jamMasuk = $candidate['datetime'];
                                }
                            }
                        }
                        
                        // Find jam keluar: Consider times from both current date (for late evening) and next date (for after midnight)
                        $shiftEndTarget = strtotime($nextDate . ' ' . $shiftEnd);
                        
                        // Get candidates from the next date (for times after midnight, like 00:00)
                        $nextDateCandidates = array_filter($allTimes, function($timeData) use ($nextDate) {
                            return $timeData['date'] === $nextDate;
                        });
                        
                        // Get candidates from the same date (for late evening times, like 23:13)
                        $sameDateCandidates = array_filter($allTimes, function($timeData) use ($date) {
                            return $timeData['date'] === $date;
                        });
                        
                        // Combine all potential keluar candidates
                        $keluarCandidates = array_merge($nextDateCandidates, $sameDateCandidates);
                        
                        // Remove duplicates based on datetime
                        $keluarCandidates = array_values(array_unique($keluarCandidates, SORT_REGULAR));
                        
                        Log::info("Overnight keluar search for {$employee->nama} on {$date}", [
                            'next_date' => $nextDate,
                            'shift_end_target' => date('Y-m-d H:i:s', $shiftEndTarget),
                            'next_date_candidates' => array_column($nextDateCandidates, 'datetime'),
                            'same_date_candidates' => array_column($sameDateCandidates, 'datetime'),
                            'total_keluar_candidates' => array_column($keluarCandidates, 'datetime')
                        ]);
                        
                        if (!empty($keluarCandidates)) {
                            $bestKeluarDiff = PHP_INT_MAX;
                            $bestKeluarTime = null;
                            
                            foreach ($keluarCandidates as $candidate) {
                                // Skip if this is the same time as jam_masuk
                                if ($jamMasuk && $candidate['datetime'] === $jamMasuk) {
                                    continue;
                                }
                                
                                // For overnight shifts, prefer times that make logical sense:
                                // 1. Times after shift start on same date (like 23:13)
                                // 2. Times on next date close to shift end (like 00:00)
                                $candidateTimestamp = $candidate['timestamp'];
                                $candidateDate = $candidate['date'];
                                
                                // If candidate is on same date, it should be after shift start time
                                if ($candidateDate === $date) {
                                    $shiftStartTime = strtotime($date . ' ' . $shiftStart);
                                    if ($candidateTimestamp < $shiftStartTime) {
                                        continue; // Skip times before shift start on same date
                                    }
                                }
                                
                                // Calculate diff from target shift end
                                $diff = abs($candidateTimestamp - $shiftEndTarget);
                                
                                if ($diff < $bestKeluarDiff) {
                                    $bestKeluarDiff = $diff;
                                    $bestKeluarTime = $candidate['datetime'];
                                }
                            }
                            
                            $jamKeluar = $bestKeluarTime;
                        }
                        
                        Log::info("Overnight shift processing for {$employee->nama} on {$date}", [
                            'shift' => $shiftStart . '-' . $shiftEnd,
                            'masuk_candidates' => array_column($masukCandidates, 'datetime'),
                            'keluar_candidates' => array_column($keluarCandidates, 'datetime'),
                            'selected_masuk' => $jamMasuk,
                            'selected_keluar' => $jamKeluar
                        ]);
                        
                    } else {
                        // REGULAR SHIFT LOGIC
                        $shiftStartTarget = strtotime($date . ' ' . $shiftStart);
                        $shiftEndTarget = strtotime($date . ' ' . $shiftEnd);
                        
                        // Find both times from the same date
                        $sameDateTimes = array_filter($allTimes, function($timeData) use ($date) {
                            return $timeData['date'] === $date;
                        });
                        
                        Log::info("Regular shift search for {$employee->nama} on {$date}", [
                            'shift_start_target' => date('Y-m-d H:i:s', $shiftStartTarget),
                            'shift_end_target' => date('Y-m-d H:i:s', $shiftEndTarget),
                            'same_date_times_count' => count($sameDateTimes),
                            'same_date_times' => array_column($sameDateTimes, 'datetime')
                        ]);
                        
                        if (!empty($sameDateTimes)) {
                            $bestMasukDiff = PHP_INT_MAX;
                            $bestKeluarDiff = PHP_INT_MAX;
                            
                            foreach ($sameDateTimes as $candidate) {
                                // Check for jam masuk
                                $masukDiff = abs($candidate['timestamp'] - $shiftStartTarget);
                                if ($masukDiff < $bestMasukDiff) {
                                    $bestMasukDiff = $masukDiff;
                                    $jamMasuk = $candidate['datetime'];
                                }
                                
                                // Check for jam keluar
                                $keluarDiff = abs($candidate['timestamp'] - $shiftEndTarget);
                                if ($keluarDiff < $bestKeluarDiff) {
                                    $bestKeluarDiff = $keluarDiff;
                                    $jamKeluar = $candidate['datetime'];
                                }
                            }
                        }
                        
                        Log::info("Regular shift processing for {$employee->nama} on {$date}", [
                            'shift' => $shiftStart . '-' . $shiftEnd,
                            'candidates' => array_column($sameDateTimes, 'datetime'),
                            'selected_masuk' => $jamMasuk,
                            'selected_keluar' => $jamKeluar
                        ]);
                    }
                    
                    // FALLBACK: If no times found, use any available times
                    if (!$jamMasuk || !$jamKeluar) {
                        Log::warning("No optimal times found for {$employee->nama} on {$date}, using fallbacks", [
                            'found_masuk' => $jamMasuk,
                            'found_keluar' => $jamKeluar,
                            'all_times_count' => count($allTimes)
                        ]);
                        
                        if (!$jamMasuk && !empty($allTimes)) {
                            $jamMasuk = $allTimes[0]['datetime']; // First available time
                        }
                        if (!$jamKeluar && !empty($allTimes)) {
                            $jamKeluar = end($allTimes)['datetime']; // Last available time
                        }
                    }
                    
                    // Calculate work hours
                    $workHour = $this->calculateWorkHours($jamMasuk, $jamKeluar, $date);
                    
                        // Helper to standardize to 'd/m/Y H:i'
                        $toExcelFormat = function($datetime, $date) {
                            // If already in 'd/m/Y H:i', return as is
                            if (preg_match('/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}/', $datetime)) {
                                return $datetime;
                            }
                            // If in 'Y-m-d H:i:s', convert
                            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $datetime)) {
                                $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
                                return $dt ? $dt->format('d/m/Y H:i') : $datetime;
                            }
                            // If only time, combine with date
                            if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $datetime)) {
                                $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $date . ' ' . $datetime);
                                return $dt ? $dt->format('d/m/Y H:i') : $datetime;
                            }
                            return $datetime;
                        };

                        AttendanceRekap::updateOrCreate(
                            [
                                'finger_id' => $fingerId,
                                'date' => $date,
                            ],
                            [
                                'employee_id' => $employee->id,
                                'jam_masuk' => $toExcelFormat($jamMasuk, $date),
                                'jam_keluar' => $toExcelFormat($jamKeluar, $date),
                                'shift_start' => $shiftStart,
                                'shift_end' => $shiftEnd,
                                'work_hour' => $workHour,
                            ]
                        );
                    
                    Log::info("Saved attendance for {$employee->nama} on {$date}", [
                        'jam_masuk' => $jamMasuk,
                        'jam_keluar' => $jamKeluar,
                        'work_hour' => $workHour
                    ]);
                }
            }
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Sync shift data for all attendance records
     */
    public function syncShiftData()
    {
        try {
            $updatedCount = 0;
            $missingScheduleCount = 0;
            $workHourUpdated = 0;
            
            // Get all attendance records
            $attendanceRecords = AttendanceRekap::with('employee')->get();
            
            foreach ($attendanceRecords as $record) {
                $hasUpdates = false;
                
                // Find the corresponding schedule
                $schedule = EmployeeSchedule::where('employee_id', $record->employee_id)
                    ->where('date', $record->date)
                    ->with('shift')
                    ->first();
                
                if ($schedule && $schedule->shift) {
                    // Update the shift times if they don't match
                    $actualStart = $schedule->shift->start_time;
                    $actualEnd = $schedule->shift->end_time;
                    
                    if ($record->shift_start !== $actualStart || $record->shift_end !== $actualEnd) {
                        $record->shift_start = $actualStart;
                        $record->shift_end = $actualEnd;
                        $hasUpdates = true;
                    }
                } else {
                    $missingScheduleCount++;
                }
                
                // Recalculate work hours with new logic
                $newWorkHour = $this->calculateWorkHours($record->jam_masuk, $record->jam_keluar, $record->date);
                if ($record->work_hour !== $newWorkHour) {
                    $record->work_hour = $newWorkHour;
                    $hasUpdates = true;
                    $workHourUpdated++;
                }
                
                if ($hasUpdates) {
                    $record->save();
                    $updatedCount++;
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Sync completed! Updated: {$updatedCount} records (Work hours: {$workHourUpdated}). Missing schedules: {$missingScheduleCount} records.",
                'updated_count' => $updatedCount,
                'work_hour_updated' => $workHourUpdated,
                'missing_schedule_count' => $missingScheduleCount
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Debug method to check shift data issues
     */
    public function debugShiftData()
    {
        $issues = [];
        
        // Check for attendance records without schedules
        $attendanceWithoutSchedule = AttendanceRekap::whereNotExists(function($query) {
            $query->select(DB::raw(1))
                  ->from('hrd_employee_schedules')
                  ->whereRaw('hrd_employee_schedules.employee_id = attendance_rekap.employee_id')
                  ->whereRaw('hrd_employee_schedules.date = attendance_rekap.date');
        })->count();
        $issues['attendance_without_schedule'] = $attendanceWithoutSchedule;
        
        // Check for attendance records with empty shift times
        $attendanceWithoutShiftTimes = AttendanceRekap::where(function($query) {
            $query->whereNull('shift_start')
                  ->orWhereNull('shift_end')
                  ->orWhere('shift_start', '')
                  ->orWhere('shift_end', '');
        })->count();
        $issues['attendance_without_shift_times'] = $attendanceWithoutShiftTimes;
        
        // Check for mismatched shift times
        $mismatchedShifts = DB::select("
            SELECT ar.id, ar.employee_id, ar.date, ar.shift_start, ar.shift_end, 
                   s.start_time, s.end_time, s.name as shift_name
            FROM attendance_rekap ar
            LEFT JOIN hrd_employee_schedules es ON ar.employee_id = es.employee_id AND ar.date = es.date
            LEFT JOIN hrd_shifts s ON es.shift_id = s.id
            WHERE s.id IS NOT NULL 
            AND (ar.shift_start != s.start_time OR ar.shift_end != s.end_time)
            LIMIT 10
        ");
        $issues['mismatched_shifts_sample'] = $mismatchedShifts;
        
        return response()->json($issues);
    }

    /**
     * Test cross-date overnight shift time selection
     */
    public function testCrossDateSelection()
    {
        // Sofia's actual data from screenshot
        // Aug 13: 15:58, 16:00, 00:00 (from Aug 14)
        // Aug 14: 08:59, 17:44, 17:29
        
        // Test 1: Aug 13 Malam shift (16:00-00:00)
        $aug13Times = [
            '2025-08-13 15:58:44',
            '2025-08-13 16:00:13',
            '2025-08-14 00:00:30',  // This should be selected as keluar
        ];
        
        $shiftStart = '16:00:00';
        $shiftEnd = '00:00:00';
        $date = '2025-08-13';
        
        [$jamMasuk13, $jamKeluar13] = $this->findBestAttendanceTimes($aug13Times, $shiftStart, $shiftEnd, $date);
        
        // Test 2: Aug 14 Pagi-Service shift (08:45-17:00)
        $aug14Times = [
            '2025-08-14 08:59:00',  // This should be selected as masuk
            '2025-08-14 17:44:00',
            '2025-08-14 17:29:00',  // This should be selected as keluar
        ];
        
        $shiftStart14 = '08:45:00';
        $shiftEnd14 = '17:00:00';
        $date14 = '2025-08-14';
        
        [$jamMasuk14, $jamKeluar14] = $this->findBestAttendanceTimes($aug14Times, $shiftStart14, $shiftEnd14, $date14);
        
        return response()->json([
            'aug_13_malam_shift' => [
                'available_times' => $aug13Times,
                'shift' => $shiftStart . '-' . $shiftEnd,
                'date' => $date,
                'is_overnight_shift' => $this->isOvernightShift($shiftStart, $shiftEnd),
                'selected_jam_masuk' => $jamMasuk13,
                'selected_jam_keluar' => $jamKeluar13,
                'expected_jam_masuk' => '2025-08-13 15:58:44',
                'expected_jam_keluar' => '2025-08-14 00:00:30',
                'test_passed' => [
                    'jam_masuk' => $jamMasuk13 === '2025-08-13 15:58:44',
                    'jam_keluar' => $jamKeluar13 === '2025-08-14 00:00:30'
                ]
            ],
            'aug_14_pagi_service_shift' => [
                'available_times' => $aug14Times,
                'shift' => $shiftStart14 . '-' . $shiftEnd14,
                'date' => $date14,
                'is_overnight_shift' => $this->isOvernightShift($shiftStart14, $shiftEnd14),
                'selected_jam_masuk' => $jamMasuk14,
                'selected_jam_keluar' => $jamKeluar14,
                'expected_jam_masuk' => '2025-08-14 08:59:00',
                'expected_jam_keluar' => '2025-08-14 17:29:00',
                'test_passed' => [
                    'jam_masuk' => $jamMasuk14 === '2025-08-14 08:59:00',
                    'jam_keluar' => $jamKeluar14 === '2025-08-14 17:29:00'
                ]
            ]
        ]);
    }
}
