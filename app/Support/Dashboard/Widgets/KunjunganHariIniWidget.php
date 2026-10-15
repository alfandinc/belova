<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\User;
use App\Support\Dashboard\Clinics;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Today's outpatient visits (same visit types and status codes as Rawat Jalan), one section per clinic:
 * who is still waiting, who is done, and the clinic's queue. A doctor (who is not Admin) sees only their
 * own patients, as on Rawat Jalan, and only the clinics they have patients in.
 */
class KunjunganHariIniWidget implements Widget
{
    private const VISIT_TYPES = [1, 2, 4, 5, 6, 7, 8, 9];

    /** Waiting patients listed per clinic */
    private const WAITING_LIMIT = 4;

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

        $counts = (clone $base)
            ->groupBy('v.klinik_id')
            ->selectRaw('v.klinik_id,
                SUM(CASE WHEN v.status_kunjungan = 0 THEN 1 ELSE 0 END) as belum_datang,
                SUM(CASE WHEN v.status_kunjungan = 1 THEN 1 ELSE 0 END) as menunggu,
                SUM(CASE WHEN v.status_kunjungan = 2 THEN 1 ELSE 0 END) as selesai,
                SUM(CASE WHEN v.status_kunjungan = 7 THEN 1 ELSE 0 END) as batal,
                SUM(CASE WHEN v.status_kunjungan != 7 THEN 1 ELSE 0 END) as total')
            ->get();

        $waiting = (clone $base)
            ->leftJoin('erm_pasiens as p', 'p.id', '=', 'v.pasien_id')
            ->leftJoin('erm_dokters as d', 'd.id', '=', 'v.dokter_id')
            ->leftJoin('users as u', 'u.id', '=', 'd.user_id')
            ->where('v.status_kunjungan', 1)
            ->orderByRaw('v.no_antrian IS NULL, v.no_antrian')
            ->orderBy('v.waktu_kunjungan')
            ->get(['v.klinik_id', 'p.nama as pasien', 'v.no_antrian', 'v.waktu_kunjungan', 'u.name as dokter'])
            ->groupBy(fn ($r) => Clinics::find($r->klinik_id)->id ?? 'none');

        $sum = fn (string $column) => (int) $counts->sum($column);

        return [
            'todayLabel' => Carbon::today()->translatedFormat('l, d M Y'),
            'ownPatients' => (bool) $dokterId,
            'total' => $sum('total'),
            'belumDatang' => $sum('belum_datang'),
            'menunggu' => $sum('menunggu'),
            'selesai' => $sum('selesai'),
            'batal' => $sum('batal'),
            'progress' => $sum('total') > 0 ? $sum('selesai') / $sum('total') * 100 : 0,
            'clinics' => Clinics::sections($counts, function ($rows, $klinik) use ($waiting) {
                $total = (int) $rows->sum('total');
                $queue = $waiting->get($klinik->id ?? 'none', collect());

                return [
                    'total' => $total,
                    'menunggu' => (int) $rows->sum('menunggu'),
                    'selesai' => (int) $rows->sum('selesai'),
                    'belumDatang' => (int) $rows->sum('belum_datang'),
                    'batal' => (int) $rows->sum('batal'),
                    'progress' => $total > 0 ? $rows->sum('selesai') / $total * 100 : 0,
                    'waiting' => $queue->take(self::WAITING_LIMIT)->all(),
                    'moreWaiting' => max(0, $queue->count() - self::WAITING_LIMIT),
                ];
            }, (bool) $dokterId),
        ];
    }
}
