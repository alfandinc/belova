<?php

namespace App\Support\Marketing;

use App\Models\Marketing\ContentPlan;
use App\Models\Marketing\DesignRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * "Belum diambil designer" warnings: a job still waiting (Baru) while its deadline is tomorrow, today or
 * past. Shown as a notification to its designer (or the Marketing team when nobody is assigned) and to
 * the PIC of the content, and as a mark on the cards.
 */
class DesignReminders
{
    /** Days before the deadline the warning starts (1 = H-1). */
    public const WARN_DAYS = 1;

    /** urgency of a waiting job: 'late' | 'today' | 'tomorrow' | null */
    public static function urgency(?Carbon $deadline, string $status): ?string
    {
        if ($status !== DesignRequest::STATUS_BARU || !$deadline) {
            return null;
        }
        $days = (int) Carbon::today()->diffInDays($deadline->copy()->startOfDay(), false);
        if ($days < 0) {
            return 'late';
        }
        if ($days === 0) {
            return 'today';
        }

        return $days <= self::WARN_DAYS ? 'tomorrow' : null;
    }

    public static function label(string $urgency): string
    {
        return ['late' => 'Lewat deadline, belum diambil designer', 'today' => 'Deadline hari ini, belum diambil designer', 'tomorrow' => 'Deadline besok, belum diambil designer'][$urgency];
    }

    /** Reminders of this user, in the notification-feed shape (stable ids: each one is shown once). */
    public static function reminders(User $user): array
    {
        if (!Schema::hasTable('marketing_design_requests')) {
            return [];
        }
        $marketing = $user->hasRole('Marketing');
        $jobs = DesignRequest::with('designer:id,name')->where('status', DesignRequest::STATUS_BARU)
            ->whereNotNull('deadline')->whereDate('deadline', '<=', Carbon::today()->addDays(self::WARN_DAYS))
            ->get();
        if ($jobs->isEmpty()) {
            return [];
        }
        $pics = ContentPlan::whereIn('id', $jobs->pluck('content_plan_id')->filter())->pluck('assigned_to', 'id');

        $out = [];
        foreach ($jobs as $job) {
            $urgency = self::urgency($job->deadline, $job->status);
            if (!$urgency) {
                continue;
            }
            $mine = $job->designer_id
                ? (int) $job->designer_id === (int) $user->id
                : $marketing;
            $pic = $job->content_plan_id && (int) ($pics[$job->content_plan_id] ?? 0) === (int) $user->id;
            if (!$mine && !$pic) {
                continue;
            }
            $out[] = [
                'id' => 'dj-' . $job->id . '-' . $job->deadline->toDateString() . '-' . $urgency,
                'title' => self::label($urgency),
                'text' => '“' . $job->judul . '” · ' . ($job->designer ? $job->designer->name : 'belum ada designer'),
                'url' => url('/design-request') . '?open=' . $job->id,
                'tone' => $urgency === 'tomorrow' ? 'warning' : 'danger',
            ];
        }

        return $out;
    }
}
