<?php

namespace App\Http\Controllers\HRD;

use App\Helpers\HrdConfig;
use App\Http\Controllers\Controller;
use App\Models\HRD\EmployeeSchedule;
use App\Models\HRD\LiburNasional;
use App\Models\HRD\PengajuanLibur;
use App\Models\HRD\ScheduleLog;
use App\Models\HRD\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShiftController extends Controller
{
    /**
     * Store a newly created shift.
     */
    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $shift = Shift::create($data);
        $this->saveMinStaff($request, $shift);

        return response()->json([
            'success' => true,
            'shift'   => $shift,
        ]);
    }

    /**
     * Update an existing shift.
     */
    public function update(Request $request, Shift $shift)
    {
        $data = $this->validateData($request);
        $shift->update($data);
        $this->saveMinStaff($request, $shift);

        return response()->json([
            'success' => true,
            'shift'   => $shift,
        ]);
    }

    /**
     * Remove the specified shift.
     * This will also cascade-delete any related schedules due to FK constraints.
     */
    public function destroy(Request $request, Shift $shift)
    {
        $holidays = LiburNasional::namesByDate();

        // Hari Minggu / libur nasional yang jadi kosong karena jadwalnya ikut terhapus (cascade):
        // sama seperti mengosongkan jadwal -> ditolak jika sudah dipakai ganti libur (saldo ikut jadwal)
        $emptied = EmployeeSchedule::where('shift_id', $shift->id)->get()
            ->map(fn($s) => [$s->employee_id, Carbon::parse($s->date)->toDateString()])
            ->unique(fn($x) => $x[0] . '_' . $x[1])
            ->filter(fn($x) => LiburNasional::isHariGantiLibur($x[1], $holidays)
                && !EmployeeSchedule::where('employee_id', $x[0])->whereDate('date', $x[1])->where('shift_id', '!=', $shift->id)->exists())
            ->values();

        foreach ($emptied as [$employeeId, $date]) {
            if ($error = PengajuanLibur::claimedError($employeeId, $date)) {
                return response()->json(['success' => false, 'message' => 'Shift tidak bisa dihapus. ' . $error], 422);
            }
        }

        // Every day that loses this shift goes to the audit log (the cascade itself leaves no trace)
        $days = EmployeeSchedule::where('shift_id', $shift->id)->get(['employee_id', 'date'])
            ->map(fn($s) => [$s->employee_id, Carbon::parse($s->date)->toDateString()])
            ->unique(fn($x) => $x[0] . '_' . $x[1]);
        $before = $days->mapWithKeys(fn($x) => [$x[0] . '_' . $x[1] => ScheduleLog::stateOf($x[0], $x[1])]);

        DB::transaction(function () use ($shift, $days, $before) {
            $shift->delete();
            foreach ($days as [$employeeId, $date]) {
                ScheduleLog::record($employeeId, $date, $before[$employeeId . '_' . $date], ScheduleLog::stateOf($employeeId, $date), 'hapus_shift');
            }
        });

        return response()->json([
            'success' => true,
            'ganti_libur_removed' => $emptied->count(),
        ]);
    }

    /**
     * Validate and normalize shift data.
     */
    /** Minimum staff per day for this shift (0 / empty = no minimum), flagged in the schedule grid footer. */
    private function saveMinStaff(Request $request, Shift $shift): void
    {
        $request->validate(['min_staff' => ['nullable', 'integer', 'min:0', 'max:100']]);
        if ($request->has('min_staff')) {
            HrdConfig::setShiftMinStaff($shift->id, (int) $request->input('min_staff'));
        }
        $shift->setAttribute('min_staff', HrdConfig::getShiftMinStaff()[$shift->id] ?? 0);
    }

    protected function validateData(Request $request): array
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:191'],
            'start_time' => ['required', 'string', 'max:8'],
            'end_time'   => ['required', 'string', 'max:8'],
            'active'     => ['nullable', 'boolean'],
            'color'      => ['nullable', 'string', 'max:20'],
        ]);

        $validated['start_time'] = $this->normalizeTime($validated['start_time']);
        $validated['end_time']   = $this->normalizeTime($validated['end_time']);

        // Default to active if not explicitly provided
        $validated['active'] = $request->boolean('active', true);

        // Normalize color (optional) - ensure it starts with '#'
        if (!empty($validated['color'])) {
            $color = trim($validated['color']);
            if ($color && $color[0] !== '#') {
                $color = '#' . $color;
            }
            $validated['color'] = $color;
        }

        return $validated;
    }

    /**
     * Ensure time is in H:i:s format.
     */
    protected function normalizeTime(string $time): string
    {
        $time = trim($time);
        // Accept formats like HH:MM or HH:MM:SS; append ":00" if seconds omitted.
        if (strlen($time) === 5) {
            return $time . ':00';
        }

        return $time;
    }
}
