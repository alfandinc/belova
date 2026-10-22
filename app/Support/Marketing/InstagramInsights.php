<?php

namespace App\Support\Marketing;

use App\Models\Marketing\ContentInsight;
use App\Models\Marketing\ContentPlan;
use App\Models\Marketing\SocialAccount;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Reads the numbers of published Instagram posts through the official Instagram API (Instagram Login,
 * permissions instagram_business_basic + instagram_business_manage_insights) and stores one snapshot
 * per content per day. A post is found by the shortcode of its link publikasi (instagram.com/p|reel/XXX).
 */
class InstagramInsights
{
    private const API = 'https://graph.instagram.com';

    /** Published content of the last days is updated by "Update insight". */
    public const SYNC_DAYS = 60;

    /** Pages of 50 recent posts searched per account to find the linked posts. */
    private const MEDIA_PAGES = 10;

    /** API metric => column. */
    private const INSIGHT_METRICS = ['views' => 'views', 'reach' => 'reach', 'saved' => 'saves', 'shares' => 'shares'];

    public static function enabled(): bool
    {
        static $enabled = null;

        return $enabled ??= Schema::hasTable('marketing_social_accounts') && Schema::hasTable('marketing_content_insights');
    }

    /** Connect (or reconnect) an account with a token generated in the Meta app dashboard. */
    public static function connect(string $token, ?string $brand, ?int $userId): SocialAccount
    {
        $token = trim($token);
        $me = self::get('/me', ['fields' => 'user_id,username,account_type'], $token);
        $type = strtoupper((string) ($me['account_type'] ?? ''));
        if ($type !== '' && !in_array($type, ['BUSINESS', 'MEDIA_CREATOR'], true)) {
            throw new RuntimeException('Akun @' . ($me['username'] ?? '?') . ' bukan akun profesional (Business/Creator).');
        }

        $account = SocialAccount::updateOrCreate(
            ['platform' => 'instagram', 'external_id' => (string) ($me['user_id'] ?? $me['id'] ?? '')],
            [
                'username' => $me['username'] ?? null,
                'brand' => $brand ?: null,
                'access_token' => $token,
                // tokens from the dashboard live 60 days; the exact date is known after the first refresh
                'token_expires_at' => now()->addDays(60),
                'token_refreshed_at' => now(),
                'last_error' => null,
                'is_active' => true,
                'created_by' => $userId,
            ]
        );

        return $account;
    }

    /**
     * Extend the token another 60 days (allowed once it is at least 24 hours old). Done during an update
     * when the last refresh is a week old, so a token in daily use never expires.
     */
    public static function refreshIfDue(SocialAccount $account): void
    {
        if ($account->token_refreshed_at && $account->token_refreshed_at->gt(now()->subDays(7))) {
            return;
        }
        try {
            $r = self::get('/refresh_access_token', ['grant_type' => 'ig_refresh_token'], $account->access_token);
            if (!empty($r['access_token'])) {
                $account->update([
                    'access_token' => $r['access_token'],
                    'token_expires_at' => now()->addSeconds((int) ($r['expires_in'] ?? 5184000)),
                    'token_refreshed_at' => now(),
                ]);
            }
        } catch (RuntimeException $e) {
            // keep the current token; it still works until it expires
            $account->update(['last_error' => 'Perpanjang token gagal: ' . $e->getMessage()]);
        }
    }

    /** "DF4IMLEyB4L" from instagram.com/p|reel|reels|tv/DF4IMLEyB4L (with or without the username). */
    public static function shortcode(?string $url): ?string
    {
        return preg_match('#instagram\.com/(?:[A-Za-z0-9._]+/)?(?:p|reel|reels|tv)/([A-Za-z0-9_-]+)#i', (string) $url, $m) ? $m[1] : null;
    }

    /**
     * Check a post link typed in "Tandai published": its format, and whether the post is among the latest
     * posts of a connected account. Returns [status, message, username]:
     * ok | not_post (story / profile / short link) | not_found | unchecked (no account or Instagram unreachable).
     */
    public static function checkLink(?string $url): array
    {
        $url = trim((string) $url);
        if ($url === '') {
            return ['empty', null, null];
        }
        if (stripos($url, 'instagram.com') === false && stripos($url, 'instagr.am') === false) {
            return ['not_post', 'Ini bukan link Instagram.', null];
        }
        if (preg_match('#instagram\.com/stories/#i', $url)) {
            return ['not_post', 'Ini link story: insight story tidak bisa dilacak (hanya 24 jam). Untuk feed/reels pakai link postingannya.', null];
        }
        $code = self::shortcode($url);
        if (!$code) {
            return ['not_post', 'Link ini bukan link postingan. Pakai link dari tombol Bagikan → Salin link di postingan (…/p/… atau …/reel/…).', null];
        }
        $accounts = self::enabled() ? SocialAccount::where('platform', 'instagram')->where('is_active', true)->get() : collect();
        if ($accounts->isEmpty()) {
            return ['unchecked', 'Format link benar. (Belum ada akun Instagram terhubung untuk dicek.)', null];
        }
        $reachable = false;
        foreach ($accounts as $account) {
            try {
                $r = self::get('/me/media', ['fields' => 'id,permalink', 'limit' => 50], $account->access_token);
                $reachable = true;
                foreach ($r['data'] ?? [] as $media) {
                    if (self::shortcode($media['permalink'] ?? '') === $code) {
                        return ['ok', 'Postingan ditemukan di @' . $account->username . '. Insight-nya akan terlacak.', $account->username];
                    }
                }
            } catch (RuntimeException $e) {
                // try the next account
            }
        }
        if (!$reachable) {
            return ['unchecked', 'Format link benar, tapi Instagram sedang tidak bisa dihubungi untuk mengecek.', null];
        }

        return ['not_found', 'Postingan ini tidak ditemukan di akun yang terhubung (@' . $accounts->pluck('username')->implode(', @') . '). Cek lagi link-nya, atau akun kliniknya belum dihubungkan.', null];
    }

    /** The Instagram post link of a content (key "Instagram", otherwise any instagram.com link). */
    public static function instagramLink(ContentPlan $plan): ?string
    {
        $links = is_array($plan->link_publikasi) ? $plan->link_publikasi : [];
        if (!empty($links['Instagram'])) {
            return $links['Instagram'];
        }
        foreach ($links as $url) {
            if (is_string($url) && stripos($url, 'instagram.com') !== false) {
                return $url;
            }
        }

        return null;
    }

    /** Published content with an Instagram link that "Update insight" refreshes. */
    public static function eligiblePlans(?int $planId = null): Collection
    {
        $query = ContentPlan::where('status', 'Published');
        $planId
            ? $query->where('id', $planId)
            : $query->where('tanggal_publish', '>=', now()->subDays(self::SYNC_DAYS)->startOfDay());

        return $query->get()->filter(fn (ContentPlan $p) => self::instagramLink($p) !== null)->values();
    }

    /**
     * Take today's snapshot of each content. Returns a summary for the user:
     * updated, not_found (link not found in any connected account), skipped (link without post code,
     * e.g. a story), errors (per account / per post) and missing metrics.
     */
    public static function sync(Collection $plans, ?int $userId, bool $withAccounts = true): array
    {
        $accounts = SocialAccount::where('platform', 'instagram')->where('is_active', true)->get();
        if ($accounts->isEmpty()) {
            throw new RuntimeException('Belum ada akun Instagram yang terhubung.');
        }

        $summary = ['updated' => 0, 'not_found' => [], 'skipped' => [], 'errors' => [], 'missing_metrics' => [], 'account_notes' => []];

        // followers & account numbers of the day (not when only one content is refreshed)
        if ($withAccounts) {
            foreach ($accounts as $account) {
                self::refreshIfDue($account);
                try {
                    foreach (InstagramAccountStats::sync($account) as $note) {
                        $summary['account_notes'][] = '@' . $account->username . ': ' . $note;
                    }
                } catch (RuntimeException $e) {
                    $summary['errors'][] = '@' . $account->username . ' (followers): ' . $e->getMessage();
                }
            }
        }
        $targets = [];
        foreach ($plans as $plan) {
            $code = self::shortcode(self::instagramLink($plan));
            if ($code) {
                $targets[$code] = $plan;
            } else {
                $summary['skipped'][] = $plan->judul . ' (link bukan postingan feed/reels, mis. story)';
            }
        }
        if (!$targets) {
            return $summary;
        }

        // find the posts in the connected accounts, newest first
        $oldest = collect($targets)->min(fn ($p) => optional($p->tanggal_publish)->timestamp ?? time());
        $found = [];
        foreach ($accounts as $account) {
            self::refreshIfDue($account);
            try {
                $after = null;
                for ($page = 0; $page < self::MEDIA_PAGES && count($found) < count($targets); $page++) {
                    $params = ['fields' => 'id,permalink,timestamp,like_count,comments_count,media_product_type,media_type,media_url,thumbnail_url', 'limit' => 50];
                    if ($after) {
                        $params['after'] = $after;
                    }
                    $r = self::get('/me/media', $params, $account->access_token);
                    $stop = false;
                    foreach ($r['data'] ?? [] as $media) {
                        $code = self::shortcode($media['permalink'] ?? '');
                        if ($code && isset($targets[$code]) && !isset($found[$code])) {
                            $found[$code] = ['media' => $media, 'account' => $account];
                        }
                        // posts older than the oldest content (plus a week of margin) are not needed
                        if (!empty($media['timestamp']) && Carbon::parse($media['timestamp'])->timestamp < $oldest - 7 * 86400) {
                            $stop = true;
                        }
                    }
                    $after = $r['paging']['cursors']['after'] ?? null;
                    if ($stop || empty($r['paging']['next']) || !$after) {
                        break;
                    }
                }
                $account->update(['last_synced_at' => now(), 'last_error' => null]);
            } catch (RuntimeException $e) {
                $account->update(['last_error' => $e->getMessage()]);
                $summary['errors'][] = '@' . $account->username . ': ' . $e->getMessage();
            }
        }

        $today = Carbon::today()->toDateString();
        foreach ($targets as $code => $plan) {
            if (!isset($found[$code])) {
                $summary['not_found'][] = $plan->judul;
                continue;
            }
            ['media' => $media, 'account' => $account] = $found[$code];
            [$values, $missing] = self::insights($media['id'], $account->access_token);
            foreach ($missing as $m) {
                $summary['missing_metrics'][$m] = true;
            }
            ContentInsight::updateOrCreate(
                ['content_plan_id' => $plan->id, 'platform' => 'instagram', 'tanggal' => $today],
                $values + [
                    'likes' => $media['like_count'] ?? null,
                    'comments' => $media['comments_count'] ?? null,
                    'social_account_id' => $account->id,
                    'media_id' => $media['id'],
                    'user_id' => $userId,
                    'fetched_at' => now(),
                ]
            );
            self::saveThumbnail($plan->id, $media);
            $summary['updated']++;
        }
        $summary['missing_metrics'] = array_keys($summary['missing_metrics']);

        return $summary;
    }

    public const THUMB_DIR = 'marketing/thumbs';

    /** Stored picture of a content's post, or null. */
    public static function thumbnailPath(int $planId): ?string
    {
        $path = self::THUMB_DIR . '/' . $planId . '.jpg';

        return Storage::disk('public')->exists($path) ? $path : null;
    }

    /**
     * Keep a copy of the post's picture (video: its cover) for the card: Instagram's media links expire
     * after a few days, a stored copy does not. Taken once per content.
     */
    private static function saveThumbnail(int $planId, array $media): void
    {
        if (self::thumbnailPath($planId)) {
            return;
        }
        $src = ($media['media_type'] ?? '') === 'VIDEO'
            ? ($media['thumbnail_url'] ?? null)
            : ($media['media_url'] ?? $media['thumbnail_url'] ?? null);
        if (!$src) {
            return;
        }
        try {
            $response = self::http()->timeout(20)->get($src);
            if ($response->ok() && str_starts_with((string) $response->header('Content-Type'), 'image/')) {
                Storage::disk('public')->put(self::THUMB_DIR . '/' . $planId . '.jpg', $response->body());
            }
        } catch (\Throwable $e) {
            report($e); // the card simply shows no picture
        }
    }

    /** HTTP client with the CA bundle when PHP has none (WAMP). */
    private static function http()
    {
        $http = Http::timeout(25);
        $caBundle = config('services.instagram.cacert_path');

        return $caBundle && is_file($caBundle) ? $http->withOptions(['verify' => $caBundle]) : $http;
    }

    /**
     * views / reach / saves / shares of one post. Not every metric exists for every kind of post: when the
     * combined request is refused, each metric is asked alone and the refused ones are reported.
     *
     * @return array{0: array<string, int|null>, 1: string[]}
     */
    public static function insights(string $mediaId, string $token): array
    {
        $values = [];
        $missing = [];
        try {
            $values = self::parseInsights(self::get('/' . $mediaId . '/insights', ['metric' => implode(',', array_keys(self::INSIGHT_METRICS))], $token));
        } catch (RuntimeException $e) {
            foreach (self::INSIGHT_METRICS as $metric => $column) {
                try {
                    $values += self::parseInsights(self::get('/' . $mediaId . '/insights', ['metric' => $metric], $token));
                } catch (RuntimeException $e2) {
                    $missing[] = $column;
                }
            }
        }

        return [$values, $missing];
    }

    private static function parseInsights(array $response): array
    {
        $out = [];
        foreach ($response['data'] ?? [] as $row) {
            $column = self::INSIGHT_METRICS[$row['name'] ?? ''] ?? null;
            if (!$column) {
                continue;
            }
            $value = $row['values'][0]['value'] ?? ($row['total_value']['value'] ?? null);
            $out[$column] = is_numeric($value) ? (int) $value : null;
        }

        return $out;
    }

    /** GET on the Instagram API; an API error becomes a RuntimeException with its (readable) message. */
    public static function get(string $path, array $params, string $token): array
    {
        $http = self::http()->acceptJson();
        try {
            $response = $http->get(self::API . $path, $params + ['access_token' => $token]);
        } catch (\Throwable $e) {
            $hint = str_contains($e->getMessage(), 'cURL error 60')
                ? 'sertifikat SSL server belum diatur: isi INSTAGRAM_CACERT_PATH di .env dengan lokasi file cacert.pem'
                : $e->getMessage();
            throw new RuntimeException('Tidak bisa menghubungi Instagram (' . $hint . ')');
        }
        $json = $response->json() ?? [];
        if ($response->failed() || isset($json['error'])) {
            $message = $json['error']['message'] ?? ('HTTP ' . $response->status());
            if (($json['error']['code'] ?? null) == 190) {
                $message = 'Token tidak berlaku lagi, hubungkan ulang akun ini.';
            }
            throw new RuntimeException($message);
        }

        return $json;
    }
}
