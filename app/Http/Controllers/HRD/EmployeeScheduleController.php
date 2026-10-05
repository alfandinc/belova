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
use App\Models\HRD\PengajuanLibur;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeScheduleController extends Controller
{
    // Penanda PengajuanLibur yang dibuat HRD langsung dari grid jadwal (nilai sel "GL")
    public const GANTI_LIBUR_HRD = 'Ganti libur ditetapkan HRD dari jadwal';

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
        $schedules = $this->weekSchedules($dates, true);

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

    /**
     * Jadwal seminggu: key "employeeId_Y-m-d" => daftar jadwal (shift) atau satu entri libur.
     * $editableGantiLibur: ganti libur yang ditetapkan HRD dikembalikan sebagai sel "GL" (untuk grid editor).
     */
    private function weekSchedules($dates, bool $editableGantiLibur = false)
    {
        $schedules = EmployeeSchedule::whereIn('date', $dates)
            ->with('shift')
            ->get()
            ->groupBy(fn($item) => $item->employee_id . '_' . Carbon::parse($item->date)->toDateString());

        // Libur/cuti yang disetujui atasan (dan tidak ditolak HRD) yang beririsan dengan minggu ini
        $libur = PengajuanLibur::where('status_manager', 'disetujui')
            ->where(fn($q) => $q->whereNull('status_hrd')->orWhere('status_hrd', '!=', 'ditolak'))
            ->whereDate('tanggal_mulai', '<=', $dates->last())
            ->whereDate('tanggal_selesai', '>=', $dates->first())
            ->get();
        foreach ($libur as $cuti) {
            $start = Carbon::parse($cuti->tanggal_mulai)->startOfDay();
            $end = Carbon::parse($cuti->tanggal_selesai)->startOfDay();
            $label = match (strtolower($cuti->jenis_libur)) {
                'cuti_tahunan' => 'Cuti',
                'ganti_libur' => 'Ganti Libur',
                default => 'Libur/Cuti',
            };
            foreach ($dates as $date) {
                if (!Carbon::parse($date)->betweenIncluded($start, $end)) {
                    continue;
                }
                $key = $cuti->employee_id . '_' . $date;
                if ($editableGantiLibur && $cuti->alasan === self::GANTI_LIBUR_HRD) {
                    $schedules[$key] = [(object) ['employee_id' => $cuti->employee_id, 'date' => $date, 'shift' => null, 'is_ganti_libur' => true]];
                    continue;
                }
                $schedules[$key] = [(object) [
                    'employee_id' => $cuti->employee_id,
                    'date' => $date,
                    'shift' => null,
                    'is_libur' => true,
                    'label' => $label,
                ]];
            }
        }

        return $schedules;
    }

    /**
     * Data jadwal mingguan (read-only) untuk tampilan di main menu / HP.
     */
    public function viewData(Request $request)
    {
        $startOfWeek = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfWeek() : Carbon::now()->startOfWeek();
        $dates = collect(range(0, 6))->map(fn($i) => $startOfWeek->copy()->addDays($i)->toDateString());
        $schedules = $this->weekSchedules($dates);
        $holidays = LiburNasional::namesByDate($dates->first(), $dates->last());
        $today = now()->toDateString();

        $divisions = [];
        foreach ($this->groupEmployeesByDivision() as $divisionName => $employees) {
            $rows = [];
            foreach ($employees as $employee) {
                $days = [];
                $hasAny = false;
                foreach ($dates as $date) {
                    $items = collect($schedules[$employee->id . '_' . $date] ?? []);
                    $first = $items->first();
                    if ($first && !empty($first->is_libur)) {
                        $days[$date] = ['libur' => $first->label ?? 'Libur'];
                        $hasAny = true;
                        continue;
                    }
                    $shifts = $items->filter(fn($s) => $s->shift)->map(fn($s) => [
                        'name' => $s->shift->name,
                        'start' => substr($s->shift->start_time, 0, 5),
                        'end' => substr($s->shift->end_time, 0, 5),
                        'color' => $s->shift->color ?: '#a3cfbb',
                    ])->values()->all();
                    $days[$date] = $shifts ? ['shifts' => $shifts] : null;
                    $hasAny = $hasAny || (bool) $shifts;
                }
                if (!$hasAny) {
                    continue; // sama seperti print: karyawan tanpa jadwal minggu ini tidak ditampilkan
                }
                $rows[] = [
                    'id' => $employee->id,
                    'nama' => $employee->nama,
                    'posisi' => $employee->schedule_position_name,
                    'days' => $days,
                ];
            }
            if ($rows) {
                $divisions[] = ['name' => $divisionName, 'employees' => $rows];
            }
        }

        return response()->json([
            'start' => $dates->first(),
            'label' => $startOfWeek->locale('id')->isoFormat('D MMM') . ' – ' . $startOfWeek->copy()->addDays(6)->locale('id')->isoFormat('D MMM YYYY'),
            'dates' => $dates->map(fn($d) => [
                'date' => $d,
                'day' => Carbon::parse($d)->locale('id')->isoFormat('ddd'),
                'dayLong' => Carbon::parse($d)->locale('id')->isoFormat('dddd, D MMMM'),
                'num' => Carbon::parse($d)->format('j'),
                'today' => $d === $today,
                'holiday' => $holidays[$d] ?? null,
                'sunday' => Carbon::parse($d)->isSunday(),
            ])->values(),
            'divisions' => $divisions,
            'me' => optional(Auth::user()?->employee)->id,
        ]);
    }

    // Store/update schedule for a week
    public function store(Request $request)
    {
        $data = $request->input('schedule', []);

        // employee_id => ['added' => [..], 'removed' => [..], 'used' => [..], 'refunded' => [..]]
        $gantiLibur = [];
        $liburNasional = LiburNasional::namesByDate(); // loaded once, checked per date below

        // Sel bernilai "GL" = hari ganti libur yang ditetapkan HRD. Diproses setelah jadwal biasa
        // supaya +1 dari kerja hari Minggu di simpanan yang sama sudah masuk saldo.
        $regular = [];
        $gantiLiburDays = [];
        foreach ($data as $employeeId => $days) {
            foreach ((array) $days as $date => $shiftIds) {
                if (in_array('GL', (array) $shiftIds, true)) {
                    $gantiLiburDays[] = [$employeeId, Carbon::parse($date)->toDateString()];
                } else {
                    $regular[$employeeId][$date] = $shiftIds;
                }
            }
        }

        try {
            DB::transaction(function () use ($regular, $gantiLiburDays, &$gantiLibur, $liburNasional) {
                foreach ($gantiLiburDays as [$employeeId, $date]) {
                    if (LiburNasional::isHariGantiLibur($date, $liburNasional)) {
                        $name = Employee::whereKey($employeeId)->value('nama');
                        throw new \DomainException("Ganti libur untuk {$name} tidak bisa di hari Minggu / libur nasional (" . Carbon::parse($date)->locale('id')->isoFormat('D MMM') . ').');
                    }
                }

                foreach ($regular as $employeeId => $days) {
                    foreach ($days as $date => $shiftIds) {
                        $normalizedDate = Carbon::parse($date)->toDateString();

                        // Sebelumnya ganti libur dari HRD -> batalkan & kembalikan jatah
                        if ($this->cancelGantiLiburHrd($employeeId, $normalizedDate)) {
                            $gantiLibur[$employeeId]['refunded'][] = $normalizedDate;
                        }

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
                                $jatah = $this->jatahFor($employeeId);
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

                // Hari ganti libur dari HRD: hapus jadwal hari itu, buat PengajuanLibur disetujui, jatah -1
                foreach ($gantiLiburDays as [$employeeId, $date]) {
                    if ($this->findGantiLiburHrd($employeeId, $date)) {
                        continue; // sudah ganti libur sebelumnya
                    }
                    $jatah = $this->jatahFor($employeeId);
                    if ((int) $jatah->jatah_ganti_libur < 1) {
                        $name = Employee::whereKey($employeeId)->value('nama');
                        throw new \DomainException("Jatah ganti libur {$name} tidak cukup (sisa " . (int) $jatah->jatah_ganti_libur . ').');
                    }

                    EmployeeSchedule::where('employee_id', $employeeId)->where('date', $date)->delete();
                    PengajuanLibur::create([
                        'employee_id' => $employeeId,
                        'jenis_libur' => 'ganti_libur',
                        'tanggal_mulai' => $date,
                        'tanggal_selesai' => $date,
                        'alasan' => self::GANTI_LIBUR_HRD,
                        'status_manager' => 'disetujui',
                        'tanggal_persetujuan_manager' => now(),
                        'status_hrd' => 'disetujui',
                        'notes_hrd' => 'Ditetapkan oleh ' . (Auth::user()->name ?? 'HRD'),
                        'tanggal_persetujuan_hrd' => now(),
                    ]);
                    $jatah->decrement('jatah_ganti_libur');
                    $gantiLibur[$employeeId]['used'][] = $date;
                }
            });
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        if ($request->ajax()) {
            // Ringkasan per karyawan untuk notifikasi
            $summary = [];
            if ($gantiLibur) {
                $names = Employee::whereIn('id', array_keys($gantiLibur))->pluck('nama', 'id');
                $saldo = JatahLibur::whereIn('employee_id', array_keys($gantiLibur))->pluck('jatah_ganti_libur', 'employee_id');
                $fmt = fn($dates) => array_map(fn($d) => Carbon::parse($d)->locale('id')->isoFormat('ddd D MMM'), $dates ?? []);
                foreach ($gantiLibur as $employeeId => $changes) {
                    $summary[] = [
                        'nama' => $names[$employeeId] ?? ('#' . $employeeId),
                        'added' => $fmt($changes['added'] ?? []),
                        'removed' => $fmt($changes['removed'] ?? []),
                        'used' => $fmt($changes['used'] ?? []),
                        'refunded' => $fmt($changes['refunded'] ?? []),
                        'saldo' => (int) ($saldo[$employeeId] ?? 0),
                    ];
                }
            }
            return response()->json(['success' => true, 'ganti_libur' => $summary]);
        }
        return redirect()->route('hrd.schedule.index')->with('success', 'Jadwal berhasil disimpan');
    }

    private function jatahFor($employeeId): JatahLibur
    {
        return JatahLibur::lockForUpdate()->firstOrCreate(
            ['employee_id' => $employeeId],
            ['jatah_cuti_tahunan' => 0, 'jatah_ganti_libur' => 0]
        );
    }

    private function findGantiLiburHrd($employeeId, string $date): ?PengajuanLibur
    {
        return PengajuanLibur::where('employee_id', $employeeId)
            ->where('jenis_libur', 'ganti_libur')
            ->where('alasan', self::GANTI_LIBUR_HRD)
            ->whereDate('tanggal_mulai', $date)
            ->first();
    }

    // Batalkan ganti libur yang ditetapkan HRD di tanggal ini dan kembalikan jatahnya
    private function cancelGantiLiburHrd($employeeId, string $date): bool
    {
        $pengajuan = $this->findGantiLiburHrd($employeeId, $date);
        if (!$pengajuan) {
            return false;
        }
        $pengajuan->delete();
        $this->jatahFor($employeeId)->increment('jatah_ganti_libur');
        return true;
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
        $schedules = $this->weekSchedules($dates);
        $holidays = LiburNasional::namesByDate($dates->first(), $dates->last());

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
        
        $viewData = compact('dates', 'employeesByDivision', 'shifts', 'schedules', 'startOfWeek', 'holidays');

        $pdf = \PDF::loadView('hrd.schedule.print', $viewData)->setPaper('A4', 'landscape');
        return $pdf->stream('jadwal_karyawan_mingguan.pdf');
    }

    /**
     * Kelompokkan karyawan aktif per divisi (dari posisi utama), lalu urutkan
     * berdasarkan hierarki organisasi: posisi paling atas (CEO) di atas, lalu bawahannya.
     * Urutan: kedalaman hierarki -> level jabatan -> nama posisi -> nama karyawan.
     */
    private function groupEmployeesByDivision()
    {
        $employees = Employee::with(['positions.divisions'])
            ->whereRaw('LOWER(status) <> ?', ['tidak aktif'])
            ->orderBy('nama')
            ->get();

        $levelRank = array_flip(Position::LEVEL_OPTIONS); // Staff=0 ... Direktur=5

        // position_id => [parent_position_id, ...] (lintas divisi, hierarki berlaku untuk seluruh organisasi)
        $parents = [];
        foreach (PositionDivision::all(['position_id', 'parent_position_id']) as $row) {
            $parents[$row->position_id][] = $row->parent_position_id;
        }

        // Kedalaman di bagan organisasi: tanpa atasan = 0 (CEO), bawahannya +1, dst.
        // Jika punya beberapa atasan, ambil jalur terpendek ke puncak.
        $memo = [];
        $depth = function ($positionId, array $visiting = []) use (&$depth, &$memo, $parents) {
            if (isset($memo[$positionId])) return $memo[$positionId];
            if (!isset($parents[$positionId])) return 99; // posisi belum dipetakan ke bagan
            $parentIds = array_filter($parents[$positionId]);
            if (!$parentIds) return $memo[$positionId] = 0;
            $visiting[$positionId] = true;
            $best = 99;
            foreach ($parentIds as $parentId) {
                if (isset($visiting[$parentId])) continue; // cegah siklus
                $best = min($best, $depth($parentId, $visiting) + 1);
            }
            return $memo[$positionId] = $best;
        };

        $rows = $employees->map(function ($emp) use ($levelRank, $depth) {
            $position = $emp->positions->first(fn($p) => (int) $p->pivot->is_primary === 1) ?? $emp->positions->first();
            $division = $position ? $position->divisions->first() : null;

            $emp->schedule_position_name = $position?->name;
            $emp->schedule_sort = [
                $position ? $depth($position->id) : 999,
                -($levelRank[$position?->level] ?? -1),
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
            ->sort(fn($a, $b) => array_slice($a->first()->schedule_sort, 0, 2) <=> array_slice($b->first()->schedule_sort, 0, 2))
            ->sortBy(fn($group, $name) => $name === 'Tanpa Divisi' ? 1 : 0);
    }


}
