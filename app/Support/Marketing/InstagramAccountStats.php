<?php

namespace App\Support\Marketing;

use App\Models\Marketing\SocialAccount;
use App\Models\Marketing\SocialAccountStat;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Daily account numbers of a connected Instagram account, taken during "Update insight":
 * total followers (today), new followers per day (Instagram keeps 30 days, so the first run back-fills
 * them), follows/unfollows and reach per day, and the follower demographics (age, gender, city, country).
 * Accounts under 100 followers only get the total: Instagram gives no daily or demographic data then.
 */
class InstagramAccountStats
{
    /** Days Instagram keeps account insights. */
    private const HISTORY_DAYS = 30;

    /**
     * Follows/unfollows and reach need one request per day each: only the last week is filled (a daily
     * update then only asks yesterday), so a run stays a few seconds.
     */
    private const DAILY_LOOKBACK = 7;

    public static function enabled(): bool
    {
        static $enabled = null;

        return $enabled ??= Schema::hasTable('marketing_social_account_stats');
    }

    /** @return string[] problems worth showing (metrics Instagram refused) */
    public static function sync(SocialAccount $account): array
    {
        if (!self::enabled()) {
            return [];
        }
        $token = $account->access_token;
        $notes = [];
        $today = Carbon::today();

        // 1) total followers now
        $me = InstagramInsights::get('/me', ['fields' => 'followers_count,follows_count,media_count'], $token);
        self::put($account, $today, ['followers' => $me['followers_count'] ?? null]);

        // 2) new followers per day, last 30 days (one request)
        try {
            $r = InstagramInsights::get('/me/insights', [
                'metric' => 'follower_count',
                'period' => 'day',
                'since' => $today->copy()->subDays(self::HISTORY_DAYS - 1)->timestamp,
                'until' => $today->timestamp,
            ], $token);
            foreach ($r['data'][0]['values'] ?? [] as $v) {
                if (isset($v['end_time'])) {
                    // end_time is the end of the day the value belongs to
                    self::put($account, Carbon::parse($v['end_time'])->subDay(), ['new_followers' => (int) ($v['value'] ?? 0)]);
                }
            }
        } catch (RuntimeException $e) {
            $notes[] = 'followers baru (' . $e->getMessage() . ')';
        }

        // 3) follows / unfollows and reach per day, for the days not filled yet
        // a day counts as done once either number was stored (a metric Instagram refuses is not retried daily)
        $known = SocialAccountStat::where('social_account_id', $account->id)
            ->whereDate('tanggal', '>=', $today->copy()->subDays(self::DAILY_LOOKBACK))
            ->where(fn ($q) => $q->whereNotNull('unfollows')->orWhereNotNull('reach'))
            ->pluck('tanggal')->map(fn ($d) => Carbon::parse($d)->toDateString())->flip();
        $failed = [];
        for ($i = 1; $i <= self::DAILY_LOOKBACK; $i++) {
            // yesterday and earlier are complete days; today's numbers are still moving
            $day = $today->copy()->subDays($i);
            if (isset($known[$day->toDateString()])) {
                continue;
            }
            $range = ['period' => 'day', 'metric_type' => 'total_value', 'since' => $day->timestamp, 'until' => $day->copy()->addDay()->timestamp];
            try {
                $r = InstagramInsights::get('/me/insights', $range + ['metric' => 'follows_and_unfollows', 'breakdown' => 'follow_type'], $token);
                $follows = 0;
                $unfollows = 0;
                foreach ($r['data'][0]['total_value']['breakdowns'][0]['results'] ?? [] as $row) {
                    $type = $row['dimension_values'][0] ?? '';
                    // FOLLOWER = accounts that followed, NON_FOLLOWER = accounts that unfollowed
                    if ($type === 'FOLLOWER') {
                        $follows = (int) $row['value'];
                    } elseif ($type === 'NON_FOLLOWER') {
                        $unfollows = (int) $row['value'];
                    }
                }
                self::put($account, $day, ['unfollows' => $unfollows] + ($follows ? ['new_followers' => $follows] : []));
            } catch (RuntimeException $e) {
                $failed['unfollow'] = $e->getMessage();
            }
            try {
                $r = InstagramInsights::get('/me/insights', $range + ['metric' => 'reach'], $token);
                $value = $r['data'][0]['total_value']['value'] ?? ($r['data'][0]['values'][0]['value'] ?? null);
                self::put($account, $day, ['reach' => $value !== null ? (int) $value : 0]);
            } catch (RuntimeException $e) {
                $failed['reach akun'] = $e->getMessage();
            }
            if (count($failed) === 2) {
                break; // the account gives neither: do not spend 30 more requests
            }
        }
        foreach ($failed as $what => $why) {
            $notes[] = $what . ' (' . $why . ')';
        }

        // 4) demographics, once a day
        $hasToday = SocialAccountStat::where('social_account_id', $account->id)->whereDate('tanggal', $today)->whereNotNull('demographics')->exists();
        if (!$hasToday) {
            $demo = [];
            foreach (['age', 'gender', 'city', 'country'] as $breakdown) {
                try {
                    $demo[$breakdown] = self::demographic($breakdown, $token);
                } catch (RuntimeException $e) {
                    $failed['demografi'] = $e->getMessage();
                }
            }
            if (array_filter($demo)) {
                self::put($account, $today, ['demographics' => $demo]);
            } elseif (isset($failed['demografi'])) {
                $notes[] = 'demografi (' . $failed['demografi'] . ')';
            }
        }

        return $notes;
    }

    /** {label: count} of one breakdown of the followers, biggest first. */
    private static function demographic(string $breakdown, string $token): array
    {
        $params = ['metric' => 'follower_demographics', 'period' => 'lifetime', 'metric_type' => 'total_value', 'breakdown' => $breakdown];
        try {
            $r = InstagramInsights::get('/me/insights', $params, $token);
        } catch (RuntimeException $e) {
            // some API versions want a timeframe for demographics
            $r = InstagramInsights::get('/me/insights', $params + ['timeframe' => 'this_month'], $token);
        }
        $out = [];
        foreach ($r['data'][0]['total_value']['breakdowns'][0]['results'] ?? [] as $row) {
            $out[(string) ($row['dimension_values'][0] ?? '?')] = (int) ($row['value'] ?? 0);
        }
        arsort($out);

        return $out;
    }

    private static function put(SocialAccount $account, Carbon $day, array $values): void
    {
        $values = array_filter($values, fn ($v) => $v !== null);
        if (!$values) {
            return;
        }
        SocialAccountStat::updateOrCreate(
            ['social_account_id' => $account->id, 'tanggal' => $day->toDateString()],
            $values + ['fetched_at' => now()]
        );
    }
}
