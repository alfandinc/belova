<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\User;
use App\Support\Dashboard\Clinics;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/** Lab requests still open (requested / processing), oldest first, and what was completed today. */
class LabWidget implements Widget
{
    private const LIST_LIMIT = 7;

    public function data(User $user, array $filter): array
    {
        $today = Carbon::today();

        // Per clinic of the request's visit; the headline numbers are the sums
        $perKlinik = DB::table('erm_lab_permintaan as lp')
            ->leftJoin('erm_visitations as v', 'v.id', '=', 'lp.visitation_id')
            ->groupBy('v.klinik_id')
            ->selectRaw("v.klinik_id,
                SUM(CASE WHEN lp.status = 'requested' THEN 1 ELSE 0 END) as requested,
                SUM(CASE WHEN lp.status = 'processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN lp.status = 'completed' AND DATE(lp.completed_at) = ? THEN 1 ELSE 0 END) as completed_today", [$today->toDateString()])
            ->get()
            ->keyBy(fn ($r) => (string) $r->klinik_id);
        $split = fn (string $column) => Clinics::split($perKlinik->map(fn ($r) => (int) $r->{$column}));

        // One row per visit: a visit usually orders several tests at once
        $open = DB::table('erm_lab_permintaan as lp')
            ->join('erm_visitations as v', 'v.id', '=', 'lp.visitation_id')
            ->leftJoin('erm_pasiens as p', 'p.id', '=', 'v.pasien_id')
            ->leftJoin('erm_dokters as d', 'd.id', '=', 'lp.dokter_id')
            ->leftJoin('users as u', 'u.id', '=', 'd.user_id')
            ->whereIn('lp.status', ['requested', 'processing'])
            ->groupBy('lp.visitation_id', 'p.nama', 'u.name')
            ->selectRaw("lp.visitation_id, p.nama as pasien, u.name as dokter, COUNT(*) as tests,
                MIN(COALESCE(lp.requested_at, lp.created_at)) as since,
                SUM(CASE WHEN lp.status = 'processing' THEN 1 ELSE 0 END) as processing")
            ->orderBy('since')
            ->limit(self::LIST_LIMIT)
            ->get()
            ->map(function ($row) use ($today) {
                $since = Carbon::parse($row->since);

                return (object) [
                    'visitationId' => $row->visitation_id,
                    'pasien' => $row->pasien ?? 'Pasien',
                    'dokter' => $row->dokter,
                    'tests' => (int) $row->tests,
                    'processing' => (int) $row->processing > 0,
                    'since' => $since,
                    'old' => $since->lt($today),
                ];
            });

        return [
            'requested' => (int) $perKlinik->sum('requested'),
            'processing' => (int) $perKlinik->sum('processing'),
            'completedToday' => (int) $perKlinik->sum('completed_today'),
            'clinics' => [
                'requested' => $split('requested'),
                'processing' => $split('processing'),
                'completedToday' => $split('completed_today'),
            ],
            'open' => $open->all(),
        ];
    }
}
