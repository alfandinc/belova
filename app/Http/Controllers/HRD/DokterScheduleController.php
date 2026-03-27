<?php
namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRD\DokterSchedule;
use App\Models\HRD\ShiftDokter;
use Illuminate\Support\Collection;

class DokterScheduleController extends Controller
{
    protected function defaultDoctorColor(int $dokterId): string
    {
        $palette = [
            '#FFD54F',
            '#FF8A65',
            '#FF7043',
            '#BA68C8',
            '#64B5F6',
            '#4DB6AC',
            '#FFB74D',
            '#E57373',
        ];

        return $palette[$dokterId % count($palette)];
    }

    protected function doctorColorMap(?Collection $shifts = null): array
    {
        $source = $shifts ?: ShiftDokter::query()
            ->select('dokter_id', 'color_hex', 'id')
            ->orderByDesc('id')
            ->get();

        $map = [];
        foreach ($source as $shift) {
            $dokterId = (int) $shift->dokter_id;
            if (!$dokterId || isset($map[$dokterId])) {
                continue;
            }

            $map[$dokterId] = $this->normalizeColor($shift->color_hex, $dokterId);
        }

        return $map;
    }

    protected function normalizeColor(?string $color, ?int $dokterId = null): string
    {
        $trimmed = strtoupper(trim((string) $color));
        if (preg_match('/^#[0-9A-F]{6}$/', $trimmed)) {
            return $trimmed;
        }

        return $this->defaultDoctorColor((int) $dokterId);
    }

    public function index(Request $request)
    {
    // Ambil data shift dokter
    $shifts = ShiftDokter::with('dokter.user')->get()->map(function ($shift) {
            $shift->color_hex = $this->normalizeColor($shift->color_hex, (int) $shift->dokter_id);
            return $shift;
        });
        // Ambil jadwal dokter untuk 1 bulan (default bulan ini)
        $month = $request->input('month', now()->format('Y-m'));
        $schedules = DokterSchedule::whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$month])->get();
        $doctorColorMap = $this->doctorColorMap($shifts);
    return view('hrd.dokter_schedule.index', compact('shifts', 'schedules', 'month', 'doctorColorMap'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'dokter_ids' => 'required|array',
        ]);
        $date = $request->date;
        $dokterIds = $request->dokter_ids;
        // Hapus jadwal lama di tanggal tersebut
        DokterSchedule::where('date', $date)->delete();
        // Simpan jadwal baru
        foreach ($dokterIds as $dokterId) {
            DokterSchedule::create([
                'dokter_id' => $dokterId,
                'date' => $date,
                'jam_mulai' => $request->jam_mulai ?? null,
                'jam_selesai' => $request->jam_selesai ?? null,
            ]);
        }
        return response()->json(['success' => true]);
    }

    public function storeSingle(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'dokter_id' => 'required',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required',
        ]);

        $date = $request->input('date');
        $dokterId = $request->input('dokter_id');

        $jadwal = DokterSchedule::updateOrCreate(
            [
                'date' => $date,
                'dokter_id' => $dokterId,
            ],
            [
                'jam_mulai' => $request->input('jam_mulai'),
                'jam_selesai' => $request->input('jam_selesai'),
            ]
        );

        return response()->json(['success' => true, 'id' => $jadwal->id]);
    }

    public function moveJadwal(Request $request, $id)
    {
        $request->validate([
            'target_date' => 'required|date',
        ]);

        $targetDate = $request->input('target_date');
        $jadwal = DokterSchedule::findOrFail($id);

        // No-op
        if ((string) $jadwal->date === (string) $targetDate) {
            return response()->json(['success' => true, 'id' => $jadwal->id]);
        }

        // If the target date already has the same dokter, merge by updating the target and deleting the source.
        $existing = DokterSchedule::where('date', $targetDate)
            ->where('dokter_id', $jadwal->dokter_id)
            ->first();

        if ($existing) {
            $existing->jam_mulai = $jadwal->jam_mulai;
            $existing->jam_selesai = $jadwal->jam_selesai;
            $existing->save();

            $jadwal->delete();
            return response()->json(['success' => true, 'id' => $existing->id, 'merged' => true]);
        }

        $jadwal->date = $targetDate;
        $jadwal->save();

        return response()->json(['success' => true, 'id' => $jadwal->id]);
    }

    public function getSchedules(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $clinicId = $request->input('clinic_id');
        $query = DokterSchedule::whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$month])->with(['dokter.user']);
        if ($clinicId) {
            $query->whereHas('dokter', function($q) use ($clinicId) {
                $q->where('klinik_id', $clinicId);
            });
        }
        $colorMap = $this->doctorColorMap();
        $schedules = $query->get()->map(function ($schedule) use ($colorMap) {
            $schedule->doctor_color = $colorMap[$schedule->dokter_id] ?? $this->defaultDoctorColor((int) $schedule->dokter_id);
            return $schedule;
        });
        return response()->json($schedules);
    }

        public function updateJam(Request $request, $id)
    {
        $request->validate([
            'jam_mulai' => 'required',
            'jam_selesai' => 'required',
        ]);
        $jadwal = DokterSchedule::findOrFail($id);
        $jadwal->jam_mulai = $request->jam_mulai;
        $jadwal->jam_selesai = $request->jam_selesai;
        $jadwal->save();
        return response()->json(['success' => true]);
    }
        public function deleteJadwal(Request $request, $id)
    {
        $jadwal = DokterSchedule::findOrFail($id);
        $jadwal->delete();
        return response()->json(['success' => true]);
    }

        // Create a ShiftDokter record (used from Daftar Dokter sidebar)
        public function storeShift(Request $request)
        {
            $request->validate([
                'dokter_id' => 'required|integer',
                'jam_mulai' => 'required',
                'jam_selesai' => 'required',
                'color_hex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            ]);

            $shift = ShiftDokter::create([
                'dokter_id' => $request->input('dokter_id'),
                'jam_mulai' => $request->input('jam_mulai'),
                'jam_selesai' => $request->input('jam_selesai'),
                'color_hex' => $this->normalizeColor($request->input('color_hex'), (int) $request->input('dokter_id')),
            ]);

            $shift->load('dokter.user');
            return response()->json(['success' => true, 'shift' => $shift]);
        }

        // Update an existing ShiftDokter
        public function updateShift(Request $request, $id)
        {
            $request->validate([
                'jam_mulai' => 'required',
                'jam_selesai' => 'required',
                'color_hex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            ]);

            $shift = ShiftDokter::findOrFail($id);
            $shift->jam_mulai = $request->input('jam_mulai');
            $shift->jam_selesai = $request->input('jam_selesai');
            // allow changing dokter_id if provided
            if ($request->filled('dokter_id')) {
                $shift->dokter_id = $request->input('dokter_id');
            }
            $targetDokterId = (int) ($request->input('dokter_id') ?: $shift->dokter_id);
            $shift->color_hex = $this->normalizeColor($request->input('color_hex'), $targetDokterId);
            $shift->save();
            $shift->load('dokter.user');
            return response()->json(['success' => true, 'shift' => $shift]);
        }

}
