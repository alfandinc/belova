<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\JatahLibur;
use App\Models\HRD\Employee;
use App\Models\HRD\EmployeeSchedule;
use App\Models\HRD\LiburNasional;
use App\Models\HRD\PengajuanLibur;
use App\Models\HRD\ScheduleLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\HrdConfig;

/**
 * HRD actions on leave balances, used from the schedule page (hrd.schedule.index): daily leave capacity,
 * annual leave reset, editing an employee's jatah cuti tahunan and re-pairing ganti libur with worked days.
 * Saldo and history are shown there (EmployeeScheduleController::riwayatJatah); ganti libur is counted from
 * the schedule (PengajuanLibur::ringkasanGantiLibur).
 */
class JatahLiburController extends Controller
{
    public function getLeaveCapacity()
    {
        return response()->json([
            'success' => true,
            'capacity' => HrdConfig::getLeaveDailyCapacity(),
        ]);
    }

    public function updateLeaveCapacity(Request $request)
    {
        $request->validate([
            'capacity' => 'required|integer|min:1|max:100',
        ]);
        HrdConfig::setLeaveDailyCapacity((int)$request->input('capacity'));
        return response()->json([
            'success' => true,
            'message' => 'Kuota libur harian berhasil diperbarui',
            'capacity' => HrdConfig::getLeaveDailyCapacity(),
        ]);
    }

    /**
     * Ganti libur recorded without a worked day (older data) still leave every worked Sunday / holiday counted
     * as "bisa dipakai". Pair each of them, oldest libur first, with the oldest free worked days before its libur
     * (masuk dulu baru libur), so the days it used drop out of the balance. employee_id = one employee, or empty
     * for everyone. Requests without enough free days are left as they are and reported.
     */
    public function pasangkanOtomatis(Request $request)
    {
        $request->validate(['employee_id' => 'nullable|integer|exists:hrd_employee,id']);
        $employeeId = $request->input('employee_id');
        $mulai = LiburNasional::gantiLiburMulai();
        $holidays = LiburNasional::namesByDate();
        $fmt = fn($d) => Carbon::parse($d)->locale('id')->isoFormat('D MMM YYYY');

        $result = DB::transaction(function () use ($employeeId, $mulai, $holidays, $fmt) {
            $requests = PengajuanLibur::gantiLiburAktif($employeeId)
                ->whereDate('tanggal_mulai', '>=', $mulai)
                ->orderBy('tanggal_mulai')
                ->lockForUpdate()
                ->get()
                ->filter(fn($p) => empty($p->tanggal_masuk_pengganti));

            $paired = 0;
            $gagal = [];
            foreach ($requests as $p) {
                $free = array_values(array_filter(
                    PengajuanLibur::hariMasukBelumDipakai($p->employee_id, $holidays),
                    fn($d) => PengajuanLibur::hariMasukSebelumLibur($d, $p->tanggal_mulai)
                ));
                $need = (int) $p->total_hari;
                if (count($free) < $need) {
                    $gagal[] = (Employee::whereKey($p->employee_id)->value('nama') ?? '#' . $p->employee_id)
                        . ' (libur ' . $fmt($p->tanggal_mulai) . ')';
                    continue;
                }
                $days = array_slice($free, 0, $need);
                $p->update(['tanggal_masuk_pengganti' => $days]);
                foreach ($days as $d) {
                    ScheduleLog::record($p->employee_id, $d, null, 'Dipakai ganti libur ' . $fmt($p->tanggal_mulai), 'ganti_libur');
                }
                $paired++;
            }

            return ['paired' => $paired, 'gagal' => $gagal];
        });

        return response()->json(['success' => true] + $result);
    }

    /**
     * Record a past weekday that HRD once emptied in the schedule as the ganti libur it was, paired with the
     * worked Sunday / holiday it used ($id = employee id). Same record as marking the cell G in the grid.
     */
    public function jadikanGantiLibur(Request $request, $id)
    {
        $request->validate([
            'libur' => 'required|date_format:Y-m-d|before:today',
            'hari_masuk' => 'required|date_format:Y-m-d',
        ]);
        Employee::findOrFail($id);
        $libur = $request->input('libur');
        $hariMasuk = $request->input('hari_masuk');
        $fmt = fn($d) => Carbon::parse($d)->locale('id')->translatedFormat('l, j F Y');
        $fail = fn(string $msg) => throw ValidationException::withMessages(['libur' => $msg]);

        DB::transaction(function () use ($id, $libur, $hariMasuk, $fmt, $fail) {
            JatahLibur::lockForUpdate()->firstOrCreate(['employee_id' => $id], ['jatah_cuti_tahunan' => 0, 'jatah_ganti_libur' => 0]);
            $holidays = LiburNasional::namesByDate();

            if (LiburNasional::isHariGantiLibur($libur, $holidays) || Carbon::parse($libur)->isSunday()) {
                $fail($fmt($libur) . ' adalah hari Minggu / libur nasional.');
            }
            if (EmployeeSchedule::where('employee_id', $id)->whereDate('date', $libur)->exists()) {
                $fail($fmt($libur) . ' masih ada jadwal masuk.');
            }
            $adaLibur = PengajuanLibur::where('employee_id', $id)
                ->where(fn($q) => $q->whereNull('status_hrd')->orWhere('status_hrd', '!=', 'ditolak'))
                ->where(fn($q) => $q->whereNull('status_manager')->orWhere('status_manager', '!=', 'ditolak'))
                ->whereDate('tanggal_mulai', '<=', $libur)->whereDate('tanggal_selesai', '>=', $libur)
                ->exists();
            if ($adaLibur) {
                $fail($fmt($libur) . ' sudah tercatat sebagai libur / cuti.');
            }
            if (!PengajuanLibur::hariMasukSebelumLibur($hariMasuk, $libur)
                || !in_array($hariMasuk, PengajuanLibur::hariMasukBelumDipakai($id, $holidays), true)) {
                $fail($fmt($hariMasuk) . ' tidak bisa dipakai: harus hari masuk Minggu / libur nasional yang belum dipakai dan sebelum ' . $fmt($libur) . '.');
            }

            PengajuanLibur::create([
                'employee_id' => $id,
                'jenis_libur' => 'ganti_libur',
                'tanggal_masuk_pengganti' => [$hariMasuk],
                'tanggal_mulai' => $libur,
                'tanggal_selesai' => $libur,
                'alasan' => EmployeeScheduleController::GANTI_LIBUR_HRD,
                'status_manager' => 'disetujui',
                'tanggal_persetujuan_manager' => now(),
                'status_hrd' => 'disetujui',
                'notes_hrd' => 'Dicatat ulang dari jadwal lama oleh ' . (auth()->user()->name ?? 'HRD'),
                'tanggal_persetujuan_hrd' => now(),
            ]);
            ScheduleLog::record($id, $libur, null, ScheduleLog::stateOf($id, $libur), 'ganti_libur');
        });

        return response()->json(['success' => true]);
    }

    /** Set the remaining jatah cuti tahunan of an employee ($id = employee id). */
    public function updateCuti(Request $request, $id)
    {
        $request->validate(['jatah_cuti_tahunan' => 'required|integer|min:0|max:365']);
        Employee::findOrFail($id);

        $jatah = JatahLibur::firstOrCreate(['employee_id' => $id], ['jatah_cuti_tahunan' => 0, 'jatah_ganti_libur' => 0]);
        $jatah->update(['jatah_cuti_tahunan' => (int) $request->input('jatah_cuti_tahunan')]);

        return response()->json([
            'success' => true,
            'message' => 'Jatah cuti tahunan diperbarui',
            'saldo' => (int) $jatah->jatah_cuti_tahunan,
        ]);
    }

    /**
     * Re-pair a ganti libur with the Sundays / holidays it replaces: swap one of its worked days ('lama' ->
     * 'baru'), or name all of them for a ganti libur that has none. Any worked day not claimed by another request
     * can be chosen; the balance follows by itself, since it is counted from the unclaimed days.
     */
    public function updateHariMasuk(Request $request, $id) // $id = employee id
    {
        $request->validate([
            'pengajuan_id' => 'required|integer',
            'lama' => 'nullable|date',
            'baru' => 'required|array|min:1',
            'baru.*' => 'required|date|distinct',
        ], [
            'baru.*.required' => 'Pilih hari masuk.',
            'baru.*.distinct' => 'Hari masuk tidak boleh sama.',
        ]);

        $fail = fn(string $msg) => throw ValidationException::withMessages(['hari_masuk' => $msg]);

        $jatah = DB::transaction(function () use ($request, $id, $fail) {
            // Locking the balance row serializes every pairing change of this employee
            Employee::findOrFail($id);
            $jatah = JatahLibur::lockForUpdate()->firstOrCreate(['employee_id' => $id], ['jatah_cuti_tahunan' => 0, 'jatah_ganti_libur' => 0]);
            $pengajuan = PengajuanLibur::gantiLiburAktif($jatah->employee_id)->lockForUpdate()->find($request->pengajuan_id)
                ?? $fail('Ganti libur tidak ditemukan atau sudah ditolak.');

            $toDate = fn($d) => Carbon::parse($d)->toDateString();
            $current = collect((array) $pengajuan->tanggal_masuk_pengganti)->map($toDate)->values();
            $baru = collect($request->input('baru'))->map($toDate)->values();

            if ($current->isEmpty()) {
                if ($baru->count() !== (int) $pengajuan->total_hari) {
                    $fail('Ganti libur ini ' . (int) $pengajuan->total_hari . ' hari, pilih tepat ' . (int) $pengajuan->total_hari . ' hari masuk.');
                }
                $result = $baru;
            } else {
                $lama = $request->filled('lama') ? $toDate($request->input('lama')) : null;
                if (!$lama || !$current->contains($lama) || $baru->count() !== 1) {
                    $fail('Hari masuk yang diganti tidak valid, muat ulang data.');
                }
                $result = $current->map(fn($d) => $d === $lama ? $baru[0] : $d);
                if ($result->duplicates()->isNotEmpty()) {
                    $fail('Tanggal tersebut sudah dipakai ganti libur ini.');
                }
            }

            $holidays = LiburNasional::namesByDate();
            $claimed = PengajuanLibur::claimedHariMasuk($jatah->employee_id, $pengajuan->id);
            foreach ($result->diff($current) as $date) {
                $label = Carbon::parse($date)->locale('id')->translatedFormat('l, j F Y');
                if (!LiburNasional::isHariGantiLibur($date, $holidays)) {
                    $fail($label . ' bukan hari Minggu / libur nasional.');
                }
                if (!PengajuanLibur::hariMasukSebelumLibur($date, $pengajuan->tanggal_mulai)) {
                    $fail($label . ' belum dikerjakan / tidak sebelum tanggal libur (masuk dulu baru libur).');
                }
                if (!EmployeeSchedule::where('employee_id', $jatah->employee_id)->whereDate('date', $date)->exists()) {
                    $fail($label . ' tidak ada di jadwal karyawan.');
                }
                if (in_array($date, $claimed, true)) {
                    $fail($label . ' sudah dipakai ganti libur lain.');
                }
            }

            $pengajuan->update(['tanggal_masuk_pengganti' => $result->sort()->values()->all()]);

            return $jatah;
        });

        return response()->json([
            'success' => true,
            'message' => 'Hari masuk pengganti diperbarui',
        ] + PengajuanLibur::rincianGantiLibur($jatah->employee_id, LiburNasional::namesByDate()));
    }

    /**
     * Reset annual leave (`jatah_cuti_tahunan`) to 12 for employees
     * whose `tanggal_masuk` is 1 year or older.
     */
    public function resetAnnualLeave()
    {
        try {
            $oneYearAgo = now()->subYear();

            $employees = Employee::active()->get();

            $counts = [
                'total' => $employees->count(),
                'set_12' => 0,
                'set_0' => 0,
                'created' => 0,
                'updated' => 0,
            ];

            foreach ($employees as $employee) {
                $jatah = JatahLibur::firstOrNew(['employee_id' => $employee->id]);
                $isNew = !$jatah->exists;

                // Determine new annual leave based on masa kerja
                if ($employee->tanggal_masuk && $employee->tanggal_masuk <= $oneYearAgo) {
                    $newAnnual = 12;
                } else {
                    $newAnnual = 0;
                }

                // Only update if value changed (or it's a new record)
                $changed = $isNew || ($jatah->jatah_cuti_tahunan !== $newAnnual);

                $jatah->jatah_cuti_tahunan = $newAnnual;
                if (is_null($jatah->jatah_ganti_libur)) {
                    $jatah->jatah_ganti_libur = 0;
                }
                $jatah->save();

                if ($isNew) {
                    $counts['created']++;
                } elseif ($changed) {
                    $counts['updated']++;
                }

                if ($newAnnual === 12) {
                    $counts['set_12']++;
                } else {
                    $counts['set_0']++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Reset annual leave completed',
                'total_employees' => $counts['total'],
                'set_12' => $counts['set_12'],
                'set_0' => $counts['set_0'],
                'updated' => $counts['updated'],
                'created' => $counts['created']
            ]);
        } catch (\Exception $e) {
            Log::error('Error resetting annual leave: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
