<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\DailyJournalTask;
use App\Models\HRD\Employee;
use App\Models\User;
use App\Support\Dashboard\Fmt;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Daily Journal tasks of the period (plus every unfinished task with a deadline). Managers can switch
 * to their team: Hrd / Admin / Ceo see everyone, other managers their direct subordinate positions.
 */
class DailyJournalWidget implements Widget
{
    private const ALL_TASK_ROLES = ['Hrd', 'Admin', 'Ceo'];

    private const LIST_LIMIT = 7;

    public function data(User $user, array $filter): array
    {
        $start = $filter['period_start']->copy()->startOfDay();
        $end = $filter['period_end']->copy()->endOfDay();
        $today = Carbon::today();

        $teamUserIds = $this->teamUserIds($user);
        $canTeam = $teamUserIds !== null;
        $mode = $canTeam && request()->input('daily_journal_mode') === 'team' ? 'team' : 'my';

        $query = DailyJournalTask::query()
            ->where(function (Builder $q) use ($start, $end) {
                $q->whereBetween('task_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhere(fn (Builder $d) => $d->whereNotNull('deadline_date')->where('status', '!=', 'done'));
            });

        if ($mode === 'team') {
            // null = everyone (Hrd / Admin / Ceo)
            if ($teamUserIds !== true) {
                $query->whereIn('user_id', $teamUserIds ?: [0]);
            }
        } else {
            $query->where('user_id', $user->id);
        }

        $counts = (clone $query)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $total = (int) $counts->sum();
        $overdue = (clone $query)
            ->whereNotNull('deadline_date')
            ->whereDate('deadline_date', '<', $today)
            ->whereNotIn('status', ['done', 'skipped'])
            ->count();

        $tasks = (clone $query)
            ->with(['user:id,name', 'fromUser:id,name'])
            ->orderByRaw("CASE WHEN status IN ('done', 'skipped') THEN 1 ELSE 0 END")
            ->orderByRaw('CASE WHEN deadline_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('deadline_date')
            ->orderByDesc('task_date')
            ->orderByRaw('CASE WHEN scheduled_time IS NULL THEN 1 ELSE 0 END')
            ->orderBy('scheduled_time')
            ->limit(self::LIST_LIMIT)
            ->get()
            ->map(fn ($task) => (object) [
                'title' => $task->title,
                'status' => $task->status,
                'owner' => $mode === 'team' ? $task->user?->name : null,
                'from' => $task->from_user_id && $task->from_user_id !== $task->user_id ? $task->fromUser?->name : null,
                'date' => $task->task_date,
                'time' => $task->scheduled_time ? substr((string) $task->scheduled_time, 0, 5) : null,
                'deadline' => $task->deadline_date,
                'overdue' => $task->deadline_date && $task->deadline_date->lt($today) && !in_array($task->status, ['done', 'skipped'], true),
            ]);

        $done = (int) ($counts['done'] ?? 0);

        return [
            'periodLabel' => $start->translatedFormat('d M') . ' – ' . $end->translatedFormat('d M Y'),
            'mode' => $mode,
            'canTeam' => $canTeam,
            'total' => $total,
            'done' => $done,
            'progress' => Fmt::share($done, $total - (int) ($counts['skipped'] ?? 0)),
            'segments' => [
                ['label' => 'Selesai', 'value' => Fmt::num($done), 'pct' => Fmt::share($done, $total), 'color' => Fmt::SERIES[2]],
                ['label' => 'Dikerjakan', 'value' => Fmt::num($counts['in_progress'] ?? 0), 'pct' => Fmt::share($counts['in_progress'] ?? 0, $total), 'color' => Fmt::SERIES[0]],
                ['label' => 'Belum mulai', 'value' => Fmt::num($counts['todo'] ?? 0), 'pct' => Fmt::share($counts['todo'] ?? 0, $total), 'color' => Fmt::SERIES[1]],
                ['label' => 'Dilewati', 'value' => Fmt::num($counts['skipped'] ?? 0), 'pct' => Fmt::share($counts['skipped'] ?? 0, $total), 'color' => Fmt::OTHER],
            ],
            'overdue' => $overdue,
            'tasks' => $tasks->all(),
            'journalUrl' => route('daily-journal.index', [
                'filter' => 'custom',
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ]),
        ];
    }

    /** true = may see everyone's tasks, list = team user ids, null = no team view. */
    private function teamUserIds(User $user): array|bool|null
    {
        if ($user->hasAnyRole(self::ALL_TASK_ROLES)) {
            return true;
        }

        $isManager = $user->getRoleNames()->contains(fn ($role) => str_contains(strtolower($role), 'manager'));
        if (!$isManager) {
            return null;
        }

        $positionIds = $user->employee?->positions()->pluck('hrd_position.id')->filter()->all() ?? [];
        if (empty($positionIds)) {
            return [];
        }

        return Employee::query()
            ->whereNotNull('user_id')
            ->whereHas('positions', fn (Builder $q) => $q->whereIn('hrd_position.parent_id', $positionIds))
            ->pluck('user_id')
            ->unique()
            ->values()
            ->all();
    }
}
