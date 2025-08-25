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

}
