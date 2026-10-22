<?php

namespace App\Support\Marketing;

use App\Models\Marketing\DesignRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Workload of the designers: active jobs per person (by status), deadlines this week, late jobs, the
 * deadlines of the next two weeks per day (to spot days where several jobs are due) and the queue
 * that nobody took yet.
 */
class DesignerCapacity
{
    public const DAYS_AHEAD = 14;

    /** Active jobs from which a designer counts as "sedang" / "padat". */
    public const LEVELS = ['sedang' => 3, 'padat' => 6];

    public static function build(): array
    {
        if (!Schema::hasTable('marketing_design_requests')) {
            return ['designers' => [], 'queue' => ['count' => 0, 'jobs' => []], 'days' => []];
        }
        $today = Carbon::today();
        $weekEnd = $today->copy()->endOfWeek();
        $days = [];
        for ($i = 0; $i < self::DAYS_AHEAD; $i++) {
            $days[] = $today->copy()->addDays($i)->toDateString();
        }

        $jobs = DesignRequest::whereIn('status', DesignRequest::ACTIVE_STATUSES)->orderBy('deadline')->get(['id', 'judul', 'jenis', 'status', 'deadline', 'designer_id']);
        $people = MarketingAccess::users()->keyBy('id');

        $designers = [];
        foreach ($people as $id => $user) {
            $mine = $jobs->where('designer_id', $id);
            $designers[$id] = self::summary($user->id, $user->name, $mine, $today, $weekEnd, $days);
        }
        // jobs of someone outside the Marketing list still count for that person
        foreach ($jobs->whereNotNull('designer_id')->pluck('designer_id')->unique() as $id) {
            if (!isset($designers[$id])) {
                $designers[$id] = self::summary($id, optional(\App\Models\User::find($id))->name ?? '?', $jobs->where('designer_id', $id), $today, $weekEnd, $days);
            }
        }

        $queue = $jobs->whereNull('designer_id')->values();

        return [
            // busiest first; people without any job last
            'designers' => collect($designers)->sortByDesc(fn ($d) => [$d['active'], $d['due_week']])->values()->all(),
            'queue' => [
                'count' => $queue->count(),
                'jobs' => $queue->take(8)->map(fn ($j) => self::job($j))->values(),
            ],
            'days' => $days,
            'levels' => self::LEVELS,
        ];
    }

    private static function summary(int $id, string $name, $mine, Carbon $today, Carbon $weekEnd, array $days): array
    {
        $byStatus = [];
        foreach (DesignRequest::ACTIVE_STATUSES as $s) {
            $byStatus[$s] = $mine->where('status', $s)->count();
        }
        $perDay = [];
        foreach ($days as $d) {
            $due = $mine->filter(fn ($j) => $j->deadline && $j->deadline->toDateString() === $d);
            $perDay[] = ['date' => $d, 'count' => $due->count(), 'jobs' => $due->pluck('judul')->values()];
        }
        $active = $mine->count();

        return [
            'id' => $id,
            'name' => $name,
            'active' => $active,
            'by_status' => $byStatus,
            'due_week' => $mine->filter(fn ($j) => $j->deadline && $j->deadline->between($today, $weekEnd))->count(),
            'late' => $mine->filter(fn ($j) => $j->deadline && $j->deadline->lt($today))->count(),
            'clashes' => collect($perDay)->where('count', '>=', 2)->count(),
            'level' => $active >= self::LEVELS['padat'] ? 'padat' : ($active >= self::LEVELS['sedang'] ? 'sedang' : 'ringan'),
            'per_day' => $perDay,
            'jobs' => $mine->take(10)->map(fn ($j) => self::job($j))->values(),
        ];
    }

    private static function job(DesignRequest $j): array
    {
        return ['id' => $j->id, 'judul' => $j->judul, 'jenis' => $j->jenis, 'status' => $j->status, 'deadline' => optional($j->deadline)->toDateString()];
    }
}
