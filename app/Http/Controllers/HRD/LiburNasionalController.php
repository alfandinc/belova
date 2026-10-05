<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\EmployeeSchedule;
use App\Models\HRD\JatahLibur;
use App\Models\HRD\LiburNasional;
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

        return view('hrd.master.libur-nasional.index', compact('tahun'));
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

            return $this->adjustGantiLibur($holiday->tanggal->toDateString(), +1);
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

        $message = DB::transaction(function () use ($holiday, $data, $oldDate, $newDate) {
            $holiday->update($data);
            if ($oldDate === $newDate) {
                return 'Libur nasional diperbarui.';
            }

            // Moved to another date: undo the old date's balance, apply the new one
            $removed = $this->adjustGantiLibur($oldDate, -1);
            $added = $this->adjustGantiLibur($newDate, +1);

            return 'Libur nasional diperbarui.' . $this->affectedMessage($removed, -1) . $this->affectedMessage($added, +1);
        });

        return response()->json(['success' => true, 'message' => $message]);
    }

    public function destroy($id)
    {
        $holiday = LiburNasional::findOrFail($id);

        $affected = DB::transaction(function () use ($holiday) {
            $date = $holiday->tanggal->toDateString();
            $holiday->delete();

            return $this->adjustGantiLibur($date, -1);
        });

        return response()->json([
            'success' => true,
            'message' => 'Libur nasional dihapus.' . $this->affectedMessage($affected, -1),
        ]);
    }

    /**
     * +1 / -1 jatah ganti libur for every employee scheduled on $date. Sundays are skipped:
     * they already earn ganti libur regardless of the holiday list. Returns the number of employees changed.
     */
    private function adjustGantiLibur(string $date, int $delta): int
    {
        if (Carbon::parse($date)->isSunday()) {
            return 0;
        }

        $employeeIds = EmployeeSchedule::whereDate('date', $date)->distinct()->pluck('employee_id');

        foreach ($employeeIds as $employeeId) {
            JatahLibur::firstOrCreate(
                ['employee_id' => $employeeId],
                ['jatah_cuti_tahunan' => 0, 'jatah_ganti_libur' => 0]
            );
            $jatah = JatahLibur::where('employee_id', $employeeId)->lockForUpdate()->first();
            $jatah->jatah_ganti_libur = max(0, (int) $jatah->jatah_ganti_libur + $delta);
            $jatah->save();
        }

        return $employeeIds->count();
    }

    private function affectedMessage(int $count, int $delta): string
    {
        if ($count === 0) {
            return '';
        }

        return ' Jatah ganti libur ' . ($delta > 0 ? '+1' : '-1') . ' untuk ' . $count . ' karyawan yang terjadwal pada tanggal tersebut.';
    }
}
