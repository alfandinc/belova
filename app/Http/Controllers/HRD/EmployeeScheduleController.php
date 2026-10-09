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
use App\Models\HRD\ScheduleLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeScheduleController extends Controller
{
    // Penanda PengajuanLibur yang dibuat HRD langsung dari grid jadwal (nilai sel "GL")
    public const GANTI_LIBUR_HRD = 'Ganti libur ditetapkan HRD dari jadwal';

    // Sama dengan middleware role:Hrd|Admin di route tulis jadwal / shift / jatah
    public static function canEdit(): bool
    {
        return (bool) Auth::user()?->hasAnyRole(['Hrd', 'Admin']);
    }

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

        $date = Carbon::parse($date)->toDateString();
        $touched = [$employeeId . '_' . $date => [$employeeId, $date]];
        $changed = [];
        try {
            $deleted = DB::transaction(function () use ($employeeId, $date, $scheduleId, $touched, &$changed) {
                $before = [$employeeId . '_' . $date => ScheduleLog::stateOf($employeeId, $date)];
                $query = EmployeeSchedule::where('employee_id', $employeeId)->whereDate('date', $date);
                $before = (clone $query)->count();

                // Jika ada schedule_id, hapus hanya jadwal tersebut.
                if ($scheduleId) {
                    $query->where('id', $scheduleId);
                }
                $deleted = $query->delete();

                // Hari Minggu / libur nasional jadi kosong: sama seperti simpan jadwal (tidak boleh jika sudah dipakai ganti libur)
                if ($deleted && $deleted === $before && LiburNasional::isHariGantiLibur($date)
                    && ($error = PengajuanLibur::claimedError($employeeId, $date))) {
                    throw new \DomainException('Jadwal tidak bisa dihapus. ' . $error);
                }

                $changed = $this->logChanges($touched, $before, 'hapus');

                return $deleted;
            });
        } catch (\DomainException $e) {
            return $request->ajax()
                ? response()->json(['success' => false, 'message' => $e->getMessage()], 422)
                : redirect()->back()->with('error', $e->getMessage());
        }
        $this->notifyEmployees($changed);

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
            'jatah' => $this->jatahSummary(collect($employeesByDivision)->flatten(1)->pluck('id')->all()),
            // Editing the schedule, shifts and leave balances (routes with role:Hrd|Admin); others get a read-only grid
            'canEdit' => self::canEdit(),
            'pendingLibur' => $this->pendingLiburMap($dates),
            // Baseline for the conflict check on save (see conflictError)
            'loadedAt' => now()->toDateTimeString(),
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
                    $hariMasuk = collect((array) $cuti->tanggal_masuk_pengganti)->first();
                    $schedules[$key] = [(object) [
                        'employee_id' => $cuti->employee_id, 'date' => $date, 'shift' => null, 'is_ganti_libur' => true,
                        'hari_masuk' => $hariMasuk ? Carbon::parse($hariMasuk)->toDateString() : null,
                    ]];
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
     * Pengajuan libur / cuti yang belum disetujui atasan (dan belum ditolak) di minggu ini: "employeeId_Y-m-d" =>
     * label. Ditandai di grid supaya HRD tidak menjadwalkan orang yang sedang mengajukan libur di hari itu.
     */
    private function pendingLiburMap($dates): array
    {
        $map = [];
        $pending = PengajuanLibur::where(fn($q) => $q->whereNull('status_manager')->orWhere('status_manager', 'menunggu'))
            ->where(fn($q) => $q->whereNull('status_hrd')->orWhere('status_hrd', '!=', 'ditolak'))
            ->whereDate('tanggal_mulai', '<=', $dates->last())
            ->whereDate('tanggal_selesai', '>=', $dates->first())
            ->get();
        foreach ($pending as $p) {
            $label = $p->jenis_libur === 'cuti_tahunan' ? 'Cuti diajukan' : 'Libur diajukan';
            foreach ($dates as $date) {
                if (Carbon::parse($date)->betweenIncluded(Carbon::parse($p->tanggal_mulai)->startOfDay(), Carbon::parse($p->tanggal_selesai)->startOfDay())) {
                    $map[$p->employee_id . '_' . $date] = $label;
                }
            }
        }

        return $map;
    }

    /**
     * Saldo cuti tahunan & ganti libur per karyawan untuk kolom rekap di grid jadwal.
     * Ganti libur dihitung dari jadwal (PengajuanLibur::ringkasanGantiLibur).
     * employee_id => ['cuti' => int, 'cuti_diajukan' => int, 'gl' => ringkasan]
     */
    private function jatahSummary(array $employeeIds): array
    {
        $cuti = JatahLibur::whereIn('employee_id', $employeeIds)->pluck('jatah_cuti_tahunan', 'employee_id');
        // Cuti yang masih menunggu persetujuan (belum dipotong dari saldo)
        $cutiDiajukan = PengajuanLibur::whereIn('employee_id', $employeeIds)
            ->where('jenis_libur', 'cuti_tahunan')
            ->where(fn($q) => $q->whereNull('status_manager')->orWhere('status_manager', '!=', 'ditolak'))
            ->where(fn($q) => $q->whereNull('status_hrd')->orWhere('status_hrd', 'menunggu'))
            ->groupBy('employee_id')
            ->selectRaw('employee_id, SUM(total_hari) as total')
            ->pluck('total', 'employee_id');
        $gl = PengajuanLibur::ringkasanGantiLiburBatch($employeeIds);

        $out = [];
        foreach ($employeeIds as $id) {
            $out[$id] = [
                'cuti' => (int) ($cuti[$id] ?? 0),
                'cuti_diajukan' => (int) ($cutiDiajukan[$id] ?? 0),
                'gl' => $gl[$id],
            ];
        }

        return $out;
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
            // Unread "jadwal Anda diubah" notifications of the viewer (JadwalBerubahNotification)
            'perubahan' => Auth::user()
                ? Auth::user()->unreadNotifications()->where('type', \App\Notifications\JadwalBerubahNotification::class)
                    ->latest()->limit(20)->get()
                    ->map(fn($n) => [
                        'waktu' => $n->created_at->locale('id')->isoFormat('D MMM HH:mm'),
                        'oleh' => $n->data['sender'] ?? 'HRD',
                        'pesan' => $n->data['message'] ?? '',
                    ])->values()
                : [],
        ]);
    }

    /** Mark the viewer's "jadwal Anda diubah" notifications as read (dismissed on the main menu schedule). */
    public function bacaPerubahan()
    {
        Auth::user()?->unreadNotifications()->where('type', \App\Notifications\JadwalBerubahNotification::class)->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    // Store/update schedule for a week
    public function store(Request $request)
    {
        $data = $request->input('schedule', []);

        // employee_id => ['added' => [..], 'removed' => [..], 'used' => [..], 'refunded' => [..]]
        $gantiLibur = [];
        $liburNasional = LiburNasional::namesByDate(); // loaded once, checked per date below

        // Sel bernilai ["GL", "Y-m-d"] = hari ganti libur yang ditetapkan HRD + hari masuk Minggu / libur nasional
        // yang diganti. Diproses setelah jadwal biasa supaya hari Minggu yang diisi di simpanan yang sama sudah ada di jadwal.
        $regular = [];
        $gantiLiburDays = [];
        foreach ($data as $employeeId => $days) {
            foreach ((array) $days as $date => $shiftIds) {
                if (in_array('GL', (array) $shiftIds, true)) {
                    $hariMasuk = collect((array) $shiftIds)->first(fn($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v));
                    $gantiLiburDays[] = [$employeeId, Carbon::parse($date)->toDateString(), $hariMasuk];
                } else {
                    $regular[$employeeId][$date] = $shiftIds;
                }
            }
        }

        // Every day this save touches: checked for conflicts, logged and notified (before -> after)
        $touched = [];
        foreach ($regular as $employeeId => $days) {
            foreach (array_keys($days) as $date) {
                $touched[$employeeId . '_' . Carbon::parse($date)->toDateString()] = [$employeeId, Carbon::parse($date)->toDateString()];
            }
        }
        foreach ($gantiLiburDays as [$employeeId, $date]) {
            $touched[$employeeId . '_' . $date] = [$employeeId, $date];
        }
        $changed = [];

        try {
            DB::transaction(function () use ($regular, $gantiLiburDays, &$gantiLibur, $liburNasional, $touched, &$changed, $request) {
                if ($error = $this->conflictError($touched, $request->input('loaded_at'))) {
                    throw new \DomainException($error);
                }
                $before = [];
                foreach ($touched as $key => [$employeeId, $date]) {
                    $before[$key] = ScheduleLog::stateOf($employeeId, $date);
                }

                foreach ($gantiLiburDays as [$employeeId, $date]) {
                    if (LiburNasional::isHariGantiLibur($date, $liburNasional)) {
                        $name = Employee::whereKey($employeeId)->value('nama');
                        throw new \DomainException("Ganti libur untuk {$name} tidak bisa di hari Minggu / libur nasional (" . Carbon::parse($date)->locale('id')->isoFormat('D MMM') . ').');
                    }
                }

                // Sebelumnya ganti libur dari HRD -> batalkan (hari masuknya bebas lagi). Dilakukan lebih dulu supaya
                // hari Minggu yang dipakainya sudah bebas saat dicek di bawah (simpanan yang sama).
                foreach ($regular as $employeeId => $days) {
                    foreach (array_keys($days) as $date) {
                        $normalizedDate = Carbon::parse($date)->toDateString();
                        if ($this->cancelGantiLiburHrd($employeeId, $normalizedDate)) {
                            $gantiLibur[$employeeId]['refunded'][] = $normalizedDate;
                        }
                    }
                }

                foreach ($regular as $employeeId => $days) {
                    foreach ($days as $date => $shiftIds) {
                        $normalizedDate = Carbon::parse($date)->toDateString();

                        $wasEmpty = !EmployeeSchedule::where('employee_id', $employeeId)->where('date', $normalizedDate)->exists();

                        // Hari Minggu / libur nasional yang sudah dipakai ganti libur tidak boleh dikosongkan
                        if (!$wasEmpty && !array_filter((array) $shiftIds) && LiburNasional::isHariGantiLibur($normalizedDate, $liburNasional)
                            && ($error = PengajuanLibur::claimedError($employeeId, $normalizedDate))) {
                            throw new \DomainException('Jadwal tidak bisa dikosongkan. ' . $error);
                        }

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

                        // Hari Minggu / libur nasional: catat untuk notifikasi (saldo ganti libur dihitung dari jadwal)
                        if (LiburNasional::isHariGantiLibur($normalizedDate, $liburNasional)) {
                            $isEmpty = count($shiftIds) === 0;
                            if ($wasEmpty !== $isEmpty) {
                                $gantiLibur[$employeeId][$wasEmpty ? 'added' : 'removed'][] = $normalizedDate;
                            }
                        }
                    }
                }

                // Hari ganti libur dari HRD: hapus jadwal hari itu, buat PengajuanLibur disetujui yang memakai hari masuknya
                foreach ($gantiLiburDays as [$employeeId, $date, $hariMasuk]) {
                    $this->jatahFor($employeeId); // lock per employee, so the hari masuk checks below can't race
                    $existing = $this->findGantiLiburHrd($employeeId, $date);
                    $currentHariMasuk = $existing ? collect((array) $existing->tanggal_masuk_pengganti)->first() : null;
                    $currentHariMasuk = $currentHariMasuk ? Carbon::parse($currentHariMasuk)->toDateString() : null;
                    if ($existing && (!$hariMasuk || $hariMasuk === $currentHariMasuk)) {
                        continue; // sudah ganti libur sebelumnya, hari masuk tidak berubah
                    }

                    $label = Carbon::parse($date)->locale('id')->isoFormat('D MMM');
                    $name = Employee::whereKey($employeeId)->value('nama');
                    if (!$hariMasuk) {
                        throw new \DomainException("Pilih hari masuk Minggu / libur nasional yang diganti untuk ganti libur {$name} ({$label}).");
                    }
                    if ($error = $this->hariMasukError($employeeId, $hariMasuk, $date, $liburNasional, $existing?->id)) {
                        throw new \DomainException("Ganti libur {$name} ({$label}): {$error}");
                    }

                    if ($existing) {
                        $existing->update(['tanggal_masuk_pengganti' => [$hariMasuk]]);
                        continue;
                    }

                    EmployeeSchedule::where('employee_id', $employeeId)->where('date', $date)->delete();
                    PengajuanLibur::create([
                        'employee_id' => $employeeId,
                        'jenis_libur' => 'ganti_libur',
                        'tanggal_masuk_pengganti' => [$hariMasuk], // hari Minggu / libur nasional yang diganti
                        'tanggal_mulai' => $date,
                        'tanggal_selesai' => $date,
                        'alasan' => self::GANTI_LIBUR_HRD,
                        'status_manager' => 'disetujui',
                        'tanggal_persetujuan_manager' => now(),
                        'status_hrd' => 'disetujui',
                        'notes_hrd' => 'Ditetapkan oleh ' . (Auth::user()->name ?? 'HRD'),
                        'tanggal_persetujuan_hrd' => now(),
                    ]);
                    $gantiLibur[$employeeId]['used'][] = $date;
                }

                $changed = $this->logChanges($touched, $before, 'jadwal');
            });
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
        $this->notifyEmployees($changed);

        if ($request->ajax()) {
            // Ringkasan per karyawan untuk notifikasi
            $summary = [];
            if ($gantiLibur) {
                $names = Employee::whereIn('id', array_keys($gantiLibur))->pluck('nama', 'id');
                $liburNasional = LiburNasional::namesByDate();
                $fmt = fn($dates) => array_map(fn($d) => Carbon::parse($d)->locale('id')->isoFormat('ddd D MMM'), $dates ?? []);
                foreach ($gantiLibur as $employeeId => $changes) {
                    $summary[] = [
                        'nama' => $names[$employeeId] ?? ('#' . $employeeId),
                        'added' => $fmt($changes['added'] ?? []),
                        'removed' => $fmt($changes['removed'] ?? []),
                        'used' => $fmt($changes['used'] ?? []),
                        'refunded' => $fmt($changes['refunded'] ?? []),
                        'saldo' => PengajuanLibur::saldoGantiLibur($employeeId, $liburNasional),
                    ];
                }
            }
            // New baseline for the next conflict check of this page
            return response()->json(['success' => true, 'ganti_libur' => $summary, 'loaded_at' => now()->toDateTimeString()]);
        }
        return redirect()->route('hrd.schedule.index')->with('success', 'Jadwal berhasil disimpan');
    }

    /**
     * Rekap jatah ganti libur: tiap hari masuk Minggu / libur nasional (EmployeeSchedule, dasar +1 jatah)
     * dipasangkan dengan tanggal libur penggantinya (PengajuanLibur.tanggal_masuk_pengganti).
     */
    public function rekapHariLibur(Request $request)
    {
        $from = Carbon::parse($request->input('from', now()->startOfMonth()))->toDateString();
        $to = Carbon::parse($request->input('to', now()->endOfMonth()))->toDateString();
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $holidays = LiburNasional::namesByDate($from, $to);

        $rows = EmployeeSchedule::with(['shift', 'employee:id,nama'])
            ->whereBetween('date', [max($from, LiburNasional::gantiLiburMulai()), $to])
            ->where(fn($q) => $q->whereRaw('DAYOFWEEK(date) = 1')->orWhereIn('date', array_keys($holidays)))
            ->orderBy('date')
            ->get()
            ->groupBy(fn($s) => $s->employee_id . '_' . Carbon::parse($s->date)->toDateString());

        // Ganti libur yang tanggal liburnya di rentang ini, atau yang mengklaim hari masuk di rentang ini
        $workedEmpIds = $rows->map(fn($items) => $items->first()->employee_id)->unique()->values()->all();
        $gantiLibur = PengajuanLibur::gantiLiburAktif()
            ->with('employee:id,nama')
            ->where(fn($q) => $q->whereIn('employee_id', $workedEmpIds)
                ->orWhere(fn($q) => $q->whereDate('tanggal_mulai', '<=', $to)->whereDate('tanggal_selesai', '>=', $from)))
            ->orderBy('tanggal_mulai')
            ->get();

        // "employeeId_Y-m-d" (hari masuk) => pengajuan ganti libur yang memakainya
        $claimedBy = [];
        foreach ($gantiLibur as $p) {
            foreach ((array) $p->tanggal_masuk_pengganti as $d) {
                $claimedBy[$p->employee_id . '_' . Carbon::parse($d)->toDateString()] = $p;
            }
        }

        $fmt = fn($d) => Carbon::parse($d)->locale('id')->isoFormat('ddd, D MMM YYYY');
        $liburInfo = function (PengajuanLibur $p) use ($fmt) {
            $menunggu = $p->status_manager !== 'disetujui' || ($p->status_hrd && $p->status_hrd !== 'disetujui');
            return [
                'label' => $fmt($p->tanggal_mulai) . ($p->total_hari > 1 ? ' – ' . $fmt($p->tanggal_selesai) : ''),
                'status' => $menunggu ? 'Menunggu persetujuan' : 'Disetujui',
                'sumber' => $p->alasan === self::GANTI_LIBUR_HRD ? 'Jadwal HRD' : 'Pengajuan',
            ];
        };

        $employees = [];
        $addPair = function ($empId, $nama, $sortDate, array $pair) use (&$employees) {
            $employees[$empId] ??= ['id' => $empId, 'nama' => $nama ?? ('#' . $empId), 'pairs' => []];
            $employees[$empId]['pairs'][] = ['sort' => $sortDate] + $pair;
        };

        // 1) Setiap hari masuk Minggu / libur nasional -> libur penggantinya (atau belum dipakai)
        foreach ($rows as $items) {
            $first = $items->first();
            $key = Carbon::parse($first->date)->toDateString();
            $p = $claimedBy[$first->employee_id . '_' . $key] ?? null;
            $addPair($first->employee_id, $first->employee->nama ?? null, $key, [
                'masuk' => [
                    'label' => $fmt($key),
                    'keterangan' => $holidays[$key] ?? 'Minggu',
                    'is_holiday' => isset($holidays[$key]),
                    'shifts' => $items->filter(fn($s) => $s->shift)
                        ->map(fn($s) => $s->shift->name . ' (' . substr($s->shift->start_time, 0, 5) . '–' . substr($s->shift->end_time, 0, 5) . ')')
                        ->values()->all(),
                ],
                'libur' => $p ? $liburInfo($p) : null,
            ]);
        }

        // 2) Libur ganti libur di rentang ini yang tidak menyebut hari masuk (dari saldo lama / manual)
        foreach ($gantiLibur as $p) {
            if (!empty($p->tanggal_masuk_pengganti)) {
                continue;
            }
            $mulai = Carbon::parse($p->tanggal_mulai)->toDateString();
            $selesai = Carbon::parse($p->tanggal_selesai)->toDateString();
            if ($mulai > $to || $selesai < $from) {
                continue;
            }
            $addPair($p->employee_id, $p->employee->nama ?? null, $mulai, ['masuk' => null, 'libur' => $liburInfo($p)]);
        }

        $saldoGl = PengajuanLibur::ringkasanGantiLiburBatch(array_keys($employees));
        $employees = collect($employees)
            ->map(function ($e) use ($saldoGl) {
                usort($e['pairs'], fn($a, $b) => strcmp($a['sort'], $b['sort']));
                $masuk = collect($e['pairs'])->filter(fn($x) => $x['masuk']);
                return $e + [
                    'total_masuk' => $masuk->count(),
                    'belum_dipakai' => $masuk->filter(fn($x) => !$x['libur'])->count(),
                    'saldo' => $saldoGl[$e['id']]['saldo'],
                ];
            })
            ->sortBy(fn($e) => strtolower($e['nama']))
            ->values();

        return response()->json([
            'from' => $from,
            'to' => $to,
            'employees' => $employees,
        ]);
    }

    /**
     * Riwayat untuk popup saat angka Cuti / Ganti Libur di grid diklik. jenis=cuti: semua pengajuan cuti
     * tahunan (terbaru dulu). jenis=ganti_libur: tiap hari masuk Minggu / libur nasional + libur penggantinya
     * (PengajuanLibur::rincianGantiLibur).
     */
    public function riwayatJatah(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer|exists:hrd_employee,id',
            'jenis' => 'required|in:cuti,ganti_libur',
        ]);
        $employeeId = (int) $request->query('employee_id');
        // Leave history is personal: HRD / management, or the employee themself
        if (!self::canEdit() && !Auth::user()?->hasAnyRole(['Manager', 'Head Manager', 'Ceo'])
            && optional(Auth::user()?->employee)->id !== $employeeId) {
            return response()->json(['message' => 'Anda tidak berhak melihat riwayat karyawan ini.'], 403);
        }
        $nama = Employee::whereKey($employeeId)->value('nama');

        if ($request->query('jenis') === 'ganti_libur') {
            $rincian = PengajuanLibur::rincianGantiLibur($employeeId, LiburNasional::namesByDate());
            return response()->json([
                'nama' => $nama,
                'saldo' => $rincian['ganti_libur_ringkasan']['saldo'],
                'mulai' => Carbon::parse(LiburNasional::gantiLiburMulai())->locale('id')->translatedFormat('j F Y'),
            ] + $rincian);
        }

        $fmt = fn($d) => Carbon::parse($d)->locale('id')->translatedFormat('D, j M Y');

        return response()->json([
            'nama' => $nama,
            'saldo' => (int) JatahLibur::where('employee_id', $employeeId)->value('jatah_cuti_tahunan'),
            'cuti' => PengajuanLibur::where('employee_id', $employeeId)
                ->where('jenis_libur', 'cuti_tahunan')
                ->orderByDesc('tanggal_mulai')
                ->get()
                ->map(fn($p) => [
                    'tanggal' => $fmt($p->tanggal_mulai) . ($p->total_hari > 1 ? ' – ' . $fmt($p->tanggal_selesai) : ''),
                    'jumlah_hari' => (int) $p->total_hari,
                    'alasan' => $p->alasan,
                    'status' => $p->status_manager === 'ditolak' || $p->status_hrd === 'ditolak' ? 'ditolak'
                        : ($p->status_hrd === 'disetujui' ? 'disetujui' : 'menunggu'),
                ])
                ->values(),
        ]);
    }

    /**
     * Someone else saved one of these days after this page was loaded ($loadedAt, from the page / last save):
     * saving now would silently overwrite their change. Uses the audit log as the change history.
     */
    private function conflictError(array $touched, ?string $loadedAt): ?string
    {
        if (!$touched || !$loadedAt) {
            return null;
        }
        $keys = array_keys($touched);
        $hit = ScheduleLog::with(['employee:id,nama', 'user:id,name'])
            ->where('created_at', '>', Carbon::parse($loadedAt))
            ->whereIn('employee_id', array_unique(array_column($touched, 0)))
            ->whereIn('date', array_unique(array_column($touched, 1)))
            ->latest('id')
            ->get()
            ->first(fn($log) => in_array($log->employee_id . '_' . $log->date->toDateString(), $keys, true));
        if (!$hit) {
            return null;
        }

        return 'Jadwal ' . ($hit->employee->nama ?? '-') . ' tanggal ' . $hit->date->locale('id')->isoFormat('D MMM')
            . ' sudah diubah oleh ' . ($hit->user->name ?? 'pengguna lain') . ' pukul ' . $hit->created_at->format('H:i')
            . ' setelah halaman ini dibuka. Muat ulang minggu ini (Minggu ini / ganti minggu) lalu ulangi perubahan Anda.';
    }

    /**
     * Log every touched day whose state changed ($before from ScheduleLog::stateOf). Returns
     * [employee_id => [[date, sebelum, sesudah], ...]] for notifyEmployees. Must run inside the transaction.
     */
    private function logChanges(array $touched, array $before, string $aksi): array
    {
        $changed = [];
        foreach ($touched as $key => [$employeeId, $date]) {
            $after = ScheduleLog::stateOf($employeeId, $date);
            if ($after === ($before[$key] ?? null)) {
                continue;
            }
            $isGl = str_starts_with((string) $after, 'Ganti libur') || str_starts_with((string) ($before[$key] ?? ''), 'Ganti libur');
            ScheduleLog::record($employeeId, $date, $before[$key] ?? null, $after, $isGl && $aksi === 'jadwal' ? 'ganti_libur' : $aksi);
            $changed[$employeeId][] = [$date, $before[$key] ?? null, $after];
        }

        return $changed;
    }

    /** Tell each employee (their user account) which of their days changed; not the person who made the change. */
    private function notifyEmployees(array $changed): void
    {
        if (!$changed) {
            return;
        }
        $employees = Employee::with('user')->whereIn('id', array_keys($changed))->get()->keyBy('id');
        foreach ($changed as $employeeId => $days) {
            $user = $employees[$employeeId]->user ?? null;
            if ($user && $user->id !== Auth::id()) {
                try {
                    $user->notify(new \App\Notifications\JadwalBerubahNotification($days));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Notifikasi jadwal gagal: ' . $e->getMessage());
                }
            }
        }
    }

    /** Audit log of the opened week (newest first), for the "Riwayat perubahan" popup. */
    public function scheduleLogs(Request $request)
    {
        $start = Carbon::parse($request->query('start_date', now()))->startOfWeek();
        $fmt = fn($d) => Carbon::parse($d)->locale('id')->isoFormat('ddd, D MMM');

        return response()->json(ScheduleLog::with(['employee:id,nama', 'user:id,name'])
            ->whereBetween('date', [$start->toDateString(), $start->copy()->addDays(6)->toDateString()])
            ->latest('id')
            ->limit(300)
            ->get()
            ->map(fn($log) => [
                'waktu' => $log->created_at->locale('id')->isoFormat('D MMM HH:mm'),
                'oleh' => $log->user->name ?? '-',
                'karyawan' => $log->employee->nama ?? ('#' . $log->employee_id),
                'tanggal' => $fmt($log->date),
                'aksi' => ScheduleLog::AKSI_LABEL[$log->aksi] ?? $log->aksi,
                'sebelum' => $log->sebelum,
                'sesudah' => $log->sesudah,
            ]));
    }

    private function jatahFor($employeeId): JatahLibur
    {
        return JatahLibur::lockForUpdate()->firstOrCreate(
            ['employee_id' => $employeeId],
            ['jatah_cuti_tahunan' => 0, 'jatah_ganti_libur' => 0]
        );
    }

    // Hari masuk yang belum dipakai dan sudah dikerjakan sebelum $tanggalLibur (masuk dulu baru libur)
    private function hariMasukUntukLibur($employeeId, string $tanggalLibur, array $holidays, $excludeId = null): array
    {
        return array_values(array_filter(
            PengajuanLibur::hariMasukBelumDipakai($employeeId, $holidays, $excludeId),
            fn($d) => PengajuanLibur::hariMasukSebelumLibur($d, $tanggalLibur)
        ));
    }

    /**
     * Hari masuk Minggu / libur nasional yang bisa dipilih saat menetapkan ganti libur di jadwal pada tanggal
     * `libur`. exclude_libur = tanggal ganti libur yang sedang diubah (klaimnya tetap bisa dipilih).
     */
    public function hariMasukTersedia(Request $request)
    {
        $employeeId = (int) $request->query('employee_id');
        $holidays = LiburNasional::namesByDate();
        $exclude = $request->filled('exclude_libur')
            ? $this->findGantiLiburHrd($employeeId, Carbon::parse($request->query('exclude_libur'))->toDateString())
            : null;
        $tersedia = $this->hariMasukUntukLibur($employeeId, $request->query('libur', Carbon::today()->addDay()->toDateString()), $holidays, $exclude?->id);

        $dates = EmployeeSchedule::with('shift:id,name')
            ->where('employee_id', $employeeId)
            ->whereIn('date', $tersedia)
            ->orderBy('date')
            ->get()
            ->groupBy(fn($s) => Carbon::parse($s->date)->toDateString())
            ->map(fn($rows, $date) => [
                'date' => $date,
                'label' => Carbon::parse($date)->locale('id')->translatedFormat('l, j F Y'),
                'keterangan' => $holidays[$date] ?? 'Hari Minggu',
                'shift' => $rows->map(fn($s) => $s->shift->name ?? null)->filter()->unique()->implode(', '),
            ])
            ->values();

        return response()->json([
            'saldo' => PengajuanLibur::saldoGantiLibur($employeeId, $holidays),
            'dates' => $dates,
        ]);
    }

    /** Why $hariMasuk cannot be used as the worked day for a ganti libur on $tanggalLibur, or null when it can. */
    private function hariMasukError($employeeId, string $hariMasuk, string $tanggalLibur, array $holidays, $excludeId = null): ?string
    {
        $label = Carbon::parse($hariMasuk)->locale('id')->isoFormat('dddd, D MMM YYYY');
        if ($hariMasuk < LiburNasional::gantiLiburMulai()) {
            return "{$label} sebelum " . Carbon::parse(LiburNasional::gantiLiburMulai())->locale('id')->isoFormat('D MMM YYYY') . ' (data lama tidak dihitung).';
        }
        if (!LiburNasional::isHariGantiLibur($hariMasuk, $holidays)) {
            return "{$label} bukan hari Minggu / libur nasional.";
        }
        if (!PengajuanLibur::hariMasukSebelumLibur($hariMasuk, $tanggalLibur)) {
            return "{$label} belum dikerjakan / tidak sebelum tanggal libur (masuk dulu baru libur).";
        }
        if (!EmployeeSchedule::where('employee_id', $employeeId)->whereDate('date', $hariMasuk)->exists()) {
            return "{$label} tidak ada jadwal masuk.";
        }
        if (in_array($hariMasuk, PengajuanLibur::claimedHariMasuk($employeeId, $excludeId), true)) {
            return "{$label} sudah dipakai untuk ganti libur lain.";
        }

        return null;
    }

    private function findGantiLiburHrd($employeeId, string $date): ?PengajuanLibur
    {
        return PengajuanLibur::where('employee_id', $employeeId)
            ->where('jenis_libur', 'ganti_libur')
            ->where('alasan', self::GANTI_LIBUR_HRD)
            ->whereDate('tanggal_mulai', $date)
            ->first();
    }

    // Batalkan ganti libur yang ditetapkan HRD di tanggal ini; hari masuknya otomatis bisa dipakai lagi
    private function cancelGantiLiburHrd($employeeId, string $date): bool
    {
        $pengajuan = $this->findGantiLiburHrd($employeeId, $date);
        if (!$pengajuan) {
            return false;
        }
        $pengajuan->delete();
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

        $employeeIds = Employee::whereRaw('LOWER(status) <> ?', ['tidak aktif'])
            ->whereDoesntHave('user.roles', fn($q) => $q->whereRaw('LOWER(name) = ?', ['ceo'])) // CEO tidak dijadwalkan
            ->pluck('id');

        // Build a set of employee_id_date that should be treated as Libur/Cuti on target week
        $liburMap = [];
        // Sama dengan tampilan grid (weekSchedules): disetujui atasan, tidak ditolak HRD, beririsan dengan minggu target
        $libur = PengajuanLibur::where('status_manager', 'disetujui')
            ->where(fn($q) => $q->whereNull('status_hrd')->orWhere('status_hrd', '!=', 'ditolak'))
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('tanggal_mulai', '<=', $targetDates->last())
            ->whereDate('tanggal_selesai', '>=', $targetDates->first())
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
        $gantiLiburAdded = 0;
        $copyTouched = $copyBefore = [];
        $holidays = LiburNasional::namesByDate($targetDates->first(), $targetDates->last());
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
                }
                $wasEmpty = !EmployeeSchedule::where('employee_id', $empId)->where('date', $tgtDate)->exists();
                $copyTouched[$empId . '_' . $tgtDate] = [$empId, $tgtDate];
                $copyBefore[$empId . '_' . $tgtDate] = ScheduleLog::stateOf($empId, $tgtDate);
                if ($overwrite) {
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

                // Hari Minggu / libur nasional yang sebelumnya kosong: hari masuk baru untuk ganti libur (info saja)
                if ($wasEmpty && $shiftIds && LiburNasional::isHariGantiLibur($tgtDate, $holidays)) {
                    $gantiLiburAdded++;
                }
            }

            $changed = $this->logChanges($copyTouched, $copyBefore, 'copy_minggu');
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
        $this->notifyEmployees($changed);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal berhasil dicopy dari minggu sebelumnya.',
            'inserted' => $inserted,
            'ganti_libur_added' => $gantiLiburAdded,
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
     * Kelompokkan karyawan aktif untuk jadwal berdasarkan role: Head Manager, Manager on Duty, lalu per divisi.
     * Di dalam tiap kelompok diurutkan berdasarkan hierarki organisasi (atasan di atas).
     * Urutan: kedalaman hierarki -> level jabatan -> nama posisi -> nama karyawan.
     */
    private function groupEmployeesByDivision()
    {
        $employees = Employee::with(['positions.divisions', 'user.roles'])
            ->whereRaw('LOWER(status) <> ?', ['tidak aktif'])
            // Role CEO tidak dijadwalkan
            ->whereDoesntHave('user.roles', fn($q) => $q->whereRaw('LOWER(name) = ?', ['ceo']))
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
            $d = $position ? $depth($position->id) : 999;

            // Kelompok jadwal berdasarkan role user:
            //  - role Head Manager / Hrd / Manager -> "Manager on Duty"
            //  - sisanya dipisah per divisi (dari posisi utama)
            $roles = $emp->user?->roles->pluck('name')->map(fn($r) => strtolower($r))->all() ?? [];
            // Urutan di dalam Manager on Duty: Head Manager -> Hrd -> Manager lainnya
            $roleRank = in_array('head manager', $roles, true) ? 0 : (in_array('hrd', $roles, true) ? 1 : 2);
            if (array_intersect(['head manager', 'hrd', 'manager'], $roles)) {
                $group = 'Manager on Duty';
                $groupOrder = 0;
            } else {
                $group = $division?->name ?? 'Tanpa Divisi';
                $groupOrder = $division ? 1 : 2;
                $roleRank = 0; // di grup divisi hanya hierarki yang menentukan
            }

            $emp->schedule_position_name = $position?->name;
            $emp->schedule_group_order = $groupOrder;
            $emp->schedule_sort = [
                $roleRank,
                $d,
                -($levelRank[$position?->level] ?? -1),
                strtolower($position?->name ?? 'zzz'),
                strtolower($emp->nama),
            ];

            return ['group' => $group, 'employee' => $emp];
        });

        return $rows->groupBy('group')
            ->map(fn($items) => $items->pluck('employee')
                ->sort(fn($a, $b) => $a->schedule_sort <=> $b->schedule_sort)
                ->values())
            // Manager on Duty -> divisi (urut posisi tertinggi di dalamnya, lalu nama) -> Tanpa Divisi
            ->sortBy(fn($items, $name) => [$items->first()->schedule_group_order, $items->first()->schedule_sort[1], strtolower($name)]);
    }


}
