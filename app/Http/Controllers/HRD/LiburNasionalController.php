<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\EmployeeSchedule;
use App\Models\HRD\LiburNasional;
use App\Models\HRD\PengajuanLibur;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Master data: national holidays. Employees scheduled on a holiday earn +1 jatah ganti libur
 * (same rule as a scheduled Sunday, see EmployeeScheduleController::store), so adding, moving or
 * removing a holiday also adjusts the balance of employees already scheduled on that date.
 */
class LiburNasionalController extends Controller
{
    public function index(Request $request)
    {
        $tahun = (int) $request->input('tahun', now()->year);

        if ($request->ajax()) {
            $holidays = LiburNasional::whereYear('tanggal', $tahun)->orderBy('tanggal')->get();

            // How many employees are scheduled on each date (their balance depends on it)
            $scheduled = EmployeeSchedule::whereIn('date', $holidays->map(fn($h) => $h->tanggal->toDateString()))
                ->select('date', DB::raw('COUNT(DISTINCT employee_id) as total'))
                ->groupBy('date')
                ->pluck('total', 'date')
                ->mapWithKeys(fn($total, $date) => [Carbon::parse($date)->toDateString() => (int) $total]);

            return response()->json([
                'success' => true,
                'data' => $holidays->map(fn($h) => [
                    'id' => $h->id,
                    'tanggal' => $h->tanggal->toDateString(),
                    'hari' => $h->tanggal->locale('id')->translatedFormat('l'),
                    'tanggal_label' => $h->tanggal->locale('id')->translatedFormat('j F Y'),
                    'nama' => $h->nama,
                    'is_sunday' => $h->tanggal->isSunday(),
                    'terjadwal' => $scheduled[$h->tanggal->toDateString()] ?? 0,
                ]),
            ]);
        }

        // Managed from a modal on the schedule page (Jadwal Karyawan > Lainnya > Libur nasional)
        return redirect()->route('hrd.schedule.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tanggal' => 'required|date|unique:hrd_libur_nasional,tanggal',
            'nama' => 'required|string|max:150',
        ], [
            'tanggal.unique' => 'Tanggal ini sudah terdaftar sebagai libur nasional.',
        ]);

        $affected = DB::transaction(function () use ($data) {
            $holiday = LiburNasional::create($data);

            return $this->scheduledCount($holiday->tanggal->toDateString());
        });

        return response()->json([
            'success' => true,
            'message' => 'Libur nasional ditambahkan.' . $this->affectedMessage($affected, +1),
        ]);
    }

    public function update(Request $request, $id)
    {
        $holiday = LiburNasional::findOrFail($id);
        $data = $request->validate([
            'tanggal' => ['required', 'date', Rule::unique('hrd_libur_nasional', 'tanggal')->ignore($holiday->id)],
            'nama' => 'required|string|max:150',
        ], [
            'tanggal.unique' => 'Tanggal ini sudah terdaftar sebagai libur nasional.',
        ]);

        $oldDate = $holiday->tanggal->toDateString();
        $newDate = Carbon::parse($data['tanggal'])->toDateString();

        if ($oldDate !== $newDate && ($error = $this->claimedError($oldDate))) {
            return response()->json(['success' => false, 'message' => 'Tanggal libur tidak bisa dipindah. ' . $error], 422);
        }

        $message = DB::transaction(function () use ($holiday, $data, $oldDate, $newDate) {
            $holiday->update($data);
            if ($oldDate === $newDate) {
                return 'Libur nasional diperbarui.';
            }

            // Moved to another date: employees scheduled on the old date lose it, on the new date gain it
            $removed = $this->scheduledCount($oldDate);
            $added = $this->scheduledCount($newDate);

            return 'Libur nasional diperbarui.' . $this->affectedMessage($removed, -1) . $this->affectedMessage($added, +1);
        });

        return response()->json(['success' => true, 'message' => $message]);
    }

    public function destroy($id)
    {
        $holiday = LiburNasional::findOrFail($id);

        if ($error = $this->claimedError($holiday->tanggal->toDateString())) {
            return response()->json(['success' => false, 'message' => 'Libur nasional tidak bisa dihapus. ' . $error], 422);
        }

        $affected = DB::transaction(function () use ($holiday) {
            $date = $holiday->tanggal->toDateString();
            $holiday->delete();

            return $this->scheduledCount($date);
        });

        return response()->json([
            'success' => true,
            'message' => 'Libur nasional dihapus.' . $this->affectedMessage($affected, -1),
        ]);
    }

    /**
     * Employees scheduled on $date, whose ganti libur balance (counted from the schedule) gains or loses this
     * day. Sundays and days before gantiLiburMulai() are skipped: the holiday list changes nothing for them.
     */
    private function scheduledCount(string $date): int
    {
        if (Carbon::parse($date)->isSunday() || $date < LiburNasional::gantiLiburMulai()) {
            return 0;
        }

        return EmployeeSchedule::whereDate('date', $date)->distinct()->count('employee_id');
    }

    /**
     * A holiday (not on a Sunday) whose date some employee already used as ganti libur pengganti
     * cannot be removed or moved, otherwise that day off would lose the day it replaces.
     */
    private function claimedError(string $date): ?string
    {
        if (Carbon::parse($date)->isSunday()) {
            return null;
        }
        $employeeIds = EmployeeSchedule::whereDate('date', $date)->distinct()->pluck('employee_id');
        foreach ($employeeIds as $employeeId) {
            if ($error = PengajuanLibur::claimedError($employeeId, $date)) {
                return $error;
            }
        }

        return null;
    }

    private function affectedMessage(int $count, int $delta): string
    {
        if ($count === 0) {
            return '';
        }

        return ' Saldo ganti libur ' . ($delta > 0 ? 'bertambah' : 'berkurang') . ' 1 hari untuk ' . $count . ' karyawan yang terjadwal pada tanggal tersebut.';
    }
}
