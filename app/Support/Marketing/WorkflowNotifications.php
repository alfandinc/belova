<?php

namespace App\Support\Marketing;

use App\Models\Marketing\ContentList;
use App\Models\Marketing\ContentPlan;
use App\Models\Marketing\DesignRequest;
use App\Models\Marketing\StatusLog;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

/**
 * Notifications of the Content Plan / Request Desain flow, read straight from the status history
 * (marketing_status_logs): every log row after the last one the browser has seen, kept when it concerns
 * this user ("perlu approval", "siap publish", "revisi masuk", "ditugaskan ke Anda", ...).
 * Nothing is stored per user; the browser remembers the last log id.
 */
class WorkflowNotifications
{
    private const LIMIT = 200;

    /**
     * New events since the last seen log id, plus today's konten-rutin upload reminders (time based,
     * the browser shows each reminder id once).
     *
     * @return array{latest: int, items: array<int, array>, reminders: array<int, array>}
     */
    public static function feed(User $user, int $since): array
    {
        return self::events($user, $since) + ['reminders' => array_merge(RoutineSchedule::reminders($user), DesignReminders::reminders($user))];
    }

    private static function events(User $user, int $since): array
    {
        if (!Schema::hasTable('marketing_status_logs')) {
            return ['latest' => 0, 'items' => []];
        }
        $latest = (int) StatusLog::max('id');
        // first call of a browser: start from now, older history is not news
        if ($since <= 0 || $since > $latest) {
            return ['latest' => $latest, 'items' => []];
        }

        $logs = StatusLog::with(['user:id,name', 'loggable'])
            ->where('id', '>', $since)
            ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', '!=', $user->id))
            ->orderBy('id')
            ->limit(self::LIMIT)
            ->get();

        $marketing = MarketingAccess::isMarketing($user);
        $items = [];
        foreach ($logs as $log) {
            $item = self::describe($log, $user, $marketing);
            if ($item) {
                $items[] = $item + ['id' => $log->id, 'at' => optional($log->created_at)->toIso8601String()];
            }
        }

        return ['latest' => $logs->isNotEmpty() && $logs->count() === self::LIMIT ? (int) $logs->last()->id : $latest, 'items' => array_slice($items, -20)];
    }

    /** Title / text / link of one log row for this user, or null when it does not concern them. */
    private static function describe(StatusLog $log, User $user, bool $marketing): ?array
    {
        $record = $log->loggable;
        if (!$record) {
            return null;
        }
        $by = optional($log->user)->name ?: 'Sistem';
        $me = (int) $user->id;

        if ($record instanceof ContentPlan) {
            $pic = (int) $record->assigned_to === $me;
            $url = url('/marketing/content-plan') . '?open=' . $record->id;
            $text = '“' . $record->judul . '”';
            switch ($log->action) {
                case 'submit_review':
                    return $marketing ? self::item('Konten perlu approval', $text . ' · dari ' . $by, $url, 'warning') : null;
                case 'approve':
                    return $marketing || $pic ? self::item('Siap publish', $text . ' sudah di-approve', $url, 'success') : null;
                case 'revise':
                    return $pic ? self::item('Revisi masuk', $text . self::firstLine($log->notes), $url, 'danger') : null;
                case 'publish':
                    return $marketing ? self::item('Sudah tayang', $text . ' · ' . $by, $url, 'info') : null;
                case 'created':
                    return $pic ? self::item('Konten baru untuk Anda', $text . ' · dari ' . $by, $url, 'info') : null;
                case 'reschedule':
                    return $pic ? self::item('Jadwal diubah', $text . ' · ' . $log->notes, $url, 'info') : null;
            }

            return null;
        }

        if ($record instanceof DesignRequest) {
            $designer = (int) $record->designer_id === $me;
            $requester = (int) $record->requester_id === $me;
            $url = url('/design-request') . '?open=' . $record->id;
            $text = '“' . $record->judul . '”';
            // approval of a content job is announced by the content itself (Siap publish / Revisi masuk)
            $contentJob = $record->isContentJob();
            switch ($log->action) {
                case 'created':
                    if ($designer) {
                        return self::item('Desain ditugaskan ke Anda', $text . ' · dari ' . $by, $url, 'warning');
                    }

                    return $marketing ? self::item('Request desain baru', $text . ' · dari ' . $by, $url, 'info') : null;
                case 'assign':
                    return $designer ? self::item('Desain ditugaskan ke Anda', $text, $url, 'warning') : null;
                case 'take':
                    return $requester && !$contentJob ? self::item('Desain mulai dikerjakan', $text . ' · ' . $by, $url, 'info') : null;
                case 'submit_review':
                    if ($contentJob) {
                        return null;
                    }
                    if ($marketing) {
                        return self::item('Desain perlu approval', $text . ' · selesai dikerjakan ' . $by, $url, 'warning');
                    }

                    return $requester ? self::item('Desain selesai dikerjakan', $text . ' · menunggu approval Marketing', $url, 'info') : null;
                case 'approve':
                    // a content job's approval is announced by the content itself ("Siap publish")
                    return !$contentJob && ($designer || $requester) ? self::item('Desain di-approve', $text, $url, 'success') : null;
                case 'revise':
                    return $designer || ($requester && !$contentJob) ? self::item('Revisi desain', $text . self::firstLine($log->notes), $url, 'danger') : null;
                case 'cancel':
                    return $designer || $requester ? self::item('Request dibatalkan', $text . ' · ' . $by, $url, 'info') : null;
                case 'comment':
                    return $designer || $requester ? self::item('Komentar baru', $text . ' · ' . $by . self::firstLine($log->notes, ': '), $url, 'info') : null;
            }

            return null;
        }

        if ($record instanceof ContentList) {
            $url = url('/marketing/content-plan') . '?tab=ide';
            $text = '“' . $record->judul . '”';
            switch ($log->action) {
                case 'created':
                case 'resubmitted':
                    return $marketing ? self::item('Ide konten perlu approval', $text . ' · dari ' . $by, $url, 'warning') : null;
                case 'approve':
                case 'reject':
                    $owner = (int) $record->assigned_to === $me || self::createdBy($record) === $me;

                    return $owner ? self::item($log->action === 'approve' ? 'Ide disetujui' : 'Ide ditolak', $text . self::firstLine($log->notes), $url, $log->action === 'approve' ? 'success' : 'danger') : null;
            }
        }

        return null;
    }

    private static function item(string $title, string $text, string $url, string $tone): array
    {
        return ['title' => $title, 'text' => $text, 'url' => $url, 'tone' => $tone];
    }

    /** The note on one line ("Dari: CEO · logo kebesaran"), without the "Dari Content Plan:" prefix of synced logs. */
    private static function firstLine(?string $notes, string $glue = ' · '): string
    {
        $lines = collect(preg_split('/\R/', (string) $notes))->map(fn ($l) => trim($l))
            ->reject(fn ($l) => $l === '' || $l === 'Dari Content Plan:' || $l === 'Dari Content Plan');
        $line = $lines->implode(' · ');

        return $line === '' ? '' : $glue . \Illuminate\Support\Str::limit($line, 90);
    }

    private static function createdBy(ContentList $idea): int
    {
        return (int) $idea->statusLogs()->where('action', 'created')->value('user_id');
    }
}
