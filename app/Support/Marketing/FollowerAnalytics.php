<?php

namespace App\Support\Marketing;

use App\Models\Marketing\ContentInsight;
use App\Models\Marketing\ContentPlan;
use App\Models\Marketing\SocialAccount;
use App\Models\Marketing\SocialAccountStat;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Followers of the connected Instagram accounts for "Analisa Konten": totals per day, new followers,
 * unfollows, account reach and demographics, for one account or all together, compared with the previous
 * period. Days where the total was not read are estimated backwards from new followers − unfollows.
 */
class FollowerAnalytics
{
    /**
     * One card per clinic (brand of the Content Plan, incl. BCL): its connected Instagram account and a
     * short summary, plus accounts not assigned to a clinic yet; and the details of the selected account
     * (or all accounts together).
     */
    public static function overview(Carbon $start, Carbon $end, Carbon $prevStart, ?int $accountId): ?array
    {
        if (!InstagramAccountStats::enabled()) {
            return null;
        }
        $accounts = SocialAccount::where('platform', 'instagram')->orderBy('username')->get();
        $brands = \App\Http\Controllers\Marketing\ContentPlanController::brandOptions();
        $brandValues = array_column($brands, 'value');

        $summary = function (SocialAccount $a) use ($start, $end, $prevStart) {
            $d = self::build($start, $end, $prevStart, $a->id);

            return [
                'id' => $a->id,
                'username' => $a->username,
                'followers' => $d['kpi']['followers']['value'] ?? null,
                'growth' => $d['kpi']['growth']['value'] ?? null,
                'change' => $d['kpi']['followers']['change'] ?? null,
                'spark' => collect($d['series'] ?? [])->pluck('followers')->filter(fn ($v) => $v !== null)->values(),
                'last_error' => $a->last_error,
            ];
        };

        $clinics = [];
        foreach ($brands as $b) {
            $clinicAccounts = $accounts->where('brand', $b['value'])->values();
            $clinics[] = [
                'brand' => $b['value'],
                'label' => $b['label'],
                'logo' => $b['logo'],
                'logo_bg' => $b['logo_bg'],
                'color' => $b['color'],
                'accounts' => $clinicAccounts->map($summary)->values(),
            ];
        }
        // connected but no (known) clinic chosen yet
        $loose = $accounts->filter(fn ($a) => !$a->brand || !in_array($a->brand, $brandValues, true))->values();

        return [
            'clinics' => $clinics,
            'unassigned' => $loose->map($summary)->values(),
            'connected' => $accounts->count(),
            'selected' => $accountId,
            'detail' => $accounts->isEmpty() ? null : self::build($start, $end, $prevStart, $accountId),
        ];
    }

    public static function build(Carbon $start, Carbon $end, Carbon $prevStart, ?int $accountId): ?array
    {
        if (!InstagramAccountStats::enabled()) {
            return null;
        }
        $accounts = SocialAccount::where('platform', 'instagram')->when($accountId, fn ($q) => $q->where('id', $accountId))->get();
        if ($accounts->isEmpty()) {
            return null;
        }

        $stats = SocialAccountStat::whereIn('social_account_id', $accounts->pluck('id'))
            ->whereDate('tanggal', '>=', $prevStart->copy()->subDay())->whereDate('tanggal', '<=', $end)
            ->orderBy('tanggal')->get()->groupBy('social_account_id');

        // per account: date => [followers (real or estimated), new, unfollows, reach]
        $perAccount = [];
        foreach ($accounts as $account) {
            $perAccount[$account->id] = self::daily($stats->get($account->id, collect()), $prevStart->copy()->subDay(), $end);
        }

        $days = [];
        foreach (CarbonPeriod::create($prevStart->copy()->subDay(), $end) as $d) {
            $key = $d->toDateString();
            $row = ['date' => $key, 'followers' => 0, 'new' => null, 'unfollows' => null, 'reach' => null, 'estimated' => false];
            foreach ($perAccount as $daily) {
                $v = $daily[$key];
                $row['followers'] = $row['followers'] === null || $v['followers'] === null ? null : $row['followers'] + $v['followers'];
                $row['estimated'] = $row['estimated'] || $v['estimated'];
                foreach (['new', 'unfollows', 'reach'] as $m) {
                    if ($v[$m] !== null) {
                        $row[$m] = ($row[$m] ?? 0) + $v[$m];
                    }
                }
            }
            $days[$key] = $row;
        }

        $prevEnd = $start->copy()->subDay();
        $current = self::totals($days, $start, $end);
        $previous = self::totals($days, $prevStart, $prevEnd);

        // days with content going live on these accounts (to see what brought followers)
        $planIds = ContentInsight::whereIn('social_account_id', $accounts->pluck('id'))->distinct()->pluck('content_plan_id');
        $posted = ContentPlan::whereIn('id', $planIds)->whereBetween('tanggal_publish', [$start, $end->copy()->endOfDay()])
            ->get(['id', 'judul', 'tanggal_publish'])
            ->groupBy(fn ($p) => $p->tanggal_publish->toDateString())
            ->map(fn ($list) => $list->map(fn ($p) => ['id' => $p->id, 'judul' => $p->judul])->values());

        $series = [];
        foreach (CarbonPeriod::create($start, $end) as $d) {
            $row = $days[$d->toDateString()];
            $row['posted'] = $posted->get($d->toDateString(), []);
            $series[] = $row;
        }

        return [
            'accounts' => $accounts->map(fn ($a) => ['id' => $a->id, 'username' => $a->username, 'brand' => $a->brand])->values(),
            'kpi' => self::compare($current, $previous),
            'series' => $series,
            'demographics' => self::demographics($stats),
            'has_daily' => collect($series)->contains(fn ($r) => $r['new'] !== null),
        ];
    }

    /** One account's days from the day before the period start to the end. */
    private static function daily($rows, Carbon $from, Carbon $end): array
    {
        $byDate = $rows->keyBy(fn ($r) => $r->tanggal->toDateString());
        $out = [];
        foreach (CarbonPeriod::create($from, $end) as $d) {
            $r = $byDate->get($d->toDateString());
            $out[$d->toDateString()] = [
                'followers' => $r && $r->followers !== null ? (int) $r->followers : null,
                'new' => $r && $r->new_followers !== null ? (int) $r->new_followers : null,
                'unfollows' => $r && $r->unfollows !== null ? (int) $r->unfollows : null,
                'reach' => $r && $r->reach !== null ? (int) $r->reach : null,
                'estimated' => false,
            ];
        }
        // backwards from each real total: total(d-1) = total(d) − new(d) + unfollows(d); a day without
        // numbers (e.g. today, which Instagram only reports tomorrow) counts as no change
        $keys = array_keys($out);
        for ($i = count($keys) - 1; $i > 0; $i--) {
            $cur = $out[$keys[$i]];
            $prevKey = $keys[$i - 1];
            if ($out[$prevKey]['followers'] === null && $cur['followers'] !== null) {
                $out[$prevKey]['followers'] = max(0, $cur['followers'] - (int) $cur['new'] + (int) $cur['unfollows']);
                $out[$prevKey]['estimated'] = true;
            }
        }
        // forwards over days without a reading (e.g. no update yet today): carry the last total
        $last = null;
        foreach ($keys as $k) {
            if ($out[$k]['followers'] !== null) {
                $last = $out[$k]['followers'];
            } elseif ($last !== null && $k <= Carbon::today()->toDateString()) {
                $out[$k]['followers'] = $last;
                $out[$k]['estimated'] = true;
            }
        }

        return $out;
    }

    private static function totals(array $days, Carbon $start, Carbon $end): array
    {
        $in = array_filter($days, fn ($r) => $r['date'] >= $start->toDateString() && $r['date'] <= $end->toDateString());
        $sum = fn ($m) => array_reduce($in, fn ($c, $r) => $r[$m] === null ? $c : ($c ?? 0) + $r[$m], null);
        $endRow = $days[$end->toDateString()] ?? null;
        $before = $days[$start->copy()->subDay()->toDateString()] ?? null;
        $new = $sum('new');
        $unfollows = $sum('unfollows');

        return [
            'followers' => $endRow['followers'] ?? null,
            'growth' => $endRow && $before && $endRow['followers'] !== null && $before['followers'] !== null ? $endRow['followers'] - $before['followers'] : null,
            'new' => $new,
            'unfollows' => $unfollows,
            'net' => $new !== null ? $new - (int) $unfollows : null,
            'reach' => $sum('reach'),
        ];
    }

    private static function compare(array $cur, array $prev): array
    {
        $out = [];
        foreach ($cur as $k => $v) {
            $p = $prev[$k];
            $out[$k] = [
                'value' => $v,
                'previous' => $p,
                'change' => $v !== null && $p !== null && $p > 0 && $k !== 'followers' ? round(($v - $p) / $p * 100, 1) : null,
            ];
        }
        // total followers: change in percent against the start of the period
        if ($cur['followers'] !== null && $cur['growth'] !== null && $cur['followers'] - $cur['growth'] > 0) {
            $out['followers']['change'] = round($cur['growth'] / ($cur['followers'] - $cur['growth']) * 100, 2);
        }

        return $out;
    }

    /** Latest demographics of each account, added together: {age: {"25-34": n}, gender: {...}, ...}. */
    private static function demographics($stats): ?array
    {
        $out = [];
        $at = null;
        foreach ($stats as $rows) {
            $latest = $rows->filter(fn ($r) => !empty($r->demographics))->last();
            if (!$latest) {
                continue;
            }
            $at = max($at, $latest->tanggal->toDateString());
            foreach ($latest->demographics as $breakdown => $values) {
                foreach ((array) $values as $label => $n) {
                    $out[$breakdown][$label] = ($out[$breakdown][$label] ?? 0) + (int) $n;
                }
            }
        }
        if (!$out) {
            return null;
        }
        foreach ($out as $breakdown => $values) {
            arsort($values);
            $out[$breakdown] = $values;
        }

        return ['as_of' => $at, 'data' => $out];
    }
}
