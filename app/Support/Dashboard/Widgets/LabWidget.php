<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/** Lab requests still open (requested / processing), oldest first, and what was completed today. */
class LabWidget implements Widget
{
    private const LIST_LIMIT = 7;

    public function data(User $user, array $filter): array
    {
        $today = Carbon::today();

        $counts = DB::table('erm_lab_permintaan')
            ->selectRaw("SUM(CASE WHEN status = 'requested' THEN 1 ELSE 0 END) as requested,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = 'completed' AND DATE(completed_at) = ? THEN 1 ELSE 0 END) as completed_today", [$today->toDateString()])
            ->first();

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
            'requested' => (int) $counts->requested,
            'processing' => (int) $counts->processing,
            'completedToday' => (int) $counts->completed_today,
            'open' => $open->all(),
        ];
    }
}
