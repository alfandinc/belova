<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\ERM\Pasien;
use App\Models\User;
use App\Support\Dashboard\Clinics;
use App\Support\Dashboard\Fmt;
use App\Support\Dashboard\Revenue;
use App\Support\Marketing\MarketingReferrals;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Omset of invoices whose visit referral counts as Marketing & Sales (Admin Panel > Referral Marketing,
 * App\Support\Marketing\MarketingReferrals): total, invoices and share of all omset with growth vs the
 * previous period, one section per clinic, and the omset per referral source. Net of returs (Revenue).
 */
class OmsetMarketingWidget implements Widget
{
    private const MAX_SOURCES = 8;

    public function data(User $user, array $filter): array
    {
        $start = $filter['period_start']->copy()->startOfDay();
        $end = $filter['period_end']->copy()->endOfDay();
        $rulesHash = md5(MarketingReferrals::rules()->toJson());

        return Cache::remember('dashboard:omset-marketing:v1:' . $rulesHash . ':' . $start->toDateString() . ':' . $end->toDateString(), 600,
            fn () => $this->compute($start, $end));
    }

    private function compute(Carbon $start, Carbon $end): array
    {
        $days = (int) $start->diffInDays($end->copy()->startOfDay()) + 1;
        $prevEnd = $start->copy()->subDay()->endOfDay();
        $prevStart = $prevEnd->copy()->subDays($days - 1)->startOfDay();

        $base = [
            'periodLabel' => $start->translatedFormat('d M Y') . ' – ' . $end->translatedFormat('d M Y'),
            'previousLabel' => $prevStart->translatedFormat('d M') . ' – ' . $prevEnd->translatedFormat('d M Y'),
            'mapped' => MarketingReferrals::hasAnyMarketing(),
            'mappingUrl' => route('admin.referral_mapping.index'),
        ];
        if (!$base['mapped']) {
            return $base;
        }

        $rows = $this->marketingRows($start, $end);
        $prevRows = $this->marketingRows($prevStart, $prevEnd);
        $omsetByKlinik = Revenue::byKlinik($start, $end);
        $omsetTotal = (float) $omsetByKlinik->sum();

        $total = (float) $rows->sum('omset');
        $prevTotal = (float) $prevRows->sum('omset');
        $prevByKlinik = $prevRows->groupBy(fn ($r) => Clinics::find($r->klinik_id)->id ?? 'none')->map(fn ($g) => (float) $g->sum('omset'));

        $clinics = Clinics::sections($rows, function (Collection $group, $klinik) use ($omsetByKlinik, $prevByKlinik) {
            $omset = (float) $group->sum('omset');
            $klinikOmset = (float) ($klinik->id ? ($omsetByKlinik[$klinik->id] ?? 0) : ($omsetByKlinik[''] ?? 0));

            return [
                'omset' => $omset,
                'invoices' => (int) $group->sum('invoices'),
                'share' => Fmt::share($omset, $klinikOmset),
                'trend' => Fmt::trend($omset, (float) ($prevByKlinik[$klinik->id ?? 'none'] ?? 0)),
            ];
        });

        return $base + [
            'total' => $total,
            'invoices' => (int) $rows->sum('invoices'),
            'share' => Fmt::share($total, $omsetTotal),
            'trend' => Fmt::trend($total, $prevTotal),
            'clinics' => $clinics,
            'sources' => $this->sources($rows, $total),
        ];
    }

    /**
     * Marketing rows of the period: (object) [klinik_id, type, ref_key, omset (net of returs), invoices].
     * Grouped by referral in SQL, filtered by the mapping in PHP (few groups).
     */
    private function marketingRows(Carbon $from, Carbon $to): Collection
    {
        $select = 'v.klinik_id, ' . MarketingReferrals::typeSql() . ' as type, ' . MarketingReferrals::keySql() . ' as ref_key';
        $withReferral = fn ($query) => $query
            ->join('erm_visitations as v', 'v.id', '=', 'fi.visitation_id')
            ->leftJoin('erm_pasiens as p', 'p.id', '=', 'v.pasien_id')
            ->groupBy('v.klinik_id', 'type', 'ref_key');

        $gross = $withReferral(Revenue::between($from, $to))
            ->selectRaw($select . ', SUM(fi.total_amount) as total, COUNT(DISTINCT fi.id) as invoices')
            ->get();
        $retur = $withReferral(Revenue::returBetween($from, $to))
            ->selectRaw($select . ', SUM(r.total_amount) as total')
            ->get()
            ->keyBy(fn ($r) => $r->klinik_id . '|' . $r->type . '|' . $r->ref_key);

        $groupKey = fn ($r) => $r->klinik_id . '|' . $r->type . '|' . $r->ref_key;

        return $gross->keyBy($groupKey)
            ->union($retur->map(fn ($r) => (object) ['klinik_id' => $r->klinik_id, 'type' => $r->type, 'ref_key' => $r->ref_key, 'total' => 0, 'invoices' => 0]))
            ->filter(fn ($r) => MarketingReferrals::isMarketing($r->type, (string) $r->ref_key))
            ->map(function ($r, $key) use ($retur) {
                return (object) [
                    'klinik_id' => $r->klinik_id,
                    'type' => $r->type,
                    'ref_key' => (string) $r->ref_key,
                    'omset' => (float) $r->total - (float) ($retur[$key]->total ?? 0),
                    'invoices' => (int) $r->invoices,
                ];
            })
            ->values();
    }

    /** Omset per referral source ("Event · Grand Opening", "Social Media · Instagram"), biggest first, rest as "Lainnya". */
    private function sources(Collection $rows, float $total): array
    {
        $detailed = fn ($r) => in_array($r->type, MarketingReferrals::DETAIL_TYPES, true) && $r->ref_key !== '';
        $labels = MarketingReferrals::detailLabels($rows->filter($detailed)->map(fn ($r) => [$r->type, $r->ref_key]));

        $sources = $rows
            ->groupBy(fn ($r) => $r->type . '|' . ($detailed($r) ? $r->ref_key : ''))
            ->map(function ($group) use ($detailed, $labels) {
                $r = $group->first();
                $label = Pasien::referralTypeLabel($r->type);
                if ($detailed($r)) {
                    $label .= ' · ' . ($labels[$r->type][$r->ref_key] ?? $r->ref_key);
                }

                return ['label' => $label, 'omset' => (float) $group->sum('omset'), 'invoices' => (int) $group->sum('invoices')];
            })
            ->sortByDesc('omset')
            ->values();

        if ($sources->count() > self::MAX_SOURCES) {
            $rest = $sources->slice(self::MAX_SOURCES - 1);
            $sources = $sources->take(self::MAX_SOURCES - 1)->push([
                'label' => 'Lainnya (' . $rest->count() . ' sumber)',
                'omset' => (float) $rest->sum('omset'),
                'invoices' => (int) $rest->sum('invoices'),
            ]);
        }

        return $sources->map(fn ($s) => $s + ['pct' => Fmt::share($s['omset'], $total)])->all();
    }
}
