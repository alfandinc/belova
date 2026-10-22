<?php

namespace App\Support\Marketing;

use App\Http\Controllers\Marketing\ContentPlanController;
use App\Models\Marketing\ContentInsight;
use App\Models\Marketing\ContentPlan;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * "Analisa Konten": numbers of a period from the daily Instagram snapshots (marketing_content_insights),
 * compared with the period just before it.
 *
 * - Views/interaksi of a period = growth inside the period (snapshot at the end minus the snapshot before
 *   the start; content published inside the period starts at 0).
 * - Engagement rate (ER) = (likes + comments + saves + shares) / reach × 100, on the latest snapshot.
 */
class ContentAnalytics
{
    /** ER of posts with a tiny reach says little: they are left out of the ER ranking and averages. */
    public const MIN_REACH_FOR_ER = 50;

    private const INTERACTIONS = ['likes', 'comments', 'saves', 'shares'];

    public static function build(Carbon $start, Carbon $end, array $filters = []): array
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->startOfDay();
        $days = (int) round(abs($start->diffInDays($end))) + 1;
        $prevEnd = $start->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($days - 1);

        $plans = self::plans($filters, $end);
        $series = ContentInsight::whereIn('content_plan_id', $plans->keys())->where('platform', 'instagram')
            ->whereDate('tanggal', '<=', $end)->orderBy('tanggal')->get()->groupBy('content_plan_id');
        $published = ContentPlanController::publishInfo($plans->keys()->all());

        $current = self::periodTotals($plans, $series, $published, $start, $end);
        $previous = self::periodTotals($plans, $series, $published, $prevStart, $prevEnd);

        $items = [];
        foreach ($plans as $id => $plan) {
            $rows = $series->get($id, collect());
            $inRange = $plan->tanggal_publish && $plan->tanggal_publish->between($start, $end->copy()->endOfDay());
            $last = $rows->last(fn ($r) => $r->tanggal->lte($end));
            $gain = self::gain($rows, $plan, $start, $end, 'views');
            if (!$last || (!$inRange && !$gain)) {
                continue;
            }
            $items[] = [
                'id' => $plan->id,
                'judul' => $plan->judul,
                'brand' => array_values((array) ($plan->brand ?? [])),
                'pilar' => $plan->konten_pilar,
                'jenis' => array_values((array) ($plan->jenis_konten ?? [])),
                'tanggal_publish' => optional($plan->tanggal_publish)->toIso8601String(),
                'published_in_range' => $inRange,
                'snapshot' => $last->tanggal->toDateString(),
                'views' => $last->views,
                'reach' => $last->reach,
                'likes' => $last->likes,
                'comments' => $last->comments,
                'saves' => $last->saves,
                'shares' => $last->shares,
                'er' => self::er($last),
                'views_gain' => $gain,
                'on_time' => $published[$id]['on_time'] ?? null,
                // views per snapshot inside the period (sparkline)
                'spark' => $rows->filter(fn ($r) => $r->tanggal->between($start, $end) && $r->views !== null)
                    ->map(fn ($r) => (int) $r->views)->values()->take(-31),
            ];
        }

        return [
            'period' => ['start' => $start->toDateString(), 'end' => $end->toDateString(), 'days' => $days,
                'prev_start' => $prevStart->toDateString(), 'prev_end' => $prevEnd->toDateString()],
            'kpi' => self::kpis($current, $previous),
            'trend' => self::trend($plans, $series, $start, $end),
            'items' => $items,
            'breakdown' => [
                'pilar' => self::breakdown($items, fn ($i) => [$i['pilar'] ?: 'Tanpa pilar']),
                'jenis' => self::breakdown($items, fn ($i) => $i['jenis'] ?: ['Lainnya']),
                'brand' => self::breakdown($items, fn ($i) => $i['brand'] ?: ['Tanpa klinik']),
            ],
            'posting_time' => PostingTimeAnalytics::build($filters),
            'team' => TeamAnalytics::build($start, $end, $prevStart, $prevEnd),
            'followers' => FollowerAnalytics::overview($start, $end, $prevStart, !empty($filters['account']) ? (int) $filters['account'] : null),
            'last_update' => optional(ContentInsight::max('fetched_at') ? Carbon::parse(ContentInsight::max('fetched_at')) : null)->toIso8601String(),
            'tracked' => $series->count(),
        ];
    }

    /** Published content that matches the filters (keyed by id). */
    private static function plans(array $filters, Carbon $end): Collection
    {
        $query = ContentPlan::where('status', 'Published')->where('tanggal_publish', '<=', $end->copy()->endOfDay());
        if (!empty($filters['brand'])) {
            $query->whereJsonContains('brand', $filters['brand']);
        }
        if (!empty($filters['jenis'])) {
            $query->whereJsonContains('jenis_konten', $filters['jenis']);
        }
        if (!empty($filters['pilar'])) {
            $query->where('konten_pilar', $filters['pilar']);
        }

        return $query->get(['id', 'judul', 'brand', 'konten_pilar', 'jenis_konten', 'tanggal_publish'])->keyBy('id');
    }

    /** Last known value of a metric on or before a day. */
    private static function valueAt(Collection $rows, Carbon $day, string $metric): ?int
    {
        $row = $rows->last(fn ($r) => $r->tanggal->lte($day) && $r->{$metric} !== null);

        return $row ? (int) $row->{$metric} : null;
    }

    /**
     * Growth of a metric between two days. Content published inside the period starts at 0; content
     * tracked only since inside the period starts at its first snapshot there.
     */
    private static function gain(Collection $rows, ContentPlan $plan, Carbon $start, Carbon $end, string $metric): ?int
    {
        $endVal = self::valueAt($rows, $end, $metric);
        if ($endVal === null) {
            return null;
        }
        $base = self::valueAt($rows, $start->copy()->subDay(), $metric);
        if ($base === null) {
            if ($plan->tanggal_publish && $plan->tanggal_publish->gte($start)) {
                $base = 0;
            } else {
                $first = $rows->first(fn ($r) => $r->tanggal->gte($start) && $r->{$metric} !== null);
                $base = $first ? (int) $first->{$metric} : $endVal;
            }
        }

        return max(0, $endVal - $base);
    }

    private static function er($row): ?float
    {
        $reach = (int) ($row->reach ?? 0);
        if ($reach < 1) {
            return null;
        }
        $sum = array_sum(array_map(fn ($m) => (int) ($row->{$m} ?? 0), self::INTERACTIONS));

        return round($sum / $reach * 100, 2);
    }

    private static function periodTotals(Collection $plans, Collection $series, array $published, Carbon $start, Carbon $end): array
    {
        $views = 0;
        $interactions = 0;
        $publishedCount = 0;
        $viewsOfPublished = [];
        $ers = [];
        $onTime = [0, 0];
        foreach ($plans as $id => $plan) {
            $rows = $series->get($id, collect());
            $views += self::gain($rows, $plan, $start, $end, 'views') ?? 0;
            foreach (self::INTERACTIONS as $m) {
                $interactions += self::gain($rows, $plan, $start, $end, $m) ?? 0;
            }
            if ($plan->tanggal_publish && $plan->tanggal_publish->between($start, $end->copy()->endOfDay())) {
                $publishedCount++;
                if (($v = self::valueAt($rows, $end, 'views')) !== null) {
                    $viewsOfPublished[] = $v;
                }
                $last = $rows->last(fn ($r) => $r->tanggal->lte($end));
                if ($last && (int) $last->reach >= self::MIN_REACH_FOR_ER && ($er = self::er($last)) !== null) {
                    $ers[] = $er;
                }
                if (isset($published[$id]['on_time'])) {
                    $onTime[0] += $published[$id]['on_time'] ? 1 : 0;
                    $onTime[1]++;
                }
            }
        }

        return [
            'views' => $views,
            'interactions' => $interactions,
            'published' => $publishedCount,
            'avg_views' => $viewsOfPublished ? (int) round(array_sum($viewsOfPublished) / count($viewsOfPublished)) : null,
            'avg_er' => $ers ? round(array_sum($ers) / count($ers), 2) : null,
            'on_time' => $onTime[1] ? (int) round($onTime[0] / $onTime[1] * 100) : null,
        ];
    }

    private static function kpis(array $cur, array $prev): array
    {
        $out = [];
        foreach ($cur as $key => $value) {
            $before = $prev[$key];
            $change = null;
            if ($value !== null && $before !== null) {
                // percentages (ER, tepat waktu) change in points, counts in percent
                $change = in_array($key, ['avg_er', 'on_time'], true)
                    ? round($value - $before, 2)
                    : ($before > 0 ? round(($value - $before) / $before * 100, 1) : null);
            }
            $out[$key] = ['value' => $value, 'previous' => $before, 'change' => $change];
        }

        return $out;
    }

    /** Views gained per day over all content (from consecutive snapshots). */
    private static function trend(Collection $plans, Collection $series, Carbon $start, Carbon $end): array
    {
        $byDay = [];
        foreach (CarbonPeriod::create($start, $end) as $d) {
            $byDay[$d->toDateString()] = ['date' => $d->toDateString(), 'views' => 0, 'tracked' => false];
        }
        foreach ($series as $planId => $rows) {
            $plan = $plans->get($planId);
            $prev = null;
            foreach ($rows as $row) {
                $key = $row->tanggal->toDateString();
                if ($row->views !== null && isset($byDay[$key])) {
                    if ($prev !== null) {
                        $byDay[$key]['views'] += max(0, $row->views - $prev);
                    } elseif ($plan && $plan->tanggal_publish && $plan->tanggal_publish->diffInDays($row->tanggal, false) <= 3) {
                        // first snapshot soon after publishing: everything so far is new
                        $byDay[$key]['views'] += (int) $row->views;
                    }
                    $byDay[$key]['tracked'] = true;
                }
                if ($row->views !== null) {
                    $prev = (int) $row->views;
                }
            }
        }

        return array_values($byDay);
    }

    /** Count, average views and average ER per group (content published in the period). */
    private static function breakdown(array $items, callable $groups): array
    {
        $acc = [];
        foreach ($items as $item) {
            if (!$item['published_in_range']) {
                continue;
            }
            foreach ($groups($item) as $group) {
                $acc[$group] ??= ['label' => $group, 'count' => 0, 'views' => [], 'er' => []];
                $acc[$group]['count']++;
                if ($item['views'] !== null) {
                    $acc[$group]['views'][] = $item['views'];
                }
                if ($item['er'] !== null && (int) $item['reach'] >= self::MIN_REACH_FOR_ER) {
                    $acc[$group]['er'][] = $item['er'];
                }
            }
        }

        return collect($acc)->map(fn ($g) => [
            'label' => $g['label'],
            'count' => $g['count'],
            'avg_views' => $g['views'] ? (int) round(array_sum($g['views']) / count($g['views'])) : null,
            'avg_er' => $g['er'] ? round(array_sum($g['er']) / count($g['er']), 2) : null,
        ])->sortByDesc('avg_views')->values()->all();
    }
}
