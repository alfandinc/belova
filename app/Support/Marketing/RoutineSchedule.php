<?php

namespace App\Support\Marketing;

use App\Models\Marketing\ContentRoutine;
use App\Models\Marketing\ContentRoutinePost;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Schema;

/**
 * The days a "konten rutin" has to be uploaded and their state: posted / skipped (recorded),
 * due (today, upload time not reached yet), late (time passed, not uploaded) or upcoming.
 * Used by the Content Plan board, the "terlewat" counter and the upload reminders.
 */
class RoutineSchedule
{
    /** Missed uploads older than this are history, not "terlewat" any more. */
    public const LATE_WINDOW_DAYS = 7;

    /** Second reminder when the upload is still missing this long after the upload time. */
    public const LATE_REMINDER_MINUTES = 60;

    public static function enabled(): bool
    {
        static $enabled = null;

        return $enabled ??= Schema::hasTable('marketing_content_routines') && Schema::hasTable('marketing_content_routine_posts');
    }

    /**
     * Occurrences between two days, grouped by date (Y-m-d), ordered by time.
     * filters: mine (user id), brand[] / platform[] (any of).
     *
     * @return array<string, array<int, array>>
     */
    public static function between(Carbon $from, Carbon $to, array $filters = []): array
    {
        if (!self::enabled()) {
            return [];
        }
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();

        $routines = ContentRoutine::with('assignedTo:id,name')
            ->whereDate('start_date', '<=', $to)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $from))
            ->when(!empty($filters['mine']), fn ($q) => $q->where('assigned_to', $filters['mine']))
            ->orderBy('jam')->orderBy('id')
            ->get()
            ->filter(fn (ContentRoutine $r) => self::matches($r, $filters));
        if ($routines->isEmpty()) {
            return [];
        }

        $posts = ContentRoutinePost::with('user:id,name')
            ->whereIn('routine_id', $routines->pluck('id'))
            ->whereBetween('tanggal', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->keyBy(fn ($p) => $p->routine_id . '|' . $p->tanggal->toDateString());

        $out = [];
        foreach (CarbonPeriod::create($from, $to) as $day) {
            foreach ($routines as $routine) {
                $post = $posts->get($routine->id . '|' . $day->toDateString());
                // recorded days always stay (also after the days / period were changed or the routine
                // was paused); open days only while the routine is active and scheduled that day
                if (!$post && (!$routine->is_active || !$routine->scheduledOn($day))) {
                    continue;
                }
                $out[$day->toDateString()][] = self::occurrence($routine, $day, $post);
            }
        }

        return $out;
    }

    /** Missed uploads of the last days (oldest first). */
    public static function late(array $filters = []): array
    {
        $rows = [];
        foreach (self::between(Carbon::today()->subDays(self::LATE_WINDOW_DAYS - 1), Carbon::today(), $filters) as $day) {
            foreach ($day as $occ) {
                if ($occ['state'] === 'late') {
                    $rows[] = $occ;
                }
            }
        }

        return $rows;
    }

    /**
     * Today's upload reminders of this user: "Waktunya upload" at the upload time and "Belum diupload"
     * an hour later. A routine without PIC reminds the Marketing team (role Marketing, not Admin).
     * Ids are stable, so each one is shown once.
     */
    public static function reminders(User $user): array
    {
        if (!self::enabled()) {
            return [];
        }
        $marketing = $user->hasRole('Marketing');
        $now = Carbon::now();
        $out = [];
        foreach (self::between(Carbon::today(), Carbon::today())[Carbon::today()->toDateString()] ?? [] as $occ) {
            $mine = (int) $occ['assigned_to'] === (int) $user->id || (!$occ['assigned_to'] && $marketing);
            if (!$mine || in_array($occ['state'], ['posted', 'skipped'], true)) {
                continue;
            }
            $slot = Carbon::parse($occ['date'] . ' ' . $occ['time']);
            if ($now->lt($slot)) {
                continue;
            }
            $late = $now->gte($slot->copy()->addMinutes(self::LATE_REMINDER_MINUTES));
            $out[] = [
                'id' => 'rt-' . $occ['routine_id'] . '-' . $occ['date'] . ($late ? '-late' : '-due'),
                'title' => $late ? 'Belum diupload' : 'Waktunya upload',
                'text' => '“' . $occ['judul'] . '” · jam ' . str_replace(':', '.', $occ['time']) . ' · ' . implode(', ', array_column($occ['platforms'], 'name')),
                'url' => url('/marketing/content-plan') . '?routine=' . $occ['routine_id'],
                'tone' => $late ? 'danger' : 'warning',
            ];
        }

        return $out;
    }

    public static function occurrence(ContentRoutine $routine, Carbon $day, ?ContentRoutinePost $post): array
    {
        // a recorded day keeps the time that was scheduled then
        $time = $post && $post->jam_jadwal ? substr((string) $post->jam_jadwal, 0, 5) : $routine->jamLabel();
        $slot = Carbon::parse($day->toDateString() . ' ' . $time);
        if ($post) {
            $state = $post->status;
        } elseif (Carbon::now()->gte($slot)) {
            $state = 'late';
        } else {
            $state = $day->isToday() ? 'due' : 'upcoming';
        }
        $links = $post && is_array($post->link_publikasi) ? $post->link_publikasi : [];
        // minutes the post went live after the upload time (posted days only)
        $lateBy = $post && $post->status === ContentRoutinePost::POSTED && $post->done_at
            ? (int) $slot->diffInMinutes($post->done_at, false) : null;

        return [
            'type' => 'routine',
            'key' => $routine->id . '-' . $day->toDateString(),
            'routine_id' => $routine->id,
            'date' => $day->toDateString(),
            'time' => $time,
            'judul' => $routine->judul,
            'brand' => array_values((array) ($routine->brand ?? [])),
            'platforms' => array_map(fn ($p) => ['name' => $p, 'url' => self::safeUrl($links[$p] ?? null)], array_values((array) ($routine->platform ?? []))),
            'jenis_konten' => array_values((array) ($routine->jenis_konten ?? [])),
            'assigned_to' => $routine->assigned_to,
            'assigned_to_name' => optional($routine->assignedTo)->name,
            'days_label' => $routine->daysLabel(),
            'state' => $state,
            'done_by' => $post ? optional($post->user)->name : null,
            'done_at' => $post && $post->done_at ? $post->done_at->toIso8601String() : null,
            'recorded_at' => $post ? optional($post->updated_at)->toIso8601String() : null,
            'on_time' => $lateBy === null ? null : $lateBy <= \App\Http\Controllers\Marketing\ContentPlanController::ON_TIME_MINUTES,
            'late_by' => $lateBy !== null && $lateBy > \App\Http\Controllers\Marketing\ContentPlanController::ON_TIME_MINUTES
                ? \App\Http\Controllers\Marketing\ContentPlanController::duration($lateBy) : null,
            'notes' => $post ? $post->notes : null,
        ];
    }

    private static function matches(ContentRoutine $routine, array $filters): bool
    {
        foreach (['brand', 'platform'] as $field) {
            if (!empty($filters[$field]) && !array_intersect((array) $filters[$field], (array) ($routine->{$field} ?? []))) {
                return false;
            }
        }

        return true;
    }

    /** Only http(s) links are rendered; a bare "instagram.com/..." gets https:// */
    public static function safeUrl($url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        return preg_match('#^[a-z][a-z0-9+.\-]*:#i', $url) ? null : 'https://' . ltrim($url, '/');
    }
}
