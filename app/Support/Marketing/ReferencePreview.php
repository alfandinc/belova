<?php

namespace App\Support\Marketing;

use App\Models\Marketing\SocialAccount;
use Illuminate\Support\Facades\Storage;

/**
 * Picture of a content's "link referensi" for the card preview, kept as a copy on the server:
 * YouTube (video thumbnail), TikTok (public oEmbed), Instagram of a connected account (API), any other
 * page (its og:image). Instagram posts of other accounts usually refuse this; then nothing is stored
 * and the card falls back to the brief's reference picture.
 * One try per content + link: a ".none" marker stops repeated attempts.
 */
class ReferencePreview
{
    private const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';

    public static function path(int $planId, string $url): string
    {
        return InstagramInsights::THUMB_DIR . '/ref-' . $planId . '-' . substr(md5(trim($url)), 0, 10) . '.jpg';
    }

    /** Stored picture of this content's current reference link, or null. */
    public static function stored(int $planId, ?string $url): ?string
    {
        if (!$url || trim($url) === '') {
            return null;
        }
        $path = self::path($planId, $url);

        return Storage::disk('public')->exists($path) ? $path : null;
    }

    /** Fetch and keep the picture (once per content + link). */
    public static function fetch(int $planId, ?string $url): void
    {
        $url = self::normalize($url);
        if (!$url) {
            return;
        }
        $path = self::path($planId, $url);
        $disk = Storage::disk('public');
        if ($disk->exists($path) || $disk->exists($path . '.none')) {
            return;
        }
        try {
            $image = self::imageUrl($url);
            $body = $image ? self::download($image) : null;
            $body ? $disk->put($path, $body) : $disk->put($path . '.none', '');
        } catch (\Throwable $e) {
            report($e);
            $disk->put($path . '.none', '');
        }
    }

    private static function normalize(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }

    /** URL of the picture behind a reference link, or null. */
    private static function imageUrl(string $url): ?string
    {
        // YouTube: fixed thumbnail address
        if (preg_match('#(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([\w-]{6,})#i', $url, $m)) {
            return 'https://i.ytimg.com/vi/' . $m[1] . '/hqdefault.jpg';
        }
        // TikTok: public oEmbed; it no longer always carries the picture, then the video's cover from the
        // data embedded in the video page (short vt.tiktok.com links redirect to that page)
        if (stripos($url, 'tiktok.com') !== false) {
            $r = InstagramInsights::http()->timeout(10)->acceptJson()->get('https://www.tiktok.com/oembed', ['url' => $url]);
            if ($r->ok() && $r->json('thumbnail_url')) {
                return $r->json('thumbnail_url');
            }
            if (!self::isPublicHost($url)) {
                return null;
            }
            $page = InstagramInsights::http()->timeout(10)->withHeaders(['User-Agent' => self::UA])->get($url);
            if ($page->ok() && preg_match('#"(?:originCover|cover|dynamicCover)":"(https?:[^"]+)"#', $page->body(), $m)) {
                return json_decode('"' . $m[1] . '"') ?: null; // the JSON escapes "/" as /
            }

            return null;
        }
        // Instagram: a post of a connected account comes from the API
        if (stripos($url, 'instagram.com') !== false && ($code = InstagramInsights::shortcode($url))) {
            foreach (SocialAccount::where('platform', 'instagram')->where('is_active', true)->get() as $account) {
                try {
                    $r = InstagramInsights::get('/me/media', ['fields' => 'permalink,media_type,media_url,thumbnail_url', 'limit' => 50], $account->access_token);
                } catch (\RuntimeException $e) {
                    continue;
                }
                foreach ($r['data'] ?? [] as $media) {
                    if (InstagramInsights::shortcode($media['permalink'] ?? '') === $code) {
                        return ($media['media_type'] ?? '') === 'VIDEO' ? ($media['thumbnail_url'] ?? null) : ($media['media_url'] ?? null);
                    }
                }
            }
        }

        // any page (also other Instagram accounts, when Instagram allows it): its og:image
        if (!self::isPublicHost($url)) {
            return null;
        }
        $r = InstagramInsights::http()->timeout(10)->withHeaders(['User-Agent' => self::UA, 'Accept-Language' => 'id,en'])->get($url);
        if (!$r->ok()) {
            return null;
        }
        if (preg_match('#<meta[^>]+(?:property|name)=["\']og:image(?::secure_url)?["\'][^>]+content=["\']([^"\']+)#i', $r->body(), $m)
            || preg_match('#<meta[^>]+content=["\']([^"\']+)["\'][^>]+(?:property|name)=["\']og:image#i', $r->body(), $m)) {
            return html_entity_decode($m[1], ENT_QUOTES);
        }

        return null;
    }

    private static function download(string $imageUrl): ?string
    {
        if (!self::isPublicHost($imageUrl)) {
            return null;
        }
        $r = InstagramInsights::http()->timeout(15)->withHeaders(['User-Agent' => self::UA])->get($imageUrl);
        if (!$r->ok() || !str_starts_with((string) $r->header('Content-Type'), 'image/') || strlen($r->body()) > 8 * 1024 * 1024) {
            return null;
        }

        return $r->body();
    }

    /** Links typed by users are fetched by the server: never towards the local network. */
    private static function isPublicHost(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host || !in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            return false;
        }
        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);

        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
