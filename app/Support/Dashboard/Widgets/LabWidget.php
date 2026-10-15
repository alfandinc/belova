<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\User;
use App\Support\Dashboard\Clinics;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Lab requests still open (requested / processing) and what was completed today: totals over all
 * clinics, then one section per clinic (the request's visit clinic) with its open requests, oldest first.
 */
class LabWidget implements Widget
{
    /** Open requests listed per clinic */
    private const LIST_LIMIT = 4;

    public function data(User $user, array $filter): array
    {
        $today = Carbon::today();

        $perKlinik = DB::table('erm_lab_permintaan as lp')
            ->leftJoin('erm_visitations as v', 'v.id', '=', 'lp.visitation_id')
            ->groupBy('v.klinik_id')
            ->selectRaw("v.klinik_id,
                SUM(CASE WHEN lp.status = 'requested' THEN 1 ELSE 0 END) as requested,
                SUM(CASE WHEN lp.status = 'processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN lp.status = 'completed' AND DATE(lp.completed_at) = ? THEN 1 ELSE 0 END) as completed_today", [$today->toDateString()])
            ->get();

        // One row per visit: a visit usually orders several tests at once
        $open = DB::table('erm_lab_permintaan as lp')
            ->join('erm_visitations as v', 'v.id', '=', 'lp.visitation_id')
            ->leftJoin('erm_pasiens as p', 'p.id', '=', 'v.pasien_id')
            ->leftJoin('erm_dokters as d', 'd.id', '=', 'lp.dokter_id')
            ->leftJoin('users as u', 'u.id', '=', 'd.user_id')
            ->whereIn('lp.status', ['requested', 'processing'])
            ->groupBy('lp.visitation_id', 'v.klinik_id', 'p.nama', 'u.name')
            ->selectRaw("lp.visitation_id, v.klinik_id, p.nama as pasien, u.name as dokter, COUNT(*) as tests,
                MIN(COALESCE(lp.requested_at, lp.created_at)) as since,
                SUM(CASE WHEN lp.status = 'processing' THEN 1 ELSE 0 END) as processing")
            ->orderBy('since')
            ->get()
            ->map(function ($row) use ($today) {
                $since = Carbon::parse($row->since);

                return (object) [
                    'klinikKey' => Clinics::find($row->klinik_id)->id ?? 'none',
                    'visitationId' => $row->visitation_id,
                    'pasien' => $row->pasien ?? 'Pasien',
                    'dokter' => $row->dokter,
                    'tests' => (int) $row->tests,
                    'processing' => (int) $row->processing > 0,
                    'since' => $since,
                    'old' => $since->lt($today),
                ];
            })
            ->groupBy('klinikKey');

        return [
            'requested' => (int) $perKlinik->sum('requested'),
            'processing' => (int) $perKlinik->sum('processing'),
            'completedToday' => (int) $perKlinik->sum('completed_today'),
            'clinics' => Clinics::sections($perKlinik, function ($rows, $klinik) use ($open) {
                $list = $open->get($klinik->id ?? 'none', collect());

                return [
                    'requested' => (int) $rows->sum('requested'),
                    'processing' => (int) $rows->sum('processing'),
                    'completedToday' => (int) $rows->sum('completed_today'),
                    'open' => $list->take(self::LIST_LIMIT)->all(),
                    'more' => max(0, $list->count() - self::LIST_LIMIT),
                ];
            }),
        ];
    }
}
