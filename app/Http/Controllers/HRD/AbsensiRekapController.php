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
    public function update(Request $request, $id)
    {
        \Log::info('AbsensiRekapController@update called', ['id' => $id, 'data' => $request->all()]);
        $rekap = AttendanceRekap::findOrFail($id);
        $request->validate([
            'jam_masuk' => 'required',
            'jam_keluar' => 'required',
        ]);
        // Format jam masuk/keluar as time only
        $date = $rekap->date;
        $jamMasuk = $date . ' ' . $request->jam_masuk;
        $jamKeluar = $date . ' ' . $request->jam_keluar;
        // Recalculate work hour
        $start = strtotime($jamMasuk);
        $end = strtotime($jamKeluar);
        $workHour = 0;
        if ($start && $end && $end > $start) {
            $workHour = round(($end - $start) / 3600, 2);
        }
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
                    // Calculate Terlambat
                    if ($jamMasuk && $shiftStart && $jamMasuk > $shiftStart) {
                        $start = strtotime($shiftStart);
                        $masuk = strtotime($jamMasuk);
                        $diff = $masuk - $start;
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
                    // Calculate Over Time
                    if ($jamKeluar && $shiftEnd && $jamKeluar > $shiftEnd) {
                        $end = strtotime($shiftEnd);
                        $keluar = strtotime($jamKeluar);
                        $diff = $keluar - $end;
                        $minutes = round($diff / 60);
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
            $hasOvertime = false;
            
            // Check if late
            $jamMasuk = $record->jam_masuk ? (explode(' ', $record->jam_masuk)[1] ?? $record->jam_masuk) : null;
            $shiftStart = $record->shift_start ?? null;
            
            if ($jamMasuk && $shiftStart && $jamMasuk > $shiftStart) {
                $lateCount++;
                $isLate = true;
            }
            
            // Check if overtime
            $jamKeluar = $record->jam_keluar ? (explode(' ', $record->jam_keluar)[1] ?? $record->jam_keluar) : null;
            $shiftEnd = $record->shift_end ?? null;
            
            if ($jamKeluar && $shiftEnd && $jamKeluar > $shiftEnd) {
                $overtimeCount++;
                $hasOvertime = true;
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
                    $jamMasuk = min($times);
                    $jamKeluar = max($times);
                    $schedule = EmployeeSchedule::where('employee_id', $employee?->id)
                        ->where('date', $date)
                        ->first();
                    $shiftStart = $schedule?->shift?->start_time;
                    $shiftEnd = $schedule?->shift?->end_time;
                    $workHour = 0;
                    if ($jamMasuk && $jamKeluar && $jamMasuk !== $jamKeluar) {
                        $jmParts = explode(' ', $jamMasuk);
                        $jkParts = explode(' ', $jamKeluar);
                        if (count($jmParts) === 2 && count($jkParts) === 2) {
                            $jmDate = $jmParts[0]; $jmTime = $jmParts[1];
                            $jkDate = $jkParts[0]; $jkTime = $jkParts[1];
                            $jmDateParts = explode('/', $jmDate);
                            $jkDateParts = explode('/', $jkDate);
                            if (count($jmDateParts) === 3 && count($jkDateParts) === 3) {
                                $jmFormatted = $jmDateParts[2] . '-' . $jmDateParts[1] . '-' . $jmDateParts[0] . ' ' . $jmTime;
                                $jkFormatted = $jkDateParts[2] . '-' . $jkDateParts[1] . '-' . $jkDateParts[0] . ' ' . $jkTime;
                                $start = strtotime($jmFormatted);
                                $end = strtotime($jkFormatted);
                                if ($start && $end && $end > $start) {
                                    $workHour = round(($end - $start) / 3600, 2);
                                }
                            }
                        }
                    }
                    AttendanceRekap::updateOrCreate(
                        [
                            'finger_id' => $fingerId,
                            'date' => $date,
                        ],
                        [
                            'employee_id' => $employee?->id,
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
            
            // Get all attendance records
            $attendanceRecords = AttendanceRekap::with('employee')->get();
            
            foreach ($attendanceRecords as $record) {
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
                        $record->update([
                            'shift_start' => $actualStart,
                            'shift_end' => $actualEnd
                        ]);
                        $updatedCount++;
                    }
                } else {
                    $missingScheduleCount++;
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Sync completed! Updated: {$updatedCount} records. Missing schedules: {$missingScheduleCount} records.",
                'updated_count' => $updatedCount,
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
}
