<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\User;
use App\Support\Dashboard\Clinics;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Today's pharmacy prescriptions: examined visits (as on E-Resep Farmasi) that have farmasi resep items.
 * "Diproses" = submitted to billing (erm_resepdetail.status 1); the rest still wait for the pharmacy.
 * Totals over all clinics, then one section per clinic with its own queue.
 */
class ResepFarmasiWidget implements Widget
{
    /** Waiting prescriptions listed per clinic */
    private const LIST_LIMIT = 4;

    public function data(User $user, array $filter): array
    {
        $today = Carbon::today()->toDateString();

        $visits = DB::table('erm_visitations as v')
            ->leftJoin('erm_resepdetail as rd', 'rd.visitation_id', '=', 'v.id')
            ->where('v.tanggal_visitation', $today)
            ->whereIn('v.jenis_kunjungan', [1, 2, 5, 6, 7, 8, 9])
            ->where('v.status_kunjungan', 2)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('erm_resepfarmasi as rf')
                    ->whereColumn('rf.visitation_id', 'v.id')
                    ->whereNull('rf.deleted_at');
            });

        $perKlinik = (clone $visits)
            ->groupBy('v.klinik_id')
            ->selectRaw('v.klinik_id, COUNT(DISTINCT v.id) as total,
                COUNT(DISTINCT CASE WHEN rd.status = 1 THEN v.id END) as diproses')
            ->get();

        $waiting = (clone $visits)
            ->where(fn ($q) => $q->whereNull('rd.status')->orWhere('rd.status', '!=', 1))
            ->leftJoin('erm_pasiens as p', 'p.id', '=', 'v.pasien_id')
            ->leftJoin('erm_dokters as d', 'd.id', '=', 'v.dokter_id')
            ->leftJoin('users as u', 'u.id', '=', 'd.user_id')
            ->selectRaw('DISTINCT v.id, v.klinik_id, p.nama as pasien, u.name as dokter, v.no_antrian, v.updated_at,
                (SELECT COUNT(*) FROM erm_resepfarmasi rf2 WHERE rf2.visitation_id = v.id AND rf2.deleted_at IS NULL) as items')
            ->orderBy('v.updated_at')
            ->get()
            ->groupBy(fn ($r) => Clinics::find($r->klinik_id)->id ?? 'none');

        $total = (int) $perKlinik->sum('total');
        $processed = (int) $perKlinik->sum('diproses');

        return [
            'todayLabel' => Carbon::today()->translatedFormat('l, d M Y'),
            'total' => $total,
            'processed' => $processed,
            'pending' => $total - $processed,
            'clinics' => Clinics::sections($perKlinik, function ($rows, $klinik) use ($waiting) {
                $total = (int) $rows->sum('total');
                $processed = (int) $rows->sum('diproses');
                $queue = $waiting->get($klinik->id ?? 'none', collect());

                return [
                    'total' => $total,
                    'processed' => $processed,
                    'pending' => $total - $processed,
                    'waiting' => $queue->take(self::LIST_LIMIT)->all(),
                    'more' => max(0, $queue->count() - self::LIST_LIMIT),
                ];
            }),
        ];
    }
}
