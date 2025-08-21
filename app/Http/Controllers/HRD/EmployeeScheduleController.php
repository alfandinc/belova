<?php
namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRD\Employee;
use App\Models\HRD\Shift;
use App\Models\HRD\EmployeeSchedule;
use Carbon\Carbon;

class EmployeeScheduleController extends Controller
{
    // Display schedule table for a week
    public function index(Request $request)
    {
        $startOfWeek = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfWeek() : Carbon::now()->startOfWeek();
        $dates = collect(range(0, 6))->map(fn($i) => $startOfWeek->copy()->addDays($i)->toDateString()); // array of Y-m-d
        // Ambil employee beserta user dan roles, urutkan per nama
        $employees = Employee::with(['user.roles'])->orderBy('nama')->get();
        
        // Define role priority order as requested
        $rolePriority = [
            'Hrd' => 1,
            'Manager' => 2,
            'Admin' => 3,
            'Marketing' => 4,
            'Kasir' => 5,
            'Inventaris' => 6,
            'Farmasi' => 7,
            'Beautician' => 8,
            'Perawat' => 9,
            'CEO' => 10,
            'Ceo' => 11,
            'Dokter' => 12,
            'Pendaftaran' => 13,
            'Lab' => 14,
            'Employee' => 15
        ];
        
        // Kelompokkan per role berdasarkan prioritas tertinggi
        $employeesByDivision = $employees->groupBy(function($emp) use ($rolePriority){
            if (!$emp->user || !$emp->user->roles->count()) {
                return 'Tanpa Role';
            }
            
            // Cari role dengan prioritas tertinggi (angka terkecil)
            $highestPriorityRole = null;
            $highestPriority = 999;
            
            foreach ($emp->user->roles as $role) {
                $priority = $rolePriority[$role->name] ?? 999;
                if ($priority < $highestPriority) {
                    $highestPriority = $priority;
                    $highestPriorityRole = $role->name;
                }
            }
            
            return $highestPriorityRole ?? 'Lainnya';
        });
        
        // Urutkan dalam setiap grup berdasarkan nama
        $employeesByDivision = $employeesByDivision->map(function($group){
            return $group->sortBy('nama')->values();
        });
        
        // Urutkan grup berdasarkan prioritas role
        $employeesByDivision = $employeesByDivision->sortBy(function($group, $roleName) use ($rolePriority) {
            return $rolePriority[$roleName] ?? 999;
        });
        $shifts = Shift::all();
        $schedules = EmployeeSchedule::whereIn('date', $dates)
            ->with('shift')
            ->get()
            ->groupBy(fn($item) => $item->employee_id.'_'.$item->date);

        // Integrate PengajuanLibur (approved by manager) into schedule
        $libur = \App\Models\HRD\PengajuanLibur::where('status_manager', 'disetujui')
            ->where(function($q) use ($dates) {
                $q->whereIn('tanggal_mulai', $dates)->orWhereIn('tanggal_selesai', $dates);
            })
            ->get();
        foreach ($libur as $cuti) {
            $empId = $cuti->employee_id;
            $start = \Carbon\Carbon::parse($cuti->tanggal_mulai);
            $end = \Carbon\Carbon::parse($cuti->tanggal_selesai);
            $label = strtolower($cuti->jenis_libur) == 'cuti_tahunan' ? 'Cuti' : 'Libur/Cuti';
            foreach ($dates as $date) {
                $cur = \Carbon\Carbon::parse($date);
                if ($cur->betweenIncluded($start, $end)) {
                    $key = $empId . '_' . $date;
                    $schedules[$key] = [ (object)[
                        'employee_id' => $empId,
                        'date' => $date,
                        'shift' => null,
                        'is_libur' => true,
                        'label' => $label
                    ] ];
                }
            }
        }
        $viewData = compact('dates', 'employeesByDivision', 'shifts', 'schedules', 'startOfWeek');
        if ($request->ajax()) {
            return view('hrd.schedule._table', $viewData)->render();
        }
        return view('hrd.schedule.index', $viewData);
    }

    // Store/update schedule for a week
    public function store(Request $request)
    {
        $data = $request->input('schedule', []);
        foreach ($data as $employeeId => $days) {
            foreach ($days as $date => $shiftId) {
                if ($shiftId) {
                    EmployeeSchedule::updateOrCreate(
                        ['employee_id' => $employeeId, 'date' => Carbon::parse($date)->toDateString()], // pastikan Y-m-d
                        ['shift_id' => $shiftId]
                    );
                }
            }
        }
        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('hrd.schedule.index')->with('success', 'Jadwal berhasil disimpan');
    }

        /**
     * Generate jadwal mingguan ke PDF
     */
    public function print(Request $request)
    {
        $startOfWeek = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfWeek() : Carbon::now()->startOfWeek();
        $dates = collect(range(0, 6))->map(fn($i) => $startOfWeek->copy()->addDays($i)->toDateString());
        $employees = Employee::with(['user.roles'])->orderBy('nama')->get();
        
        // Define role priority order as requested
        $rolePriority = [
            'Hrd' => 1,
            'Manager' => 2,
            'Admin' => 3,
            'Marketing' => 4,
            'Kasir' => 5,
            'Inventaris' => 6,
            'Farmasi' => 7,
            'Beautician' => 8,
            'Perawat' => 9,
            'CEO' => 10,
            'Ceo' => 11,
            'Dokter' => 12,
            'Pendaftaran' => 13,
            'Lab' => 14,
            'Employee' => 15
        ];
        
        $employeesByDivision = $employees->groupBy(function($emp) use ($rolePriority){
            if (!$emp->user || !$emp->user->roles->count()) {
                return 'Tanpa Role';
            }
            
            // Cari role dengan prioritas tertinggi (angka terkecil)
            $highestPriorityRole = null;
            $highestPriority = 999;
            
            foreach ($emp->user->roles as $role) {
                $priority = $rolePriority[$role->name] ?? 999;
                if ($priority < $highestPriority) {
                    $highestPriority = $priority;
                    $highestPriorityRole = $role->name;
                }
            }
            
            return $highestPriorityRole ?? 'Lainnya';
        })->map(function($group){
            return $group->sortBy('nama')->values();
        });
        
        // Urutkan grup berdasarkan prioritas role
        $employeesByDivision = $employeesByDivision->sortBy(function($group, $roleName) use ($rolePriority) {
            return $rolePriority[$roleName] ?? 999;
        });
        $shifts = Shift::all();
        $schedules = EmployeeSchedule::whereIn('date', $dates)
            ->with('shift')
            ->get()
            ->groupBy(fn($item) => $item->employee_id.'_'.$item->date);

        // Integrate PengajuanLibur (approved by manager) into schedule for PDF
        $libur = \App\Models\HRD\PengajuanLibur::where('status_manager', 'disetujui')
            ->where(function($q) use ($dates) {
                $q->whereIn('tanggal_mulai', $dates)->orWhereIn('tanggal_selesai', $dates);
            })
            ->get();
        foreach ($libur as $cuti) {
            $empId = $cuti->employee_id;
            $start = \Carbon\Carbon::parse($cuti->tanggal_mulai);
            $end = \Carbon\Carbon::parse($cuti->tanggal_selesai);
            $label = strtolower($cuti->jenis_libur) == 'cuti_tahunan' ? 'Cuti' : 'Libur/Cuti';
            foreach ($dates as $date) {
                $cur = \Carbon\Carbon::parse($date);
                if ($cur->betweenIncluded($start, $end)) {
                    $key = $empId . '_' . $date;
                    $schedules[$key] = [ (object)[
                        'employee_id' => $empId,
                        'date' => $date,
                        'shift' => null,
                        'is_libur' => true,
                        'label' => $label
                    ] ];
                }
            }
        }
        $viewData = compact('dates', 'employeesByDivision', 'shifts', 'schedules', 'startOfWeek');

        $pdf = \PDF::loadView('hrd.schedule.print', $viewData)->setPaper('A4', 'landscape');
        return $pdf->stream('jadwal_karyawan_mingguan.pdf');
    }


}
