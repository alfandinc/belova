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
        // Kelompokkan per role
        $employeesByDivision = $employees->groupBy(function($emp){
            if (!$emp->user || !$emp->user->roles->count()) {
                return 'Tanpa Role';
            }
            // Ambil role pertama sebagai pengelompokan utama
            return $emp->user->roles->first()->name;
        });
        // Urutkan berdasarkan hierarki role dan nama
        $employeesByDivision = $employeesByDivision->map(function($group){
            return $group->sort(function($a, $b){
                // CEO di atas, kemudian Manager, lalu role lainnya, diurutkan nama
                if($a->isCEO() && !$b->isCEO()) return -1;
                if(!$a->isCEO() && $b->isCEO()) return 1;
                if($a->isManager() && !$b->isManager()) return -1;
                if(!$a->isManager() && $b->isManager()) return 1;
                return strcmp($a->nama, $b->nama);
            })->values();
        });
        // Urutkan grup berdasarkan prioritas role
        $roleOrder = ['CEO', 'Ceo', 'Manager', 'Hrd', 'Dokter', 'Perawat', 'Farmasi', 'Kasir', 'Pendaftaran', 'Lab', 'Beautician', 'Marketing', 'Admin', 'Employee', 'Inventaris'];
        $employeesByDivision = $employeesByDivision->sortBy(function($group, $roleName) use ($roleOrder) {
            $index = array_search($roleName, $roleOrder);
            return $index !== false ? $index : 999;
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
        $employeesByDivision = $employees->groupBy(function($emp){
            if (!$emp->user || !$emp->user->roles->count()) {
                return 'Tanpa Role';
            }
            // Ambil role pertama sebagai pengelompokan utama
            return $emp->user->roles->first()->name;
        })->map(function($group){
            return $group->sort(function($a, $b){
                // CEO di atas, kemudian Manager, lalu role lainnya, diurutkan nama
                if($a->isCEO() && !$b->isCEO()) return -1;
                if(!$a->isCEO() && $b->isCEO()) return 1;
                if($a->isManager() && !$b->isManager()) return -1;
                if(!$a->isManager() && $b->isManager()) return 1;
                return strcmp($a->nama, $b->nama);
            })->values();
        });
        // Urutkan grup berdasarkan prioritas role
        $roleOrder = ['CEO', 'Ceo', 'Manager', 'Hrd', 'Dokter', 'Perawat', 'Farmasi', 'Kasir', 'Pendaftaran', 'Lab', 'Beautician', 'Marketing', 'Admin', 'Employee', 'Inventaris'];
        $employeesByDivision = $employeesByDivision->sortBy(function($group, $roleName) use ($roleOrder) {
            $index = array_search($roleName, $roleOrder);
            return $index !== false ? $index : 999;
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
