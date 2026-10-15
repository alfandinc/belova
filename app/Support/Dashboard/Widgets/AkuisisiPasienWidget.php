<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\User;
use App\Support\Dashboard\Clinics;
use App\Support\Dashboard\Fmt;
use App\Support\Dashboard\Revenue;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * New patients = patients whose first examined visit (status_kunjungan 2) falls in the period.
 * Shows growth vs the previous period, where they came from (erm_pasiens.referral_type) with the omset
 * they brought, new vs returning spend, and new patients per month over the last 12 months.
 */
class AkuisisiPasienWidget implements Widget
{
    public const SOURCES = [
        'walk_in' => 'Datang langsung',
        'dokter' => 'Rujukan dokter',
        'pasien' => 'Rekomendasi pasien',
        'employee' => 'Rekomendasi karyawan',
        'event' => 'Event marketing',
        'marketplace' => 'Marketplace',
        'partnership' => 'Partnership',
        'follow_up' => 'Follow up',
    ];

    public function data(User $user, array $filter): array
    {
        $start = $filter['period_start']->copy()->startOfDay();
        $end = $filter['period_end']->copy()->endOfDay();

        // First-visit lookups scan the whole visit history; the result is the same for everyone, so keep it 10 minutes
        return Cache::remember('dashboard:akuisisi:v3:' . $start->toDateString() . ':' . $end->toDateString(), 600, fn () => $this->compute($start, $end));
    }

    private function compute(Carbon $start, Carbon $end): array
    {
        $days = (int) $start->diffInDays($end->copy()->startOfDay()) + 1;
        $prevEnd = $start->copy()->subDay()->endOfDay();
        $prevStart = $prevEnd->copy()->subDays($days - 1)->startOfDay();

        $newIn = fn (Carbon $from, Carbon $to) => DB::query()
            ->fromSub($this->firstVisits(), 'fv')
            ->whereBetween('fv.first_visit', [$from->toDateString(), $to->toDateString()]);

        $newCount = $newIn($start, $end)->count();

        // Clinic of the first visit (a patient seen at two clinics that day counts at both)
        $newByKlinik = fn (Carbon $from, Carbon $to) => $newIn($from, $to)
            ->join('erm_visitations as v', fn ($join) => $join->on('v.pasien_id', '=', 'fv.pasien_id')->on('v.tanggal_visitation', '=', 'fv.first_visit'))
            ->where('v.status_kunjungan', 2)
            ->groupBy('v.klinik_id')
            ->selectRaw('v.klinik_id, COUNT(DISTINCT fv.pasien_id) as total')
            ->get();
        $prevByKlinik = $newByKlinik($prevStart, $prevEnd)
            ->groupBy(fn ($r) => Clinics::find($r->klinik_id)->id ?? 'none')
            ->map(fn ($rows) => (int) $rows->sum('total'));
        $prevCount = $newIn($prevStart, $prevEnd)->count();

        // Omset per patient in the period, split into new (first visit in period) and returning
        $spend = Revenue::between($start, $end)
            ->join('erm_visitations as v', 'v.id', '=', 'fi.visitation_id')
            ->joinSub($this->firstVisits(), 'fv', 'fv.pasien_id', '=', 'v.pasien_id')
            ->selectRaw('v.pasien_id, SUM(fi.total_amount) as total, MAX(fv.first_visit >= ?) as is_new', [$start->toDateString()])
            ->groupBy('v.pasien_id')
            ->get();
        $newSpend = $spend->where('is_new', 1);
        $oldSpend = $spend->where('is_new', 0);

        $sourceOmset = $spend->where('is_new', 1)->pluck('total', 'pasien_id');
        $sources = $newIn($start, $end)
            ->join('erm_pasiens as p', 'p.id', '=', 'fv.pasien_id')
            ->selectRaw("COALESCE(NULLIF(p.referral_type, ''), 'walk_in') as source, fv.pasien_id")
            ->get()
            ->groupBy('source')
            ->map(fn ($rows, $source) => [
                'label' => self::SOURCES[$source] ?? ucwords(str_replace('_', ' ', $source)),
                'count' => $rows->count(),
                'pct' => Fmt::share($rows->count(), $newCount),
                'omset' => (float) $rows->sum(fn ($r) => $sourceOmset[$r->pasien_id] ?? 0),
            ])
            ->sortByDesc('count')
            ->values();

        return [
            'periodLabel' => $start->translatedFormat('d M Y') . ' – ' . $end->translatedFormat('d M Y'),
            'previousLabel' => $prevStart->translatedFormat('d M') . ' – ' . $prevEnd->translatedFormat('d M Y'),
            'newCount' => $newCount,
            'clinics' => Clinics::sections($newByKlinik($start, $end), function ($rows, $klinik) use ($prevByKlinik) {
                $count = (int) $rows->sum('total');

                return ['count' => $count, 'trend' => Fmt::trend($count, $prevByKlinik->get($klinik->id ?? 'none', 0))];
            }),
            'trend' => Fmt::trend($newCount, $prevCount),
            'perDay' => $newCount / max(1, $days),
            'referredPct' => Fmt::share($sources->where('label', '!=', self::SOURCES['walk_in'])->sum('count'), $newCount),
            'newAvg' => $newSpend->count() ? $newSpend->sum('total') / $newSpend->count() : 0,
            'oldAvg' => $oldSpend->count() ? $oldSpend->sum('total') / $oldSpend->count() : 0,
            'newOmsetPct' => Fmt::share($newSpend->sum('total'), $spend->sum('total')),
            'sources' => $sources->all(),
            'chart' => $this->monthly($end),
        ];
    }

    /** pasien_id => date of the first examined visit, over all clinics. */
    private function firstVisits()
    {
        return DB::table('erm_visitations')
            ->whereNotNull('pasien_id')
            ->where('status_kunjungan', 2)
            ->groupBy('pasien_id')
            ->selectRaw('pasien_id, MIN(tanggal_visitation) as first_visit');
    }

    private function monthly(Carbon $end): array
    {
        $from = $end->copy()->startOfMonth()->subMonths(11);

        $counts = DB::query()
            ->fromSub($this->firstVisits(), 'fv')
            ->whereBetween('fv.first_visit', [$from->toDateString(), $end->toDateString()])
            ->selectRaw("DATE_FORMAT(fv.first_visit, '%Y-%m') as month, COUNT(*) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        $categories = $values = [];
        foreach (CarbonPeriod::create($from, '1 month', $end->copy()->startOfMonth()) as $month) {
            $categories[] = $month->translatedFormat('M y');
            $values[] = (int) ($counts[$month->format('Y-m')] ?? 0);
        }

        return [
            'type' => 'bar',
            'format' => 'number',
            'height' => 190,
            'categories' => $categories,
            'series' => [['name' => 'Pasien baru', 'values' => $values, 'color' => '--dw-series-1']],
        ];
    }
}
