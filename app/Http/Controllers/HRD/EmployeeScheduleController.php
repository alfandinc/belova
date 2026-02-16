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
    /**
     * Delete a schedule entry for an employee and date
     */
    public function delete(Request $request)
    {
        $employeeId = $request->input('employee_id');
        $date = $request->input('date');
        $scheduleId = $request->input('schedule_id');
        if (!$employeeId || !$date) {
            return response()->json(['success' => false, 'message' => 'Missing employee_id or date'], 400);
        }

        $query = EmployeeSchedule::where('employee_id', $employeeId)
            ->where('date', $date);

        // Jika ada schedule_id, hapus hanya jadwal tersebut.
        if ($scheduleId) {
            $query->where('id', $scheduleId);
        }

        $deleted = $query->delete();
        if ($request->ajax()) {
            return response()->json(['success' => $deleted > 0]);
        }
        return redirect()->back()->with('success', $deleted ? 'Jadwal dihapus' : 'Jadwal tidak ditemukan');
    }
    // Display schedule table for a week
    public function index(Request $request)
    {
        $startOfWeek = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfWeek() : Carbon::now()->startOfWeek();
        $dates = collect(range(0, 6))->map(fn($i) => $startOfWeek->copy()->addDays($i)->toDateString()); // array of Y-m-d
        // Ambil employee beserta user dan roles, urutkan per nama
        $employees = Employee::with(['user.roles'])
            ->whereRaw('LOWER(status) <> ?', ['tidak aktif'])
            ->orderBy('nama')
            ->get();
        
        // Define role priority order as requested
        $rolePriority = [
            'CEO' => 1,
            'Ceo' => 1,
            'Hrd' => 2,
            'Manager' => 3,
            'Admin' => 4,
            'Marketing' => 5,
            'Kasir' => 6,
            'Inventaris' => 7,
            'Farmasi' => 8,
            'Beautician' => 9,
            'Perawat' => 10,
            'Dokter' => 11,
            'Pendaftaran' => 12,
            'Lab' => 13,
            'Employee' => 14
        ];
        
        // Define role display labels
        $roleDisplayLabels = [
            'Hrd' => 'HRD',
            'Manager' => 'Manager on Duty',
            'Admin' => 'IT',
            'Marketing' => 'Marketing & FO',
            'Kasir' => 'Kasir & Akunting',
            'Inventaris' => 'Inventaris',
            'Farmasi' => 'Farmasi',
            'Beautician' => 'Beautician',
            'Perawat' => 'Perawat',
            'CEO' => 'CEO',
            'Ceo' => 'CEO',
            'Dokter' => 'Dokter',
            'Pendaftaran' => 'Pendaftaran',
            'Lab' => 'Lab',
            'Employee' => 'Lain-Lain'
        ];
        
        // Kelompokkan per role berdasarkan prioritas tertinggi
        $employeesByDivision = $employees->groupBy(function($emp) use ($rolePriority, $roleDisplayLabels){
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
            
            // Return display label instead of role name
            return $roleDisplayLabels[$highestPriorityRole] ?? ($highestPriorityRole ?? 'Lainnya');
        });
        
        // Urutkan dalam setiap grup berdasarkan nama
        $employeesByDivision = $employeesByDivision->map(function($group){
            return $group->sortBy('nama')->values();
        });
        
        // Urutkan grup berdasarkan prioritas role
        $employeesByDivision = $employeesByDivision->sortBy(function($group, $displayLabel) use ($rolePriority, $roleDisplayLabels) {
            // Find the original role name from display label
            $originalRole = array_search($displayLabel, $roleDisplayLabels);
            return $rolePriority[$originalRole] ?? 999;
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
            foreach ($days as $date => $shiftIds) {
                $normalizedDate = Carbon::parse($date)->toDateString();

                // Hapus semua jadwal existing untuk karyawan & tanggal ini,
                // lalu simpan kembali berdasarkan input (bisa 0, 1, atau 2 shift).
                EmployeeSchedule::where('employee_id', $employeeId)
                    ->where('date', $normalizedDate)
                    ->delete();

                $shiftIds = array_values(array_filter((array) $shiftIds)); // buang yang kosong

                foreach ($shiftIds as $shiftId) {
                    EmployeeSchedule::create([
                        'employee_id' => $employeeId,
                        'date'        => $normalizedDate,
                        'shift_id'    => $shiftId,
                    ]);
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
        $employees = Employee::with(['user.roles'])
            ->whereRaw('LOWER(status) <> ?', ['tidak aktif'])
            ->orderBy('nama')
            ->get();
        
        // Define role priority order as requested
        $rolePriority = [
            'CEO' => 1,
            'Ceo' => 1,
            'Hrd' => 2,
            'Manager' => 3,
            'Admin' => 4,
            'Marketing' => 5,
            'Kasir' => 6,
            'Inventaris' => 7,
            'Farmasi' => 8,
            'Beautician' => 9,
            'Perawat' => 10,
            'Dokter' => 11,
            'Pendaftaran' => 12,
            'Lab' => 13,
            'Employee' => 14
        ];
        
        // Define role display labels
        $roleDisplayLabels = [
            'Hrd' => 'HRD',
            'Manager' => 'Manager on Duty',
            'Admin' => 'IT',
            'Marketing' => 'Marketing & FO',
            'Kasir' => 'Kasir & Akunting',
            'Inventaris' => 'Inventaris',
            'Farmasi' => 'Farmasi',
            'Beautician' => 'Beautician',
            'Perawat' => 'Perawat',
            'CEO' => 'CEO',
            'Ceo' => 'CEO',
            'Dokter' => 'Dokter',
            'Pendaftaran' => 'Pendaftaran',
            'Lab' => 'Lab',
            'Employee' => 'Lain-Lain'
        ];
        
        $employeesByDivision = $employees->groupBy(function($emp) use ($rolePriority, $roleDisplayLabels){
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
            
            // Return display label instead of role name
            return $roleDisplayLabels[$highestPriorityRole] ?? ($highestPriorityRole ?? 'Lainnya');
        })->map(function($group){
            return $group->sortBy('nama')->values();
        });
        
        // Urutkan grup berdasarkan prioritas role
        $employeesByDivision = $employeesByDivision->sortBy(function($group, $displayLabel) use ($rolePriority, $roleDisplayLabels) {
            // Find the original role name from display label
            $originalRole = array_search($displayLabel, $roleDisplayLabels);
            return $rolePriority[$originalRole] ?? 999;
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
        
        // Filter out role groups that have no employees with schedule data
        $employeesByDivision = $employeesByDivision->filter(function($employees, $roleName) use ($schedules, $dates) {
            // Check if any employee in this role group has schedule data
            foreach ($employees as $employee) {
                foreach ($dates as $date) {
                    $key = $employee->id . '_' . $date;
                    if (isset($schedules[$key])) {
                        return true; // Found at least one employee with schedule data
                    }
                }
            }
            return false; // No employees in this group have schedule data
        });
        
        $viewData = compact('dates', 'employeesByDivision', 'shifts', 'schedules', 'startOfWeek');

        $pdf = \PDF::loadView('hrd.schedule.print', $viewData)->setPaper('A4', 'landscape');
        return $pdf->stream('jadwal_karyawan_mingguan.pdf');
    }


}
