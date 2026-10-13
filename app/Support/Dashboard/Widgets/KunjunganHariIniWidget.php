<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Today's outpatient visits (same visit types and status codes as Rawat Jalan): who is still waiting,
 * who is done, per clinic. A doctor (who is not Admin) sees only their own patients, as on Rawat Jalan.
 */
class KunjunganHariIniWidget implements Widget
{
    private const VISIT_TYPES = [1, 2, 4, 5, 6, 7, 8, 9];

    private const WAITING_LIMIT = 8;

    public function data(User $user, array $filter): array
    {
        $today = Carbon::today()->toDateString();
        $dokterId = $user->hasRole('Dokter') && !$user->hasRole('Admin')
            ? DB::table('erm_dokters')->where('user_id', $user->id)->value('id')
            : null;

        $base = DB::table('erm_visitations as v')
            ->whereIn('v.jenis_kunjungan', self::VISIT_TYPES)
            ->where('v.tanggal_visitation', $today)
            ->when($dokterId, fn ($q) => $q->where('v.dokter_id', $dokterId));

        $status = (clone $base)
            ->selectRaw('COUNT(*) as total,
                SUM(CASE WHEN v.status_kunjungan = 0 THEN 1 ELSE 0 END) as belum_datang,
                SUM(CASE WHEN v.status_kunjungan = 1 THEN 1 ELSE 0 END) as menunggu,
                SUM(CASE WHEN v.status_kunjungan = 2 THEN 1 ELSE 0 END) as selesai,
                SUM(CASE WHEN v.status_kunjungan = 7 THEN 1 ELSE 0 END) as batal')
            ->first();

        $perKlinik = (clone $base)
            ->leftJoin('erm_klinik as k', 'k.id', '=', 'v.klinik_id')
            ->where('v.status_kunjungan', '!=', 7)
            ->groupBy('k.nama')
            ->selectRaw("COALESCE(k.nama, 'Tanpa klinik') as nama, COUNT(*) as total,
                SUM(CASE WHEN v.status_kunjungan = 2 THEN 1 ELSE 0 END) as selesai")
            ->orderByDesc('total')
            ->get();

        $waiting = (clone $base)
            ->leftJoin('erm_pasiens as p', 'p.id', '=', 'v.pasien_id')
            ->leftJoin('erm_dokters as d', 'd.id', '=', 'v.dokter_id')
            ->leftJoin('users as u', 'u.id', '=', 'd.user_id')
            ->leftJoin('erm_klinik as k', 'k.id', '=', 'v.klinik_id')
            ->where('v.status_kunjungan', 1)
            ->orderByRaw('v.no_antrian IS NULL, v.no_antrian')
            ->orderBy('v.waktu_kunjungan')
            ->limit(self::WAITING_LIMIT)
            ->get(['p.nama as pasien', 'v.no_antrian', 'v.waktu_kunjungan', 'u.name as dokter', 'k.nama as klinik']);

        $active = (int) $status->total - (int) $status->batal;

        return [
            'todayLabel' => Carbon::today()->translatedFormat('l, d M Y'),
            'ownPatients' => (bool) $dokterId,
            'total' => $active,
            'belumDatang' => (int) $status->belum_datang,
            'menunggu' => (int) $status->menunggu,
            'selesai' => (int) $status->selesai,
            'batal' => (int) $status->batal,
            'progress' => $active > 0 ? (int) $status->selesai / $active * 100 : 0,
            'perKlinik' => $perKlinik->all(),
            'waiting' => $waiting->all(),
        ];
    }
}
