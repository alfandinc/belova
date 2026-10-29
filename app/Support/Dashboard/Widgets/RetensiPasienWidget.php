<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\User;
use App\Support\Dashboard\Clinics;
use App\Support\Dashboard\Fmt;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Do patients come back? Per clinic only: the clinics are managed separately, so a return counts only to the
 * same clinic (Premiere → Skin is a new patient for Skin). All counts use examined visits (status_kunjungan 2):
 *  - returning vs new: patients seen at the clinic in the period who had been seen there before it, and the rest
 *    (first visit to that clinic) — together 100% of the period's patients;
 *  - cohorts: the clinic's new patients of each of the last 6 months and the share whose next visit there came
 *    within 30 / 60 / 90 days (a cell is only shown once every patient of that month had the full window);
 *  - visit frequency over the last 12 months, and patients not seen there for 6–12 months (at risk of churn).
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

        $period = $this->periodByKlinik($start, $end);
        // Everything else depends only on $ref; the at-risk count scans every visit, so it is cached
        $reference = Cache::remember(
            'dashboard:retensi:v4:' . $ref->toDateString(),
            600,
            fn () => $this->referenceByKlinik($ref)
        );

        return [
            'periodLabel' => $start->translatedFormat('d M Y') . ' – ' . $end->translatedFormat('d M Y'),
            'windows' => self::WINDOWS,
            'clinics' => $this->clinicRows($period, $reference),
        ];
    }

    /** Clinic key of a visit row: the clinic id, or 'none' for no / unknown clinic. */
    private static function klinikKey($klinikId): int|string
    {
        return Clinics::find($klinikId)->id ?? 'none';
    }

    /** Per clinic: patients examined there in the period, and those who had been examined there before. */
    private function periodByKlinik(Carbon $start, Carbon $end): array
    {
        $period = DB::table('erm_visitations')
            ->where('status_kunjungan', 2)
            ->whereNotNull('pasien_id')
            ->whereBetween('tanggal_visitation', [$start->toDateString(), $end->toDateString()])
            ->groupBy('pasien_id', 'klinik_id')
            ->get(['pasien_id', 'klinik_id']);

        $before = $period->isEmpty() ? collect() : DB::table('erm_visitations')
            ->where('status_kunjungan', 2)
            ->whereIn('pasien_id', $period->pluck('pasien_id')->unique())
            ->where('tanggal_visitation', '<', $start->toDateString())
            ->groupBy('pasien_id', 'klinik_id')
            ->get(['pasien_id', 'klinik_id'])
            ->mapWithKeys(fn ($r) => [$r->pasien_id . '|' . self::klinikKey($r->klinik_id) => true]);

        $stats = [];
        foreach ($period as $row) {
            $key = self::klinikKey($row->klinik_id);
            $stats[$key]['patients'] = ($stats[$key]['patients'] ?? 0) + 1;
            $stats[$key]['returning'] = ($stats[$key]['returning'] ?? 0) + (int) $before->has($row->pasien_id . '|' . $key);
        }

        return $stats;
    }

    /** Per clinic, depending only on $ref: 12-month visit frequency, patients at risk, and the new-patient cohorts. */
    private function referenceByKlinik(Carbon $ref): array
    {
        $yearAgo = $ref->copy()->subYear();
        $stats = [];

        DB::table('erm_visitations')
            ->where('status_kunjungan', 2)
            ->whereBetween('tanggal_visitation', [$yearAgo->toDateString(), $ref->toDateString()])
            ->groupBy('klinik_id')
            ->selectRaw('klinik_id, COUNT(*) as visits, COUNT(DISTINCT pasien_id) as patients')
            ->get()
            ->each(function ($r) use (&$stats) {
                $key = self::klinikKey($r->klinik_id);
                $stats[$key]['visits'] = ($stats[$key]['visits'] ?? 0) + (int) $r->visits;
                $stats[$key]['patients12'] = ($stats[$key]['patients12'] ?? 0) + (int) $r->patients;
            });

        // Last visit to the clinic 6–12 months ago
        DB::table('erm_visitations')
            ->where('status_kunjungan', 2)
            ->whereNotNull('pasien_id')
            ->groupBy('pasien_id', 'klinik_id')
            ->havingRaw('MAX(tanggal_visitation) BETWEEN ? AND ?', [$yearAgo->toDateString(), $ref->copy()->subMonths(6)->toDateString()])
            ->get(['klinik_id'])
            ->each(function ($r) use (&$stats) {
                $key = self::klinikKey($r->klinik_id);
                $stats[$key]['atRisk'] = ($stats[$key]['atRisk'] ?? 0) + 1;
            });

        foreach ($this->cohortsByKlinik($ref) as $key => $cohorts) {
            $stats[$key]['cohorts'] = $cohorts;
        }

        return $stats;
    }

    /** Per clinic: its new patients (first visit there) of each cohort month and how many came back there in time. */
    private function cohortsByKlinik(Carbon $ref): array
    {
        $firstMonth = $ref->copy()->startOfMonth()->subMonths(self::COHORT_MONTHS - 1);

        $firstVisits = DB::table('erm_visitations')
            ->whereNotNull('pasien_id')
            ->where('status_kunjungan', 2)
            ->groupBy('pasien_id', 'klinik_id')
            ->selectRaw('pasien_id, klinik_id, MIN(tanggal_visitation) as first_visit');

        $patients = DB::query()
            ->fromSub($firstVisits, 'fv')
            ->leftJoin('erm_visitations as v', function ($join) {
                $join->on('v.pasien_id', '=', 'fv.pasien_id')
                    ->whereRaw('v.klinik_id <=> fv.klinik_id')
                    ->where('v.status_kunjungan', 2)
                    ->whereColumn('v.tanggal_visitation', '>', 'fv.first_visit');
            })
            ->whereBetween('fv.first_visit', [$firstMonth->toDateString(), $ref->toDateString()])
            ->groupBy('fv.pasien_id', 'fv.klinik_id', 'fv.first_visit')
            ->selectRaw('fv.klinik_id, fv.first_visit, MIN(v.tanggal_visitation) as next_visit')
            ->get()
            ->groupBy(fn ($p) => self::klinikKey($p->klinik_id));

        return $patients->map(fn ($rows) => $this->cohorts($rows, $firstMonth, $ref))->all();
    }

    /** Cohort rows (newest month first) of one clinic's new patients. */
    private function cohorts($patients, Carbon $firstMonth, Carbon $ref): array
    {
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

    /** One entry per clinic (every clinic, plus "Tanpa klinik" when such visits exist). */
    private function clinicRows(array $period, array $reference): array
    {
        $keys = collect(array_keys($period + $reference))->map(fn ($key) => (object) ['klinik_id' => $key === 'none' ? null : $key]);

        return Clinics::sections($keys, function ($rows, $klinik) use ($period, $reference) {
            $p = $period[$klinik->id ?? 'none'] ?? [];
            $r = $reference[$klinik->id ?? 'none'] ?? [];
            $patients = $p['patients'] ?? 0;
            $cohorts = $r['cohorts'] ?? [];

            // Weighted 90-day retention over the cohorts old enough to measure it
            $mature = collect($cohorts)->filter(fn ($c) => $c['cells'][90] !== null);
            $retention90 = $mature->sum('size') > 0
                ? $mature->sum(fn ($c) => $c['cells'][90] * $c['size']) / $mature->sum('size')
                : null;

            $returning = $p['returning'] ?? 0;
            $returningPct = Fmt::share($returning, $patients);

            return [
                // Period patients split in two that make 100%: back again, or new to this clinic
                'periodPatients' => $patients,
                'returning' => $returning,
                'returningPct' => $returningPct,
                'newPatients' => $patients - $returning,
                'newPct' => $patients > 0 ? 100 - $returningPct : 0,
                'retention90' => $retention90,
                'frequency' => !empty($r['patients12']) ? $r['visits'] / $r['patients12'] : 0,
                'atRisk' => $r['atRisk'] ?? 0,
                'cohorts' => $cohorts,
            ];
        });
    }
}
