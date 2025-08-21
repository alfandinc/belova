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
    $query = AttendanceRekap::with(['employee', 'employeeSchedule.shift']);
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
                // Try relationship first
                if ($row->employeeSchedule && $row->employeeSchedule->shift) {
                    $shiftName = $row->employeeSchedule->shift->name ?? '';
                } else {
                    // Fallback: fetch EmployeeSchedule and Shift manually
                    $schedule = \App\Models\HRD\EmployeeSchedule::where('employee_id', $row->employee_id)
                        ->where('date', $row->date)
                        ->with('shift')
                        ->first();
                    if ($schedule && $schedule->shift) {
                        $shiftName = $schedule->shift->name ?? '';
                    }
                }
                if ($shiftName) {
                    return $shiftName . ' (' . $start . '-' . $end . ')';
                } elseif ($start || $end) {
                    return '(' . $start . '-' . $end . ')';
                }
                return '';
            })
            ->make(true);
    }

    public function index()
    {
        return view('hrd.absensi_rekap.index');
    }

    public function statistics(Request $request)
    {
        $query = AttendanceRekap::with(['employee', 'employeeSchedule.shift']);
        
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
}
