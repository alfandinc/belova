<?php
namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRD\Employee;
use App\Models\HRD\Shift;
use App\Models\HRD\EmployeeSchedule;
use App\Models\HRD\Position;
use App\Models\HRD\PositionDivision;
use App\Models\HRD\JatahLibur;
use App\Models\HRD\LiburNasional;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
        // Kelompokkan per divisi, urut posisi tertinggi di atas
        $employeesByDivision = $this->groupEmployeesByDivision();
        // Shifts aktif untuk dropdown penjadwalan
        $activeShifts = Shift::where('active', true)->get();
        // Semua shift (aktif & tidak aktif) untuk manajemen shift
        $allShifts = Shift::all();
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
        $viewData = [
            'dates' => $dates,
            'employeesByDivision' => $employeesByDivision,
            'shifts' => $activeShifts,
            'allShifts' => $allShifts,
            'schedules' => $schedules,
            'startOfWeek' => $startOfWeek,
        ];
        if ($request->ajax()) {
            return view('hrd.schedule._table', $viewData)->render();
        }
        return view('hrd.schedule.index', $viewData);
    }

    // Store/update schedule for a week
    public function store(Request $request)
    {
        $data = $request->input('schedule', []);

        $gantiLibur = []; // employee_id => ['added' => [tanggal], 'removed' => [tanggal]]
        $liburNasional = LiburNasional::namesByDate(); // loaded once, checked per date below
        DB::transaction(function () use ($data, &$gantiLibur, $liburNasional) {
            foreach ($data as $employeeId => $days) {
                foreach ((array) $days as $date => $shiftIds) {
                    $normalizedDate = Carbon::parse($date)->toDateString();
                    $wasEmpty = !EmployeeSchedule::where('employee_id', $employeeId)->where('date', $normalizedDate)->exists();

                    // Hapus semua jadwal existing untuk karyawan & tanggal ini,
                    // lalu simpan kembali berdasarkan input (bisa 0, 1, atau 2 shift).
                    EmployeeSchedule::where('employee_id', $employeeId)
                        ->where('date', $normalizedDate)
                        ->delete();

                    $shiftIds = array_slice(array_values(array_unique(array_filter((array) $shiftIds))), 0, 2); // buang yang kosong, maks 2

                    foreach ($shiftIds as $shiftId) {
                        EmployeeSchedule::create([
                            'employee_id' => $employeeId,
                            'date'        => $normalizedDate,
                            'shift_id'    => $shiftId,
                        ]);
                    }

                    // Hari Minggu / libur nasional: jadwal baru = +1 jatah ganti libur, jadwal dihapus = -1
                    if (LiburNasional::isHariGantiLibur($normalizedDate, $liburNasional)) {
                        $isEmpty = count($shiftIds) === 0;
                        if ($wasEmpty !== $isEmpty) {
                            $jatah = JatahLibur::firstOrCreate(
                                ['employee_id' => $employeeId],
                                ['jatah_cuti_tahunan' => 0, 'jatah_ganti_libur' => 0]
                            );
                            if ($wasEmpty) {
                                $jatah->increment('jatah_ganti_libur');
                                $gantiLibur[$employeeId]['added'][] = $normalizedDate;
                            } else {
                                $jatah->jatah_ganti_libur = max(0, (int) $jatah->jatah_ganti_libur - 1);
                                $jatah->save();
                                $gantiLibur[$employeeId]['removed'][] = $normalizedDate;
                            }
                        }
                    }
                }
            }
        });
        if ($request->ajax()) {
            // Ringkasan per karyawan untuk notifikasi
            $summary = [];
            if ($gantiLibur) {
                $names = Employee::whereIn('id', array_keys($gantiLibur))->pluck('nama', 'id');
                $saldo = JatahLibur::whereIn('employee_id', array_keys($gantiLibur))->pluck('jatah_ganti_libur', 'employee_id');
                foreach ($gantiLibur as $employeeId => $changes) {
                    $summary[] = [
                        'nama' => $names[$employeeId] ?? ('#' . $employeeId),
                        'added' => array_map(fn($d) => Carbon::parse($d)->locale('id')->isoFormat('D MMM'), $changes['added'] ?? []),
                        'removed' => array_map(fn($d) => Carbon::parse($d)->locale('id')->isoFormat('D MMM'), $changes['removed'] ?? []),
                        'saldo' => (int) ($saldo[$employeeId] ?? 0),
                    ];
                }
            }
            return response()->json(['success' => true, 'ganti_libur' => $summary]);
        }
        return redirect()->route('hrd.schedule.index')->with('success', 'Jadwal berhasil disimpan');
    }

    /**
     * Copy schedules from a source week to a target week.
     * Default behavior: do NOT overwrite existing schedules in target week.
     */
    public function copyWeek(Request $request)
    {
        $validated = $request->validate([
            'target_start_date' => ['required', 'date'],
            'source_start_date' => ['nullable', 'date'],
            'overwrite' => ['nullable'],
        ]);

        $targetStart = Carbon::parse($validated['target_start_date'])->startOfWeek();
        $sourceStart = isset($validated['source_start_date']) && $validated['source_start_date']
            ? Carbon::parse($validated['source_start_date'])->startOfWeek()
            : $targetStart->copy()->subWeek();

        $overwrite = filter_var($request->input('overwrite', false), FILTER_VALIDATE_BOOLEAN);

        $targetDates = collect(range(0, 6))->map(fn($i) => $targetStart->copy()->addDays($i)->toDateString());
        $sourceDates = collect(range(0, 6))->map(fn($i) => $sourceStart->copy()->addDays($i)->toDateString());

        $employeeIds = Employee::whereRaw('LOWER(status) <> ?', ['tidak aktif'])->pluck('id');

        // Build a set of employee_id_date that should be treated as Libur/Cuti on target week
        $liburMap = [];
        $libur = \App\Models\HRD\PengajuanLibur::where('status_manager', 'disetujui')
            ->whereIn('employee_id', $employeeIds)
            ->where(function ($q) use ($targetDates) {
                $q->whereIn('tanggal_mulai', $targetDates)->orWhereIn('tanggal_selesai', $targetDates);
            })
            ->get();
        foreach ($libur as $cuti) {
            $empId = $cuti->employee_id;
            $start = Carbon::parse($cuti->tanggal_mulai);
            $end = Carbon::parse($cuti->tanggal_selesai);
            foreach ($targetDates as $date) {
                $cur = Carbon::parse($date);
                if ($cur->betweenIncluded($start, $end)) {
                    $liburMap[$empId . '_' . $date] = true;
                }
            }
        }

        $existingTarget = [];
        if (!$overwrite) {
            $existingTarget = EmployeeSchedule::whereIn('employee_id', $employeeIds)
                ->whereIn('date', $targetDates)
                ->get()
                ->groupBy(fn($item) => $item->employee_id . '_' . $item->date)
                ->toArray();
        }

        $sourceSchedules = EmployeeSchedule::whereIn('employee_id', $employeeIds)
            ->whereIn('date', $sourceDates)
            ->orderBy('employee_id')
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        // Map: empId_sourceDate => [shiftId, shiftId2]
        $sourceMap = [];
        foreach ($sourceSchedules as $row) {
            $k = $row->employee_id . '_' . $row->date;
            if (!isset($sourceMap[$k])) {
                $sourceMap[$k] = [];
            }
            $sourceMap[$k][] = $row->shift_id;
        }

        $inserted = 0;
        DB::beginTransaction();
        try {
            foreach ($sourceMap as $key => $shiftIds) {
                [$empId, $srcDate] = explode('_', $key, 2);
                $src = Carbon::parse($srcDate);
                $offsetDays = $sourceStart->diffInDays($src, false);
                $tgtDate = $targetStart->copy()->addDays($offsetDays)->toDateString();

                if (!in_array($tgtDate, $targetDates->all(), true)) {
                    continue;
                }

                if (isset($liburMap[$empId . '_' . $tgtDate])) {
                    continue;
                }

                if (!$overwrite) {
                    if (isset($existingTarget[$empId . '_' . $tgtDate])) {
                        continue;
                    }
                } else {
                    EmployeeSchedule::where('employee_id', $empId)
                        ->where('date', $tgtDate)
                        ->delete();
                }

                $shiftIds = array_values(array_filter((array) $shiftIds));
                foreach ($shiftIds as $shiftId) {
                    EmployeeSchedule::create([
                        'employee_id' => $empId,
                        'date' => $tgtDate,
                        'shift_id' => $shiftId,
                    ]);
                    $inserted++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json([
            'success' => true,
            'message' => 'Jadwal berhasil dicopy dari minggu sebelumnya.',
            'inserted' => $inserted,
            'source_start' => $sourceStart->toDateString(),
            'target_start' => $targetStart->toDateString(),
        ]);
    }

        /**
     * Generate jadwal mingguan ke PDF
     */
    public function print(Request $request)
    {
        $startOfWeek = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfWeek() : Carbon::now()->startOfWeek();
        $dates = collect(range(0, 6))->map(fn($i) => $startOfWeek->copy()->addDays($i)->toDateString());
        $employeesByDivision = $this->groupEmployeesByDivision();
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

    /**
     * Kelompokkan karyawan aktif per divisi (dari posisi utama), lalu urutkan
     * posisi tertinggi di atas: level jabatan dulu, kemudian kedalaman hierarki
     * (posisi tanpa atasan di divisi tsb paling atas), lalu nama posisi & nama karyawan.
     */
    private function groupEmployeesByDivision()
    {
        $employees = Employee::with(['positions.divisions'])
            ->whereRaw('LOWER(status) <> ?', ['tidak aktif'])
            ->orderBy('nama')
            ->get();

        $levelRank = array_flip(Position::LEVEL_OPTIONS); // Staff=0 ... Direktur=5

        // [division_id][position_id] => [parent_position_id, ...]
        $parents = [];
        foreach (PositionDivision::all(['position_id', 'division_id', 'parent_position_id']) as $row) {
            $parents[$row->division_id][$row->position_id][] = $row->parent_position_id;
        }
        $depth = function ($positionId, $divisionId) use ($parents) {
            $d = 0;
            $seen = [];
            $current = $positionId;
            while ($current && !isset($seen[$current])) {
                $seen[$current] = true;
                $parentId = collect($parents[$divisionId][$current] ?? [])->filter()->first();
                if (!$parentId) break;
                $d++;
                $current = $parentId;
            }
            return $d;
        };

        $rows = $employees->map(function ($emp) use ($levelRank, $depth) {
            $position = $emp->positions->first(fn($p) => (int) $p->pivot->is_primary === 1) ?? $emp->positions->first();
            $division = $position ? $position->divisions->first() : null;

            $emp->schedule_position_name = $position?->name;
            $emp->schedule_sort = [
                -($levelRank[$position?->level] ?? -1),
                $position && $division ? $depth($position->id, $division->id) : 99,
                strtolower($position?->name ?? 'zzz'),
                strtolower($emp->nama),
            ];

            return ['division' => $division?->name ?? 'Tanpa Divisi', 'employee' => $emp];
        });

        return $rows->groupBy('division')
            ->map(fn($group) => $group->pluck('employee')
                ->sort(fn($a, $b) => $a->schedule_sort <=> $b->schedule_sort)
                ->values())
            // Divisi diurutkan berdasarkan posisi tertinggi di dalamnya, "Tanpa Divisi" paling bawah
            ->sort(function ($a, $b) {
                return [$a->first()->schedule_sort[0], $a->first()->schedule_sort[1]]
                    <=> [$b->first()->schedule_sort[0], $b->first()->schedule_sort[1]];
            })
            ->sortBy(fn($group, $name) => $name === 'Tanpa Divisi' ? 1 : 0);
    }


}
