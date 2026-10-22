<?php

namespace App\Support\Marketing;

use App\Http\Controllers\Marketing\ContentPlanController;
use App\Models\Marketing\ContentInsight;
use App\Models\Marketing\ContentPlan;
use Carbon\Carbon;

/**
 * Best time to post: views in the first 3 days after going live (fair between old and new content),
 * averaged per time of day and per weekday over the published content of the last 90 days.
 * Go-live time = the time recorded in "Tandai published", otherwise the scheduled time.
 */
class PostingTimeAnalytics
{
    public const LOOKBACK_DAYS = 90;

    /** Views counted up to this many days after going live. */
    public const AFTER_DAYS = 3;

    /** A slot needs at least this many posts before it can be called "best". */
    public const MIN_POSTS = 2;

    /** [label, from hour, to hour (exclusive)] */
    public const SLOTS = [
        ['00.00–06.00', 0, 6],
        ['06.00–09.00', 6, 9],
        ['09.00–12.00', 9, 12],
        ['12.00–15.00', 12, 15],
        ['15.00–18.00', 15, 18],
        ['18.00–21.00', 18, 21],
        ['21.00–24.00', 21, 24],
    ];

    private const HARI = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    public static function build(array $filters = []): array
    {
        $samples = self::samples($filters);
        $overall = $samples ? array_sum(array_column($samples, 'views')) / count($samples) : null;

        $slots = [];
        foreach (self::SLOTS as $i => [$label, $from, $to]) {
            $in = array_filter($samples, fn ($s) => $s['hour'] >= $from && $s['hour'] < $to);
            $slots[] = self::group($label, $in, $overall);
        }
        $days = [];
        foreach (self::HARI as $n => $label) {
            $days[] = self::group($label, array_filter($samples, fn ($s) => $s['dow'] === $n), $overall);
        }

        return [
            'samples' => count($samples),
            'overall_avg' => $overall !== null ? (int) round($overall) : null,
            'slots' => $slots,
            'days' => $days,
            'best_slot' => self::best($slots),
            'best_day' => self::best($days),
            'lookback_days' => self::LOOKBACK_DAYS,
            'after_days' => self::AFTER_DAYS,
            'min_posts' => self::MIN_POSTS,
        ];
    }

    /** One short sentence for the schedule form ("Jam tayang terbaik … "), or null without enough data. */
    public static function hint(array $filters = []): ?array
    {
        $r = self::build($filters);
        if (!$r['best_slot']) {
            return null;
        }

        return [
            'slot' => $r['best_slot']['label'],
            'avg' => $r['best_slot']['avg'],
            'count' => $r['best_slot']['count'],
            'ratio' => $r['best_slot']['ratio'],
            'day' => $r['best_day']['label'] ?? null,
            'samples' => $r['samples'],
        ];
    }

    /** Published content of the lookback window with its views after AFTER_DAYS days. */
    private static function samples(array $filters): array
    {
        if (!InstagramInsights::enabled()) {
            return [];
        }
        $query = ContentPlan::where('status', 'Published')
            ->where('tanggal_publish', '>=', now()->subDays(self::LOOKBACK_DAYS)->startOfDay());
        if (!empty($filters['brand'])) {
            $query->whereJsonContains('brand', $filters['brand']);
        }
        if (!empty($filters['jenis'])) {
            $query->whereJsonContains('jenis_konten', $filters['jenis']);
        }
        if (!empty($filters['pilar'])) {
            $query->where('konten_pilar', $filters['pilar']);
        }
        $plans = $query->get(['id', 'tanggal_publish'])->keyBy('id');
        if ($plans->isEmpty()) {
            return [];
        }
        $published = ContentPlanController::publishInfo($plans->keys()->all());
        $series = ContentInsight::whereIn('content_plan_id', $plans->keys())->where('platform', 'instagram')
            ->whereNotNull('views')->orderBy('tanggal')->get()->groupBy('content_plan_id');

        $out = [];
        foreach ($plans as $id => $plan) {
            $live = isset($published[$id]['at']) ? Carbon::parse($published[$id]['at']) : Carbon::parse($plan->tanggal_publish);
            $target = $live->copy()->startOfDay()->addDays(self::AFTER_DAYS);
            // the snapshot nearest to "3 days after", at most one day off
            $row = $series->get($id, collect())
                ->filter(fn ($r) => abs((int) $r->tanggal->diffInDays($target, false)) <= 1)
                ->sortBy(fn ($r) => abs((int) $r->tanggal->diffInDays($target, false)))
                ->first();
            if (!$row) {
                continue;
            }
            $out[] = ['views' => (int) $row->views, 'hour' => (int) $live->format('G'), 'dow' => $live->dayOfWeekIso];
        }

        return $out;
    }

    private static function group(string $label, array $in, ?float $overall): array
    {
        $count = count($in);
        $avg = $count ? array_sum(array_column($in, 'views')) / $count : null;

        return [
            'label' => $label,
            'count' => $count,
            'avg' => $avg !== null ? (int) round($avg) : null,
            // 1.8 = 80 % more views than the average post
            'ratio' => $avg !== null && $overall ? round($avg / $overall, 2) : null,
        ];
    }

    private static function best(array $groups): ?array
    {
        $ok = array_filter($groups, fn ($g) => $g['count'] >= self::MIN_POSTS);
        if (count($ok) < 2) {
            return null; // nothing to compare with yet
        }
        usort($ok, fn ($a, $b) => $b['avg'] <=> $a['avg']);

        return $ok[0];
    }
}
