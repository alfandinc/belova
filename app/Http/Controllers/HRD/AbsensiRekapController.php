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
     * Calculate work hours considering overnight shifts
     */
    private function calculateWorkHours($jamMasuk, $jamKeluar, $date)
    {
        if (!$jamMasuk || !$jamKeluar) {
            return 0;
        }

        // Parse times
        $jamMasukTime = null;
        $jamKeluarTime = null;

        // Handle different time formats
        if (strpos($jamMasuk, ' ') !== false) {
            // Format: "2024-08-22 08:00:00"
            $jamMasukTime = strtotime($jamMasuk);
        } else {
            // Format: "08:00:00" - combine with date
            $jamMasukTime = strtotime($date . ' ' . $jamMasuk);
        }

        if (strpos($jamKeluar, ' ') !== false) {
            // Format: "2024-08-22 17:00:00"
            $jamKeluarTime = strtotime($jamKeluar);
        } else {
            // Format: "17:00:00" - combine with date
            $jamKeluarTime = strtotime($date . ' ' . $jamKeluar);
        }

        // Extract time parts for comparison
        $jamMasukTimeOnly = date('H:i:s', $jamMasukTime);
        $jamKeluarTimeOnly = date('H:i:s', $jamKeluarTime);

        // If jam_keluar is earlier than jam_masuk or it's midnight (00:00:00), it's next day
        if ($jamKeluarTimeOnly <= $jamMasukTimeOnly || $jamKeluarTimeOnly === '00:00:00') {
            $jamKeluarTime += 24 * 3600; // Add 24 hours
        }

        $workHours = round(($jamKeluarTime - $jamMasukTime) / 3600, 2);
        
        // Ensure positive work hours
        return max(0, $workHours);
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
     * Find the best jam masuk and jam keluar based on shift schedule
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

        // Convert times to timestamps for comparison
        $timeStamps = [];
        foreach ($times as $time) {
            $timestamp = strtotime($time);
            $timeOnly = date('H:i:s', $timestamp);
            
            $timeStamps[] = [
                'original' => $time,
                'timestamp' => $timestamp,
                'time_only' => $timeOnly,
                'date_only' => date('Y-m-d', $timestamp)
            ];
        }

        // Sort by timestamp
        usort($timeStamps, function($a, $b) {
            return $a['timestamp'] - $b['timestamp'];
        });

        // Determine if this is an overnight shift
        $isOvernightShift = $this->isOvernightShift($shiftStart, $shiftEnd);
        
        // Create shift start and end timestamps for comparison
        $shiftStartTimestamp = strtotime($date . ' ' . $shiftStart);
        $shiftEndTimestamp = strtotime($date . ' ' . $shiftEnd);

        // For overnight shifts, adjust end timestamp to next day
        if ($isOvernightShift) {
            $shiftEndTimestamp += 24 * 3600; // Add 24 hours
        }

        $bestMasuk = null;
        $bestKeluar = null;
        $minMasukDiff = PHP_INT_MAX;
        $minKeluarDiff = PHP_INT_MAX;

        foreach ($timeStamps as $timeData) {
            $timestamp = $timeData['timestamp'];
            $timeOnly = $timeData['time_only'];
            $dateOnly = $timeData['date_only'];
            
            // For overnight shifts, adjust timestamp if time is early morning of next day
            $adjustedTimestamp = $timestamp;
            if ($isOvernightShift) {
                // If the time is early morning (00:00 - 11:59) and it's the next day, or
                // if the time is early morning and we expect it to be next day
                if ($timeOnly < '12:00:00' && 
                    ($dateOnly > $date || 
                     ($dateOnly === $date && $shiftEnd < '12:00:00'))) {
                    $adjustedTimestamp += 24 * 3600; // Treat as next day
                }
            }

            // Find closest to shift start (jam masuk)
            // Jam masuk should be around shift start time, not too early or too late
            $masukDiff = abs($adjustedTimestamp - $shiftStartTimestamp);
            if ($masukDiff < $minMasukDiff) {
                $hoursDiff = $masukDiff / 3600;
                // For jam masuk, allow up to 4 hours before/after shift start
                if ($hoursDiff <= 4) {
                    // Prefer times that are after shift start (not too early)
                    $timeDiff = $adjustedTimestamp - $shiftStartTimestamp;
                    if ($timeDiff >= -2 * 3600) { // Not more than 2 hours early
                        $minMasukDiff = $masukDiff;
                        $bestMasuk = $timeData['original'];
                    }
                }
            }

            // Find closest to shift end (jam keluar)
            // Jam keluar should be around shift end time
            $keluarDiff = abs($adjustedTimestamp - $shiftEndTimestamp);
            if ($keluarDiff < $minKeluarDiff) {
                $hoursDiff = $keluarDiff / 3600;
                // For jam keluar, allow up to 6 hours before/after shift end
                if ($hoursDiff <= 6) {
                    // Prefer times that are after shift end (indicating completion)
                    $timeDiff = $adjustedTimestamp - $shiftEndTimestamp;
                    if ($timeDiff >= -1 * 3600) { // Not more than 1 hour early
                        $minKeluarDiff = $keluarDiff;
                        $bestKeluar = $timeData['original'];
                    }
                }
            }
        }

        // Enhanced fallback logic
        if (!$bestMasuk || !$bestKeluar) {
            if ($isOvernightShift) {
                // For overnight shifts, be smarter about fallback
                $afternoonTimes = [];
                $morningTimes = [];
                
                foreach ($timeStamps as $timeData) {
                    $timeOnly = $timeData['time_only'];
                    if ($timeOnly >= '12:00:00') {
                        $afternoonTimes[] = $timeData;
                    } else {
                        $morningTimes[] = $timeData;
                    }
                }
                
                // Use earliest afternoon time for masuk if not found
                if (!$bestMasuk && !empty($afternoonTimes)) {
                    $bestMasuk = $afternoonTimes[0]['original'];
                } elseif (!$bestMasuk) {
                    $bestMasuk = $timeStamps[0]['original'];
                }
                
                // Use latest morning time or latest afternoon time for keluar
                if (!$bestKeluar && !empty($morningTimes)) {
                    $bestKeluar = end($morningTimes)['original'];
                } elseif (!$bestKeluar) {
                    $bestKeluar = end($timeStamps)['original'];
                }
            } else {
                // Regular shift fallback
                if (!$bestMasuk) {
                    $bestMasuk = $timeStamps[0]['original']; // Earliest time
                }
                if (!$bestKeluar) {
                    $bestKeluar = end($timeStamps)['original']; // Latest time
                }
            }
        }

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
        
        foreach ($records as $record) {
            $isLate = false;
            $hasOvertimeFlag = false;
            
            // Check if late using new helper function
            $jamMasuk = $record->jam_masuk ? (explode(' ', $record->jam_masuk)[1] ?? $record->jam_masuk) : null;
            $shiftStart = $record->shift_start ?? null;
            
            if ($this->isLate($jamMasuk, $shiftStart, $record->date)) {
                $lateCount++;
                $isLate = true;
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
        
        return response()->json([
            'total_records' => $totalEmployees,
            'late_count' => $lateCount,
            'overtime_count' => $overtimeCount,
            'on_time_count' => $onTimeCount,
            'late_percentage' => $totalEmployees > 0 ? round(($lateCount / $totalEmployees) * 100, 1) : 0,
            'overtime_percentage' => $totalEmployees > 0 ? round(($overtimeCount / $totalEmployees) * 100, 1) : 0,
            'on_time_percentage' => $totalEmployees > 0 ? round(($onTimeCount / $totalEmployees) * 100, 1) : 0,
        ]);
    }

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
                
                foreach ($dates as $date => $times) {
                    // Get shift schedule first
                    $schedule = EmployeeSchedule::where('employee_id', $employee->id)
                        ->where('date', $date)
                        ->with('shift')
                        ->first();
                    
                    $shiftStart = $schedule?->shift?->start_time;
                    $shiftEnd = $schedule?->shift?->end_time;
                    
                    // Use smart time selection based on shift schedule
                    [$jamMasuk, $jamKeluar] = $this->findBestAttendanceTimes($times, $shiftStart, $shiftEnd, $date);
                    
                    // Calculate work hours using the new helper function
                    $workHour = $this->calculateWorkHours($jamMasuk, $jamKeluar, $date);
                    
                    // Log the selection for debugging
                    Log::info("Smart time selection for {$employee->nama} on {$date}", [
                        'available_times' => $times,
                        'shift' => $shiftStart . '-' . $shiftEnd,
                        'selected_masuk' => $jamMasuk,
                        'selected_keluar' => $jamKeluar,
                        'work_hours' => $workHour
                    ]);
                    
                    AttendanceRekap::updateOrCreate(
                        [
                            'finger_id' => $fingerId,
                            'date' => $date,
                        ],
                        [
                            'employee_id' => $employee->id,
                            'jam_masuk' => $jamMasuk,
                            'jam_keluar' => $jamKeluar,
                            'shift_start' => $shiftStart,
                            'shift_end' => $shiftEnd,
                            'work_hour' => $workHour,
                        ]
                    );
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
     * Re-process attendance records with smart time selection
     */
    public function reprocessAttendanceTimes()
    {
        try {
            $processedCount = 0;
            
            // Group attendance records by employee and date to simulate upload data
            $attendanceRecords = AttendanceRekap::with(['employee'])
                ->whereNotNull('employee_id')
                ->get()
                ->groupBy(['employee_id', 'date']);
            
            foreach ($attendanceRecords as $employeeId => $dateGroups) {
                foreach ($dateGroups as $date => $records) {
                    // Skip if only one record (no multiple times to choose from)
                    if ($records->count() <= 1) {
                        continue;
                    }
                    
                    $employee = $records->first()->employee;
                    
                    // Get all attendance times for this employee on this date
                    $times = [];
                    foreach ($records as $record) {
                        if ($record->jam_masuk) $times[] = $record->jam_masuk;
                        if ($record->jam_keluar) $times[] = $record->jam_keluar;
                    }
                    
                    // Remove duplicates and sort
                    $times = array_unique($times);
                    sort($times);
                    
                    if (count($times) < 2) {
                        continue; // Need at least 2 different times
                    }
                    
                    // Get shift schedule
                    $schedule = EmployeeSchedule::where('employee_id', $employeeId)
                        ->where('date', $date)
                        ->with('shift')
                        ->first();
                    
                    $shiftStart = $schedule?->shift?->start_time;
                    $shiftEnd = $schedule?->shift?->end_time;
                    
                    // Use smart time selection
                    [$jamMasuk, $jamKeluar] = $this->findBestAttendanceTimes($times, $shiftStart, $shiftEnd, $date);
                    
                    // Calculate work hours
                    $workHour = $this->calculateWorkHours($jamMasuk, $jamKeluar, $date);
                    
                    // Update the main record (use the first one found)
                    $mainRecord = $records->first();
                    $oldMasuk = $mainRecord->jam_masuk;
                    $oldKeluar = $mainRecord->jam_keluar;
                    
                    if ($oldMasuk !== $jamMasuk || $oldKeluar !== $jamKeluar) {
                        $mainRecord->update([
                            'jam_masuk' => $jamMasuk,
                            'jam_keluar' => $jamKeluar,
                            'work_hour' => $workHour,
                        ]);
                        
                        Log::info("Reprocessed attendance for {$employee->nama} on {$date}", [
                            'old_masuk' => $oldMasuk,
                            'old_keluar' => $oldKeluar,
                            'new_masuk' => $jamMasuk,
                            'new_keluar' => $jamKeluar,
                            'available_times' => $times,
                            'shift' => $shiftStart . '-' . $shiftEnd
                        ]);
                        
                        $processedCount++;
                        
                        // Delete duplicate records for the same employee/date
                        $records->skip(1)->each(function($record) {
                            $record->delete();
                        });
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Reprocessed {$processedCount} attendance records with smart time selection. Check logs for details.",
                'processed_count' => $processedCount
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
