<?php

namespace App\Support\Marketing;

use App\Http\Controllers\Marketing\ContentPlanController;
use App\Models\Marketing\ContentInsight;
use App\Models\Marketing\ContentPlan;
use App\Models\Marketing\ContentRoutine;
use App\Models\Marketing\DesignRequest;
use App\Models\Marketing\StatusLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Track record of the team in a period, compared with the previous one.
 *
 * Content creator (PIC of the content): content published, % on time, revisions per content, average
 * views, konten-rutin uploads, content still pending past its date.
 * Designer: jobs finished, % delivered by the deadline (first "Selesai dikerjakan"), average working time
 * (taken → first "Selesai dikerjakan"), revisions per job, active jobs past their deadline.
 */
class TeamAnalytics
{
    public static function build(Carbon $start, Carbon $end, Carbon $prevStart, Carbon $prevEnd): array
    {
        $logsReady = Schema::hasTable('marketing_status_logs');

        return [
            'creators' => self::creators($start, $end, $prevStart, $prevEnd, $logsReady),
            'designers' => Schema::hasTable('marketing_design_requests') ? self::designers($start, $end, $prevStart, $prevEnd, $logsReady) : [],
        ];
    }

    // ------------------------------------------------------------------ content creators

    private static function creators(Carbon $start, Carbon $end, Carbon $prevStart, Carbon $prevEnd, bool $logsReady): array
    {
        $cur = self::creatorStats($start, $end, $logsReady);
        $prev = self::creatorStats($prevStart, $prevEnd, $logsReady);
        $names = User::whereIn('id', array_keys($cur + $prev))->pluck('name', 'id');

        $out = [];
        foreach ($cur as $userId => $s) {
            $p = $prev[$userId] ?? null;
            $out[] = $s + [
                'id' => $userId,
                'name' => $names[$userId] ?? '?',
                'prev_on_time' => $p['on_time'] ?? null,
                'prev_published' => $p['published'] ?? null,
                'prev_revisions' => $p['revisions'] ?? null,
            ];
        }
        usort($out, fn ($a, $b) => $b['published'] <=> $a['published']);

        return $out;
    }

    private static function creatorStats(Carbon $start, Carbon $end, bool $logsReady): array
    {
        $to = $end->copy()->endOfDay();
        $plans = ContentPlan::whereNotNull('assigned_to')->whereBetween('tanggal_publish', [$start, $to])->where('status', '!=', 'Cancelled')
            ->get(['id', 'assigned_to', 'status', 'tanggal_publish']);
        $published = $plans->where('status', 'Published');
        $info = ContentPlanController::publishInfo($published->pluck('id')->all());
        $revisions = $logsReady
            ? StatusLog::where('loggable_type', ContentPlan::class)->whereIn('loggable_id', $plans->pluck('id'))->where('action', 'revise')
                ->selectRaw('loggable_id, count(*) as n')->groupBy('loggable_id')->pluck('n', 'loggable_id')
            : collect();
        $views = InstagramInsights::enabled()
            ? ContentInsight::whereIn('content_plan_id', $published->pluck('id'))->whereNotNull('views')->orderBy('tanggal')->get()
                ->groupBy('content_plan_id')->map(fn ($rows) => (int) $rows->last()->views)
            : collect();

        $out = [];
        foreach ($plans->groupBy('assigned_to') as $userId => $list) {
            $pub = $list->where('status', 'Published');
            $timed = $pub->filter(fn ($p) => isset($info[$p->id]['on_time']));
            $onTime = $timed->filter(fn ($p) => $info[$p->id]['on_time'])->count();
            $viewed = $pub->filter(fn ($p) => $views->has($p->id));
            $out[$userId] = [
                'planned' => $list->count(),
                'published' => $pub->count(),
                // past their publish time and still not live
                'pending' => $list->filter(fn ($p) => $p->status !== 'Published' && $p->tanggal_publish && $p->tanggal_publish->lt(now()))->count(),
                'on_time' => $timed->count() ? (int) round($onTime / $timed->count() * 100) : null,
                'revisions' => $list->count() ? round($list->sum(fn ($p) => (int) ($revisions[$p->id] ?? 0)) / $list->count(), 1) : null,
                'avg_views' => $viewed->count() ? (int) round($viewed->avg(fn ($p) => $views[$p->id])) : null,
                'routine' => null,
            ];
        }

        // konten rutin: days uploaded / days scheduled (skipped days left out)
        if (RoutineSchedule::enabled()) {
            $routinePic = ContentRoutine::whereNotNull('assigned_to')->pluck('assigned_to', 'id');
            $done = [];
            foreach (RoutineSchedule::between($start, $end->copy()->min(Carbon::today())) as $day) {
                foreach ($day as $occ) {
                    $pic = $routinePic[$occ['routine_id']] ?? null;
                    if (!$pic || in_array($occ['state'], ['skipped', 'upcoming', 'due'], true)) {
                        continue;
                    }
                    $done[$pic] ??= [0, 0];
                    $done[$pic][1]++;
                    $done[$pic][0] += $occ['state'] === 'posted' ? 1 : 0;
                }
            }
            foreach ($done as $pic => [$posted, $total]) {
                $out[$pic] ??= ['planned' => 0, 'published' => 0, 'pending' => 0, 'on_time' => null, 'revisions' => null, 'avg_views' => null];
                $out[$pic]['routine'] = ['posted' => $posted, 'total' => $total];
            }
        }

        return $out;
    }

    // ------------------------------------------------------------------ designers

    private static function designers(Carbon $start, Carbon $end, Carbon $prevStart, Carbon $prevEnd, bool $logsReady): array
    {
        $cur = self::designerStats($start, $end, $logsReady);
        $prev = self::designerStats($prevStart, $prevEnd, $logsReady);
        $late = DesignRequest::whereIn('status', DesignRequest::ACTIVE_STATUSES)->whereNotNull('designer_id')
            ->whereDate('deadline', '<', Carbon::today())->selectRaw('designer_id, count(*) as n')->groupBy('designer_id')->pluck('n', 'designer_id');
        $ids = array_unique(array_merge(array_keys($cur), $late->keys()->all()));
        $names = User::whereIn('id', $ids)->pluck('name', 'id');

        $out = [];
        foreach ($ids as $userId) {
            $s = $cur[$userId] ?? ['finished' => 0, 'on_deadline' => null, 'work_hours' => null, 'revisions' => null, 'delivered' => 0];
            $p = $prev[$userId] ?? null;
            $out[] = $s + [
                'id' => $userId,
                'name' => $names[$userId] ?? '?',
                'late_active' => (int) ($late[$userId] ?? 0),
                'prev_on_deadline' => $p['on_deadline'] ?? null,
                'prev_finished' => $p['finished'] ?? null,
                'prev_work_hours' => $p['work_hours'] ?? null,
            ];
        }
        usort($out, fn ($a, $b) => $b['delivered'] <=> $a['delivered']);

        return $out;
    }

    /** Jobs delivered (first "Selesai dikerjakan") inside the period, per designer. */
    private static function designerStats(Carbon $start, Carbon $end, bool $logsReady): array
    {
        if (!$logsReady) {
            return [];
        }
        $to = $end->copy()->endOfDay();
        // only jobs handed in during the period (their earlier logs are needed for take / first hand-in)
        $jobIds = StatusLog::where('loggable_type', DesignRequest::class)->where('action', 'submit_review')
            ->whereBetween('created_at', [$start, $to])->distinct()->pluck('loggable_id');
        if ($jobIds->isEmpty()) {
            return [];
        }
        $logs = StatusLog::where('loggable_type', DesignRequest::class)->whereIn('loggable_id', $jobIds)
            ->whereIn('action', ['take', 'submit_review', 'revise'])
            ->orderBy('id')->get(['loggable_id', 'action', 'created_at'])->groupBy('loggable_id');
        $jobs = DesignRequest::whereIn('id', $logs->keys())->whereNotNull('designer_id')
            ->get(['id', 'designer_id', 'deadline', 'status', 'completed_at'])->keyBy('id');

        $acc = [];
        foreach ($logs as $jobId => $list) {
            $job = $jobs->get($jobId);
            $firstDone = $list->firstWhere('action', 'submit_review');
            if (!$job || !$firstDone || !$firstDone->created_at->between($start, $to)) {
                continue;
            }
            $taken = $list->firstWhere('action', 'take');
            $a = &$acc[$job->designer_id];
            $a ??= ['delivered' => 0, 'finished' => 0, 'deadline_ok' => 0, 'deadline_n' => 0, 'hours' => [], 'revisions' => 0];
            $a['delivered']++;
            if ($job->status === DesignRequest::STATUS_SELESAI) {
                $a['finished']++;
            }
            if ($job->deadline) {
                $a['deadline_n']++;
                $a['deadline_ok'] += $firstDone->created_at->lte($job->deadline->copy()->endOfDay()) ? 1 : 0;
            }
            if ($taken && $taken->created_at->lte($firstDone->created_at)) {
                $a['hours'][] = $taken->created_at->diffInMinutes($firstDone->created_at) / 60;
            }
            $a['revisions'] += $list->where('action', 'revise')->count();
            unset($a);
        }

        $out = [];
        foreach ($acc as $designerId => $a) {
            $out[$designerId] = [
                'delivered' => $a['delivered'],
                'finished' => $a['finished'],
                'on_deadline' => $a['deadline_n'] ? (int) round($a['deadline_ok'] / $a['deadline_n'] * 100) : null,
                'work_hours' => $a['hours'] ? round(array_sum($a['hours']) / count($a['hours']), 1) : null,
                'revisions' => round($a['revisions'] / $a['delivered'], 1),
            ];
        }

        return $out;
    }
}
