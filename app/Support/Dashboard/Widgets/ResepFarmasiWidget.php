<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Today's pharmacy prescriptions: examined visits (as on E-Resep Farmasi) that have farmasi resep items.
 * "Diproses" = submitted to billing (erm_resepdetail.status 1); the rest still wait for the pharmacy.
 */
class ResepFarmasiWidget implements Widget
{
    private const LIST_LIMIT = 8;

    public function data(User $user, array $filter): array
    {
        $today = Carbon::today()->toDateString();

        $visits = DB::table('erm_visitations as v')
            ->leftJoin('erm_resepdetail as rd', 'rd.visitation_id', '=', 'v.id')
            ->where('v.tanggal_visitation', $today)
            ->whereIn('v.jenis_kunjungan', [1, 2, 5])
            ->where('v.status_kunjungan', 2)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('erm_resepfarmasi as rf')
                    ->whereColumn('rf.visitation_id', 'v.id')
                    ->whereNull('rf.deleted_at');
            });

        $counts = (clone $visits)
            ->selectRaw('COUNT(DISTINCT v.id) as total,
                COUNT(DISTINCT CASE WHEN rd.status = 1 THEN v.id END) as diproses')
            ->first();

        $waiting = (clone $visits)
            ->where(fn ($q) => $q->whereNull('rd.status')->orWhere('rd.status', '!=', 1))
            ->leftJoin('erm_pasiens as p', 'p.id', '=', 'v.pasien_id')
            ->leftJoin('erm_dokters as d', 'd.id', '=', 'v.dokter_id')
            ->leftJoin('users as u', 'u.id', '=', 'd.user_id')
            ->leftJoin('erm_klinik as k', 'k.id', '=', 'v.klinik_id')
            ->selectRaw('DISTINCT v.id, p.nama as pasien, u.name as dokter, k.nama as klinik, v.no_antrian, v.updated_at,
                (SELECT COUNT(*) FROM erm_resepfarmasi rf2 WHERE rf2.visitation_id = v.id AND rf2.deleted_at IS NULL) as items')
            ->orderBy('v.updated_at')
            ->limit(self::LIST_LIMIT)
            ->get();

        $total = (int) $counts->total;
        $processed = (int) $counts->diproses;

        return [
            'todayLabel' => Carbon::today()->translatedFormat('l, d M Y'),
            'total' => $total,
            'processed' => $processed,
            'pending' => $total - $processed,
            'waiting' => $waiting->all(),
        ];
    }
}
