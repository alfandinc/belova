<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\User;
use App\Support\Dashboard\Fmt;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Do patients come back? All counts use examined visits (status_kunjungan 2):
 *  - returning rate: patients seen in the period who had been seen before it;
 *  - cohorts: new patients of each of the last 6 months and the share whose next visit came within
 *    30 / 60 / 90 days (a cell is only shown once every patient of that month had the full window);
 *  - visit frequency over the last 12 months, and patients not seen for 6–12 months (at risk of churn).
 */
class RetensiPasienWidget implements Widget
{
    private const COHORT_MONTHS = 6;

    private const WINDOWS = [30, 60, 90];

    public function data(User $user, array $filter): array
    {
        $start = $filter['period_start']->copy()->startOfDay();
        $end = $filter['period_end']->copy()->startOfDay();
        $ref = $end->isFuture() ? Carbon::today() : $end;

        $periodPatients = DB::table('erm_visitations as v')
            ->where('v.status_kunjungan', 2)
            ->whereBetween('v.tanggal_visitation', [$start->toDateString(), $end->toDateString()])
            ->distinct()
            ->pluck('v.pasien_id');
        $returning = $periodPatients->isEmpty() ? 0 : DB::table('erm_visitations')
            ->where('status_kunjungan', 2)
            ->whereIn('pasien_id', $periodPatients)
            ->where('tanggal_visitation', '<', $start->toDateString())
            ->distinct()
            ->count('pasien_id');

        // Everything below depends only on $ref; the at-risk count scans every visit, so it shares the cohorts' cache
        ['cohorts' => $cohorts, 'frequency' => $frequency, 'atRisk' => $atRiskCount] = Cache::remember(
            'dashboard:retensi:v2:' . $ref->toDateString(),
            600,
            fn () => $this->referenceStats($ref)
        );

        // Weighted 90-day retention over the cohorts old enough to measure it
        $mature = collect($cohorts)->filter(fn ($c) => $c['cells'][90] !== null);
        $retention90 = $mature->sum('size') > 0
            ? $mature->sum(fn ($c) => $c['cells'][90] * $c['size']) / $mature->sum('size')
            : null;

        return [
            'periodLabel' => $start->translatedFormat('d M Y') . ' – ' . $end->translatedFormat('d M Y'),
            'periodPatients' => $periodPatients->count(),
            'returning' => $returning,
            'returningPct' => Fmt::share($returning, $periodPatients->count()),
            'retention90' => $retention90,
            'frequency' => $frequency['patients'] > 0 ? $frequency['visits'] / $frequency['patients'] : 0,
            'atRisk' => $atRiskCount,
            'cohorts' => $cohorts,
            'windows' => self::WINDOWS,
        ];
    }

    private function referenceStats(Carbon $ref): array
    {
        $yearAgo = $ref->copy()->subYear();
        $frequency = DB::table('erm_visitations')
            ->where('status_kunjungan', 2)
            ->whereBetween('tanggal_visitation', [$yearAgo->toDateString(), $ref->toDateString()])
            ->selectRaw('COUNT(*) as visits, COUNT(DISTINCT pasien_id) as patients')
            ->first();

        $atRisk = DB::table('erm_visitations')
            ->where('status_kunjungan', 2)
            ->whereNotNull('pasien_id')
            ->groupBy('pasien_id')
            ->havingRaw('MAX(tanggal_visitation) BETWEEN ? AND ?', [$yearAgo->toDateString(), $ref->copy()->subMonths(6)->toDateString()])
            ->select('pasien_id');

        return [
            'cohorts' => $this->cohorts($ref),
            'frequency' => ['visits' => (int) $frequency->visits, 'patients' => (int) $frequency->patients],
            'atRisk' => DB::query()->fromSub($atRisk, 'r')->count(),
        ];
    }

    private function cohorts(Carbon $ref): array
    {
        $firstMonth = $ref->copy()->startOfMonth()->subMonths(self::COHORT_MONTHS - 1);

        $firstVisits = DB::table('erm_visitations')
            ->whereNotNull('pasien_id')
            ->where('status_kunjungan', 2)
            ->groupBy('pasien_id')
            ->selectRaw('pasien_id, MIN(tanggal_visitation) as first_visit');

        $patients = DB::query()
            ->fromSub($firstVisits, 'fv')
            ->leftJoin('erm_visitations as v', function ($join) {
                $join->on('v.pasien_id', '=', 'fv.pasien_id')
                    ->where('v.status_kunjungan', 2)
                    ->whereColumn('v.tanggal_visitation', '>', 'fv.first_visit');
            })
            ->whereBetween('fv.first_visit', [$firstMonth->toDateString(), $ref->toDateString()])
            ->groupBy('fv.pasien_id', 'fv.first_visit')
            ->selectRaw('fv.first_visit, MIN(v.tanggal_visitation) as next_visit')
            ->get();

        $cohorts = [];
        for ($i = 0; $i < self::COHORT_MONTHS; $i++) {
            $month = $firstMonth->copy()->addMonths($i);
            $monthEnd = $month->copy()->endOfMonth()->startOfDay();
            $members = $patients->filter(fn ($p) => Carbon::parse($p->first_visit)->isSameMonth($month));
            $size = $members->count();

            $cells = [];
            foreach (self::WINDOWS as $days) {
                // Only measurable once the last patient of the month had the whole window
                if ($size === 0 || $monthEnd->copy()->addDays($days)->gt($ref)) {
                    $cells[$days] = null;
                    continue;
                }
                $back = $members->filter(fn ($p) => $p->next_visit
                    && Carbon::parse($p->first_visit)->diffInDays(Carbon::parse($p->next_visit)) <= $days)->count();
                $cells[$days] = Fmt::share($back, $size);
            }

            $cohorts[] = ['label' => $month->translatedFormat('M Y'), 'size' => $size, 'cells' => $cells];
        }

        return array_reverse($cohorts);
    }
}
