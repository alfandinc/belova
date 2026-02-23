<?php
namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRD\DokterSchedule;
use App\Models\HRD\ShiftDokter;

class DokterScheduleController extends Controller
{
    public function index(Request $request)
    {
    // Ambil data shift dokter
    $shifts = ShiftDokter::with('dokter.user')->get();
        // Ambil jadwal dokter untuk 1 bulan (default bulan ini)
        $month = $request->input('month', now()->format('Y-m'));
        $schedules = DokterSchedule::whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$month])->get();
    return view('hrd.dokter_schedule.index', compact('shifts', 'schedules', 'month'));
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
        $schedules = $query->get();
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
            ]);

            $shift = ShiftDokter::create([
                'dokter_id' => $request->input('dokter_id'),
                'jam_mulai' => $request->input('jam_mulai'),
                'jam_selesai' => $request->input('jam_selesai'),
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
            ]);

            $shift = ShiftDokter::findOrFail($id);
            $shift->jam_mulai = $request->input('jam_mulai');
            $shift->jam_selesai = $request->input('jam_selesai');
            // allow changing dokter_id if provided
            if ($request->filled('dokter_id')) {
                $shift->dokter_id = $request->input('dokter_id');
            }
            $shift->save();
            $shift->load('dokter.user');
            return response()->json(['success' => true, 'shift' => $shift]);
        }

}
