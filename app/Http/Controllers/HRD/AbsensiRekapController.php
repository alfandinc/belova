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
                $processedOvernightDates = []; // Track which dates were processed as overnight shifts
                
                foreach ($dates as $date => $times) {
                    // Skip if this date was already processed as part of an overnight shift
                    if (in_array($date, $processedOvernightDates)) {
                        continue;
                    }
                    
                    // Get shift schedule first
                    $schedule = EmployeeSchedule::where('employee_id', $employee->id)
                        ->where('date', $date)
                        ->with('shift')
                        ->first();
                    
                    $shiftStart = $schedule?->shift?->start_time;
                    $shiftEnd = $schedule?->shift?->end_time;
                    
                    // Check if this is an overnight shift
                    $isOvernightShift = $shiftStart && $shiftEnd && $this->isOvernightShift($shiftStart, $shiftEnd);
                    
                    if ($isOvernightShift) {
                        // For overnight shifts, merge times from current and next date
                        $nextDate = date('Y-m-d', strtotime($date . ' +1 day'));
                        $allAvailableTimes = $times;
                        
                        if (isset($dates[$nextDate])) {
                            // Check if next date has its own shift schedule
                            $nextSchedule = EmployeeSchedule::where('employee_id', $employee->id)
                                ->where('date', $nextDate)
                                ->with('shift')
                                ->first();
                            
                            if ($nextSchedule && $nextSchedule->shift) {
                                // Next date has its own shift, so only use early times from next date (for overnight end)
                                $nextDateTimes = $dates[$nextDate];
                                $earlyTimesFromNextDate = array_filter($nextDateTimes, function($time) use ($nextDate) {
                                    $timeOnly = date('H:i:s', strtotime($time));
                                    return $timeOnly <= '06:00:00'; // Only early morning times for overnight end
                                });
                                $allAvailableTimes = array_merge($times, $earlyTimesFromNextDate);
                            } else {
                                // Next date has no shift, use all times from next date
                                $allAvailableTimes = array_merge($times, $dates[$nextDate]);
                                $processedOvernightDates[] = $nextDate;
                            }
                        }
                        
                        // Use smart time selection for overnight shift
                        [$jamMasuk, $jamKeluar] = $this->findBestAttendanceTimes($allAvailableTimes, $shiftStart, $shiftEnd, $date);
                        
                        // Log the selection for debugging
                        Log::info("Overnight shift processing for {$employee->nama} on {$date}", [
                            'current_date_times' => $times,
                            'next_date_times' => isset($dates[$nextDate]) ? $dates[$nextDate] : [],
                            'all_available_times' => $allAvailableTimes,
                            'shift' => $shiftStart . '-' . $shiftEnd,
                            'selected_masuk' => $jamMasuk,
                            'selected_keluar' => $jamKeluar,
                            'next_date_has_own_shift' => isset($nextSchedule) && $nextSchedule && $nextSchedule->shift,
                            'processed_overnight_dates' => $processedOvernightDates
                        ]);
                    } else {
                        // For regular shifts, use only current date times
                        [$jamMasuk, $jamKeluar] = $this->findBestAttendanceTimes($times, $shiftStart, $shiftEnd, $date);
                        
                        // Log the selection for debugging
                        Log::info("Regular shift processing for {$employee->nama} on {$date}", [
                            'available_times' => $times,
                            'shift' => $shiftStart . '-' . $shiftEnd,
                            'selected_masuk' => $jamMasuk,
                            'selected_keluar' => $jamKeluar
                        ]);
                    }
                    
                    // Calculate work hours using the new helper function
                    $workHour = $this->calculateWorkHours($jamMasuk, $jamKeluar, $date);
                    
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
