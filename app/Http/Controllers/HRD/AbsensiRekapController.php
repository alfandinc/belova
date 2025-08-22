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
        $minMasukDiff = PHP_INT_MAX;
        $minKeluarDiff = PHP_INT_MAX;

        // Create target timestamps for shift start and end
        $shiftStartTarget = strtotime($date . ' ' . $shiftStart);
        
        // For overnight shifts, shift end is next day
        $nextDate = date('Y-m-d', strtotime($date . ' +1 day'));
        $shiftEndTarget = $isOvernightShift ? 
            strtotime($nextDate . ' ' . $shiftEnd) : 
            strtotime($date . ' ' . $shiftEnd);

        // Find best jam masuk (closest to shift start on the same date)
        foreach ($timeRecords as $record) {
            // For jam masuk, prefer times on the shift date
            if ($record['date_only'] === $date) {
                $diff = abs($record['timestamp'] - $shiftStartTarget);
                $hoursDiff = $diff / 3600;
                
                // Allow up to 4 hours difference from shift start
                if ($hoursDiff <= 4 && $diff < $minMasukDiff) {
                    $minMasukDiff = $diff;
                    $bestMasuk = $record['original'];
                }
            }
        }

        // Find best jam keluar
        if ($isOvernightShift) {
            // For overnight shifts, prefer times on the next date for jam keluar
            foreach ($timeRecords as $record) {
                // Check both same date (late times) and next date (early times)
                $targetTimestamp = $shiftEndTarget;
                
                if ($record['date_only'] === $nextDate) {
                    // Time is on next date - perfect for overnight shift end
                    $diff = abs($record['timestamp'] - $targetTimestamp);
                } elseif ($record['date_only'] === $date && $record['time_only'] >= '20:00:00') {
                    // Time is on same date but late (might be close to midnight)
                    $diff = abs($record['timestamp'] - $targetTimestamp);
                } else {
                    continue; // Skip times that don't make sense for jam keluar
                }
                
                $hoursDiff = $diff / 3600;
                
                // Allow up to 6 hours difference from shift end
                if ($hoursDiff <= 6 && $diff < $minKeluarDiff) {
                    $minKeluarDiff = $diff;
                    $bestKeluar = $record['original'];
                }
            }
        } else {
            // For regular shifts, find jam keluar on the same date
            foreach ($timeRecords as $record) {
                if ($record['date_only'] === $date) {
                    $diff = abs($record['timestamp'] - $shiftEndTarget);
                    $hoursDiff = $diff / 3600;
                    
                    // Allow up to 6 hours difference from shift end
                    if ($hoursDiff <= 6 && $diff < $minKeluarDiff) {
                        $minKeluarDiff = $diff;
                        $bestKeluar = $record['original'];
                    }
                }
            }
        }

        // Enhanced fallback logic
        if (!$bestMasuk) {
            // Find the latest time on the shift date that could be jam masuk
            $sameDateTimes = array_filter($timeRecords, function($record) use ($date) {
                return $record['date_only'] === $date;
            });
            
            if (!empty($sameDateTimes)) {
                // For jam masuk, prefer times in the afternoon/evening if it's night shift
                if ($isOvernightShift) {
                    $afternoonTimes = array_filter($sameDateTimes, function($record) {
                        return $record['time_only'] >= '12:00:00';
                    });
                    $bestMasuk = !empty($afternoonTimes) ? 
                        reset($afternoonTimes)['original'] : 
                        reset($sameDateTimes)['original'];
                } else {
                    $bestMasuk = reset($sameDateTimes)['original'];
                }
            } else {
                $bestMasuk = $timeRecords[0]['original'];
            }
        }

        if (!$bestKeluar) {
            if ($isOvernightShift) {
                // For overnight shifts, prefer times on next date
                $nextDateTimes = array_filter($timeRecords, function($record) use ($nextDate) {
                    return $record['date_only'] === $nextDate;
                });
                
                if (!empty($nextDateTimes)) {
                    // Prefer early morning times on next date
                    $morningTimes = array_filter($nextDateTimes, function($record) {
                        return $record['time_only'] <= '08:00:00';
                    });
                    $bestKeluar = !empty($morningTimes) ? 
                        reset($morningTimes)['original'] : 
                        reset($nextDateTimes)['original'];
                } else {
                    // Fallback to latest time on same date
                    $sameDateTimes = array_filter($timeRecords, function($record) use ($date) {
                        return $record['date_only'] === $date;
                    });
                    $bestKeluar = !empty($sameDateTimes) ? 
                        end($sameDateTimes)['original'] : 
                        end($timeRecords)['original'];
                }
            } else {
                // For regular shifts, use latest time on same date
                $sameDateTimes = array_filter($timeRecords, function($record) use ($date) {
                    return $record['date_only'] === $date;
                });
                $bestKeluar = !empty($sameDateTimes) ? 
                    end($sameDateTimes)['original'] : 
                    end($timeRecords)['original'];
            }
        }

        // Log the selection process for debugging
        Log::info("Smart time selection process", [
            'date' => $date,
            'shift' => $shiftStart . '-' . $shiftEnd,
            'is_overnight' => $isOvernightShift,
            'available_times' => array_column($timeRecords, 'formatted_datetime'),
            'selected_masuk' => $bestMasuk,
            'selected_keluar' => $bestKeluar,
            'shift_start_target' => date('Y-m-d H:i:s', $shiftStartTarget),
            'shift_end_target' => date('Y-m-d H:i:s', $shiftEndTarget)
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
                
                // Sort dates to process them in order
                ksort($dates);
                
                foreach ($dates as $date => $times) {
                    // Get shift schedule first
                    $schedule = EmployeeSchedule::where('employee_id', $employee->id)
                        ->where('date', $date)
                        ->with('shift')
                        ->first();
                    
                    $shiftStart = $schedule?->shift?->start_time;
                    $shiftEnd = $schedule?->shift?->end_time;
                    
                    // For overnight shifts, we need to consider times from next date too
                    $allAvailableTimes = $times;
                    if ($shiftStart && $shiftEnd && $this->isOvernightShift($shiftStart, $shiftEnd)) {
                        $nextDate = date('Y-m-d', strtotime($date . ' +1 day'));
                        if (isset($dates[$nextDate])) {
                            // Merge times from next date for cross-date selection
                            $allAvailableTimes = array_merge($times, $dates[$nextDate]);
                        }
                    }
                    
                    // Use smart time selection based on shift schedule with all available times
                    [$jamMasuk, $jamKeluar] = $this->findBestAttendanceTimes($allAvailableTimes, $shiftStart, $shiftEnd, $date);
                    
                    // Calculate work hours using the new helper function
                    $workHour = $this->calculateWorkHours($jamMasuk, $jamKeluar, $date);
                    
                    // Log the selection for debugging
                    Log::info("Smart time selection for {$employee->nama} on {$date}", [
                        'current_date_times' => $times,
                        'all_available_times' => $allAvailableTimes,
                        'shift' => $shiftStart . '-' . $shiftEnd,
                        'selected_masuk' => $jamMasuk,
                        'selected_keluar' => $jamKeluar,
                        'work_hours' => $workHour,
                        'is_overnight_shift' => $shiftStart && $shiftEnd ? $this->isOvernightShift($shiftStart, $shiftEnd) : false
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
     * Handles cross-date selection for overnight shifts
     */
    public function reprocessAttendanceTimes()
    {
        try {
            $processedCount = 0;
            
            // Get all attendance records grouped by employee and date
            $attendanceRecords = AttendanceRekap::with(['employee'])
                ->whereNotNull('employee_id')
                ->orderBy('employee_id')
                ->orderBy('date')
                ->get();
            
            // Group by employee_id and date
            $groupedRecords = [];
            foreach ($attendanceRecords as $record) {
                $groupedRecords[$record->employee_id][$record->date][] = $record;
            }
            
            foreach ($groupedRecords as $employeeId => $dateGroups) {
                foreach ($dateGroups as $date => $records) {
                    $employee = $records[0]->employee;
                    
                    // Get shift schedule for this date
                    $schedule = EmployeeSchedule::where('employee_id', $employeeId)
                        ->where('date', $date)
                        ->with('shift')
                        ->first();
                    
                    if (!$schedule || !$schedule->shift) {
                        continue; // Skip if no schedule
                    }
                    
                    $shiftStart = $schedule->shift->start_time;
                    $shiftEnd = $schedule->shift->end_time;
                    $isOvernightShift = $this->isOvernightShift($shiftStart, $shiftEnd);
                    
                    // Collect all possible attendance times for this date and next date (for overnight shifts)
                    $availableTimes = [];
                    
                    // Add times from current date
                    foreach ($records as $record) {
                        if ($record->jam_masuk) $availableTimes[] = $record->jam_masuk;
                        if ($record->jam_keluar) $availableTimes[] = $record->jam_keluar;
                    }
                    
                    // For overnight shifts, also check next date records
                    if ($isOvernightShift) {
                        $nextDate = date('Y-m-d', strtotime($date . ' +1 day'));
                        $nextDateRecords = AttendanceRekap::where('employee_id', $employeeId)
                            ->where('date', $nextDate)
                            ->get();
                        
                        foreach ($nextDateRecords as $record) {
                            if ($record->jam_masuk) $availableTimes[] = $record->jam_masuk;
                            if ($record->jam_keluar) $availableTimes[] = $record->jam_keluar;
                        }
                    }
                    
                    // Remove duplicates and ensure we have enough times
                    $availableTimes = array_unique($availableTimes);
                    
                    if (count($availableTimes) < 2) {
                        continue; // Need at least 2 different times
                    }
                    
                    // Use smart time selection
                    [$jamMasuk, $jamKeluar] = $this->findBestAttendanceTimes($availableTimes, $shiftStart, $shiftEnd, $date);
                    
                    // Calculate work hours
                    $workHour = $this->calculateWorkHours($jamMasuk, $jamKeluar, $date);
                    
                    // Update the main record
                    $mainRecord = $records[0];
                    $oldMasuk = $mainRecord->jam_masuk;
                    $oldKeluar = $mainRecord->jam_keluar;
                    $oldWorkHour = $mainRecord->work_hour;
                    
                    if ($oldMasuk !== $jamMasuk || $oldKeluar !== $jamKeluar || $oldWorkHour !== $workHour) {
                        $mainRecord->update([
                            'jam_masuk' => $jamMasuk,
                            'jam_keluar' => $jamKeluar,
                            'work_hour' => $workHour,
                        ]);
                        
                        Log::info("Reprocessed attendance for {$employee->nama} on {$date}", [
                            'shift' => $shiftStart . '-' . $shiftEnd,
                            'is_overnight' => $isOvernightShift,
                            'old_masuk' => $oldMasuk,
                            'old_keluar' => $oldKeluar,
                            'old_work_hour' => $oldWorkHour,
                            'new_masuk' => $jamMasuk,
                            'new_keluar' => $jamKeluar,
                            'new_work_hour' => $workHour,
                            'available_times' => $availableTimes
                        ]);
                        
                        $processedCount++;
                    }
                    
                    // Remove duplicate records for the same employee/date (keep only the first one)
                    if (count($records) > 1) {
                        for ($i = 1; $i < count($records); $i++) {
                            $records[$i]->delete();
                        }
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Reprocessed {$processedCount} attendance records with smart cross-date selection. Check logs for details.",
                'processed_count' => $processedCount
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error in reprocessAttendanceTimes: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Test cross-date overnight shift time selection
     */
    public function testCrossDateSelection()
    {
        // Sofia's test data from the screenshot
        $testTimes = [
            '2025-08-13 15:58:44',
            '2025-08-13 16:00:13',
            '2025-08-14 00:00:30',
        ];
        
        $shiftStart = '16:00:00';
        $shiftEnd = '00:00:00';
        $date = '2025-08-13';
        
        [$jamMasuk, $jamKeluar] = $this->findBestAttendanceTimes($testTimes, $shiftStart, $shiftEnd, $date);
        
        return response()->json([
            'test_data' => [
                'available_times' => $testTimes,
                'shift' => $shiftStart . '-' . $shiftEnd,
                'date' => $date,
                'is_overnight_shift' => $this->isOvernightShift($shiftStart, $shiftEnd)
            ],
            'results' => [
                'selected_jam_masuk' => $jamMasuk,
                'selected_jam_keluar' => $jamKeluar,
                'expected_jam_masuk' => '2025-08-13 15:58:44',
                'expected_jam_keluar' => '2025-08-14 00:00:30',
                'test_passed' => [
                    'jam_masuk' => $jamMasuk === '2025-08-13 15:58:44',
                    'jam_keluar' => $jamKeluar === '2025-08-14 00:00:30'
                ]
            ]
        ]);
    }
}
