<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/** Doctors scheduled today (HRD dokter schedule): practice hours, whether they are on now, and their patient count. */
class JadwalDokterWidget implements Widget
{
    public function data(User $user, array $filter): array
    {
        $now = Carbon::now();
        $today = $now->toDateString();
        $time = $now->format('H:i:s');

        $patients = DB::table('erm_visitations')
            ->where('tanggal_visitation', $today)
            ->whereIn('jenis_kunjungan', [1, 2, 4, 5, 6, 7, 8, 9])
            ->where('status_kunjungan', '!=', 7)
            ->groupBy('dokter_id')
            ->selectRaw('dokter_id, COUNT(*) as total, SUM(CASE WHEN status_kunjungan = 2 THEN 1 ELSE 0 END) as selesai')
            ->get()
            ->keyBy('dokter_id');

        $schedules = DB::table('hrd_dokter_schedules as s')
            ->join('erm_dokters as d', 'd.id', '=', 's.dokter_id')
            ->leftJoin('users as u', 'u.id', '=', 'd.user_id')
            ->leftJoin('erm_klinik as k', 'k.id', '=', 'd.klinik_id')
            ->leftJoin('erm_spesialisasis as sp', 'sp.id', '=', 'd.spesialisasi_id')
            ->where('s.date', $today)
            ->orderBy('s.jam_mulai')
            ->get(['s.dokter_id', 's.jam_mulai', 's.jam_selesai', 'u.name as nama', 'k.nama as klinik', 'sp.nama as spesialisasi'])
            ->map(function ($row) use ($time, $patients) {
                $state = match (true) {
                    $time < $row->jam_mulai => 'soon',
                    $time > $row->jam_selesai => 'done',
                    default => 'now',
                };

                return (object) [
                    'nama' => $row->nama ?? 'Dokter',
                    'klinik' => $row->klinik,
                    'spesialisasi' => $row->spesialisasi,
                    'jam' => substr((string) $row->jam_mulai, 0, 5) . '–' . substr((string) $row->jam_selesai, 0, 5),
                    'state' => $state,
                    'pasien' => (int) ($patients[$row->dokter_id]->total ?? 0),
                    'selesai' => (int) ($patients[$row->dokter_id]->selesai ?? 0),
                ];
            });

        return [
            'todayLabel' => $now->translatedFormat('l, d M Y'),
            'schedules' => $schedules->all(),
            'onNow' => $schedules->where('state', 'now')->count(),
        ];
    }
}
