<?php

namespace App\Http\Controllers;

use App\Models\DailyJournalRecurrence;
use App\Models\DailyJournalTask;
use App\Models\User;
use App\Notifications\DailyJournalReportSent;
use App\Notifications\DailyJournalTaskAssigned;
use App\Notifications\DailyJournalTaskCompleted;
use App\Support\DailyJournal\JournalTeam;
use App\Support\DailyJournal\RecurrenceGenerator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

/**
 * Daily Journal lives in a modal (partials/daily_journal_modal) opened from the sidebar on any page,
 * plus a reminder popup (partials/daily_journal_reminder). Everything except index()/modal() is JSON.
 */
class DailyJournalController extends Controller
{
    private const PERIODS = ['day', 'week', 'month'];

    private const OPEN_STATUSES = ['todo', 'in_progress'];

    /** Unfinished tasks without a deadline keep showing up for this many days. */
    private const CARRY_DAYS = 14;

    /** Shown in the reminder card (left bottom). */
    private const NOTIFICATION_TYPES = [DailyJournalTaskAssigned::class];

    /** Shown as toasts (right top) to managers / task givers, polled by the reminder partial. */
    private const TOAST_TYPES = [DailyJournalTaskCompleted::class, DailyJournalReportSent::class];

    public function __construct(private JournalTeam $team, private RecurrenceGenerator $recurrences)
    {
    }

    /** Old page URL (bookmarks, dashboard links): open the dashboard with the modal shown. */
    public function index(): RedirectResponse
    {
        return redirect()->to(url('/') . '?journal=1');
    }

    /**
     * Modal markup + script, fetched on first use. The sidebar asks for it with ?v=<file mtime>, so the
     * browser can keep it for a week and a deploy still gets the new version.
     */
    public function modal()
    {
        return response(view('partials.daily_journal_modal')->render())
            ->header('Cache-Control', 'private, max-age=604800');
    }

    /** Version for the modal URL (see modal()). */
    public static function modalVersion(): int
    {
        return (int) @filemtime(resource_path('views/partials/daily_journal_modal.blade.php'));
    }

    // ------------------------------------------------------------------ my tasks

    public function tasks(Request $request): JsonResponse
    {
        $user = Auth::user();
        // One indexed query, empty once today's copies exist
        $this->recurrences->generateFor($user->id);

        [$period, $date, $start, $end] = $this->resolvePeriod($request);
        $status = in_array($request->query('status'), DailyJournalTask::STATUSES, true) ? $request->query('status') : null;
        $today = now()->startOfDay();

        // One query for the period; the status chips are counted from it instead of a second query
        $all = $this->periodQuery([$user->id], $start, $end, $today)
            ->with(['fromUser:id,name', 'recurrence'])
            ->tap(fn ($query) => $this->applyOrdering($query))
            ->get();

        $tasks = $all
            ->when($status, fn ($items) => $items->where('status', $status))
            ->values()
            ->map(fn (DailyJournalTask $task) => $this->present($task, $start, $today, $user));

        $report = $period === 'day'
            ? DailyJournalTask::query()
                ->where('user_id', $user->id)
                ->whereDate('task_date', $date)
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN reported = 0 THEN 1 ELSE 0 END) as unreported')
                ->first()
            : null;

        return response()->json([
            'period' => $period,
            'date' => $date->toDateString(),
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'today' => $today->toDateString(),
            'label' => $this->label($period, $date, $start, $end),
            'is_current' => $this->range($period, $today)[0]->equalTo($start),
            'counts' => $this->countsPayload($all->countBy('status')),
            'tasks' => $tasks,
            'can_team' => $this->team->memberUserIds($user) !== null,
            'report' => $report ? [
                'total' => (int) $report->total,
                'unreported' => (int) $report->unreported,
            ] : null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validate($this->rules(true) + [
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'repeat' => ['nullable', Rule::in(DailyJournalRecurrence::PATTERNS)],
            'repeat_until' => ['nullable', 'date', 'after_or_equal:task_date'],
        ], [
            'title.required' => 'Nama task wajib diisi.',
            'deadline_date.after_or_equal' => 'Deadline tidak boleh sebelum tanggal task.',
            'repeat_until.after_or_equal' => 'Tanggal akhir pengulangan tidak boleh sebelum tanggal task.',
        ]);

        $ownerId = (int) $user->id;
        $assigneeId = (int) ($validated['assignee_id'] ?? 0);
        if ($assigneeId && $assigneeId !== $ownerId) {
            abort_unless($this->team->canManage($user, $assigneeId), 403, 'Anda tidak bisa memberi task ke user ini.');
            $ownerId = $assigneeId;
        }
        $fromUserId = $ownerId !== (int) $user->id ? (int) $user->id : null;
        $taskDate = Carbon::parse($validated['task_date'])->startOfDay();
        $repeat = $validated['repeat'] ?? null;

        $task = DB::transaction(function () use ($validated, $ownerId, $fromUserId, $taskDate, $repeat) {
            if (!$repeat) {
                return DailyJournalTask::create([
                    'user_id' => $ownerId,
                    'from_user_id' => $fromUserId,
                    'task_date' => $taskDate->toDateString(),
                    'deadline_date' => $validated['deadline_date'] ?? null,
                    'title' => trim($validated['title']),
                    'note' => $validated['note'] ?? null,
                    'scheduled_time' => $validated['scheduled_time'] ?? null,
                    'status' => $validated['status'] ?? 'todo',
                ]);
            }

            // Repeating: the template carries the schedule; a deadline makes no sense on every copy
            $recurrence = DailyJournalRecurrence::create([
                'user_id' => $ownerId,
                'from_user_id' => $fromUserId,
                'title' => trim($validated['title']),
                'note' => $validated['note'] ?? null,
                'scheduled_time' => $validated['scheduled_time'] ?? null,
                'pattern' => $repeat,
                'weekday' => $taskDate->isoWeekday(),
                'month_day' => $taskDate->day,
                'start_date' => $taskDate->toDateString(),
                'end_date' => $validated['repeat_until'] ?? null,
                'last_generated_date' => $taskDate->isToday() ? $taskDate->toDateString() : null,
            ]);

            // First copy right away when the start day matches (e.g. "hari kerja" starting on a Saturday does not)
            return $recurrence->matches($taskDate) ? $this->recurrences->createTask($recurrence, $taskDate) : null;
        });

        if ($fromUserId) {
            $owner = User::find($ownerId);
            if ($owner && $task) {
                $owner->notify(new DailyJournalTaskAssigned($task, $user->name, $task->recurrence?->label()));
            }
        }

        $this->forgetNavBadges($ownerId);

        // The modal inserts the returned task into its list instead of reloading it
        return response()->json([
            'message' => $fromUserId ? 'Task diberikan.' : 'Task ditambahkan.',
            'id' => $task?->id,
            'task' => $task ? $this->present($task->fresh(['fromUser:id,name', 'recurrence']), $taskDate, now()->startOfDay(), $user) : null,
        ], 201);
    }

    public function update(Request $request, DailyJournalTask $dailyJournalTask): JsonResponse
    {
        $this->authorizeTask($dailyJournalTask);

        $validated = $request->validate($this->rules(false));
        abort_if($validated === [], 422, 'Tidak ada perubahan.');

        // Checked only when the deadline itself is submitted: moving an overdue task to today keeps its old deadline
        if (array_key_exists('deadline_date', $validated) && $validated['deadline_date']) {
            $taskDate = $validated['task_date'] ?? $dailyJournalTask->task_date?->toDateString();
            if ($taskDate && Carbon::parse($validated['deadline_date'])->lt(Carbon::parse($taskDate))) {
                return response()->json([
                    'message' => 'Deadline tidak boleh sebelum tanggal task.',
                    'errors' => ['deadline_date' => ['Deadline tidak boleh sebelum tanggal task.']],
                ], 422);
            }
        }

        if (isset($validated['title'])) {
            $validated['title'] = trim($validated['title']);
        }

        $wasDone = $dailyJournalTask->status === 'done';
        $dailyJournalTask->update($validated);

        if (!$wasDone && $dailyJournalTask->status === 'done') {
            $this->notifyCompleted($dailyJournalTask, Auth::user());
        }
        $this->forgetNavBadges($dailyJournalTask->user_id);

        // view_start: first day of the list the modal shows, to flag the task as carried over or not
        $viewStart = $this->parseDate($request->input('view_start')) ?? $dailyJournalTask->task_date ?? now()->startOfDay();

        return response()->json([
            'message' => 'Task diperbarui.',
            'task' => $this->present($dailyJournalTask->fresh(['fromUser:id,name', 'recurrence']), $viewStart, now()->startOfDay(), Auth::user()),
        ]);
    }

    public function destroy(DailyJournalTask $dailyJournalTask): JsonResponse
    {
        $this->authorizeTask($dailyJournalTask);
        abort_unless($this->canDelete($dailyJournalTask, Auth::user()), 422, 'Hanya task "Belum mulai" yang bisa dihapus. Task dari atasan hanya bisa dihapus oleh pemberinya.');

        $dailyJournalTask->delete();
        $this->forgetNavBadges($dailyJournalTask->user_id);

        return response()->json(['message' => 'Task dihapus.']);
    }

    // ------------------------------------------------------------------ team


    /**
     * Team progress for the period: per member counts only (one grouped query). A member's tasks are
     * fetched separately when their card is opened, so the payload stays small for HRD / Admin.
     */
    public function team(Request $request): JsonResponse
    {
        $user = Auth::user();
        $memberIds = $this->team->memberUserIds($user);
        abort_if($memberIds === null, 403, 'Anda tidak memiliki tim.');

        [$period, $date, $start, $end] = $this->resolvePeriod($request);
        $today = now()->startOfDay();

        $rows = $this->periodQuery($memberIds === true ? null : ($memberIds ?: [0]), $start, $end, $today)
            ->where('user_id', '!=', $user->id)
            ->selectRaw('user_id, status, COUNT(*) as total')
            ->selectRaw(
                'SUM(CASE WHEN deadline_date IS NOT NULL AND deadline_date < ? AND status IN (?, ?) THEN 1 ELSE 0 END) as overdue',
                [$today->toDateString(), ...self::OPEN_STATUSES]
            )
            ->groupBy('user_id', 'status')
            ->get()
            ->groupBy('user_id');

        // Everyone (Hrd / Admin / Ceo): only active employees who have something in the period, to keep the list readable
        // (subordinate lists are cached, so they are filtered again for someone deactivated since)
        $userIds = array_values(array_intersect(
            $memberIds === true ? $rows->keys()->map(fn ($id) => (int) $id)->all() : $memberIds,
            $this->team->activeUserIds()
        ));

        $members = User::query()
            ->select(['id', 'name'])
            ->with(['employee:id,user_id', 'employee.position:id,name'])
            ->whereIn('id', $userIds ?: [0])
            ->get()
            ->map(function (User $member) use ($rows) {
                $own = $rows->get($member->id, collect());
                $counts = $this->countsPayload($own->pluck('total', 'status'));
                $countable = $counts['all'] - $counts['skipped'];

                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'position' => $member->employee?->position?->name,
                    'counts' => $counts,
                    'overdue' => (int) $own->sum('overdue'),
                    'progress' => $countable > 0 ? (int) round($counts['done'] / $countable * 100) : null,
                ];
            })
            ->sort(fn ($a, $b) => [$b['overdue'], $b['counts']['all']] <=> [$a['overdue'], $a['counts']['all']] ?: strcasecmp($a['name'], $b['name']))
            ->values();

        return response()->json([
            'period' => $period,
            'date' => $date->toDateString(),
            'label' => $this->label($period, $date, $start, $end),
            'is_current' => $this->range($period, $today)[0]->equalTo($start),
            'members' => $members,
        ]);
    }

    /** One team member's tasks for the period (opened member card). */
    public function teamTasks(Request $request, User $member): JsonResponse
    {
        $user = Auth::user();
        abort_unless($this->team->canManage($user, (int) $member->id), 403, 'Bukan anggota tim Anda.');

        [, , $start, $end] = $this->resolvePeriod($request);
        $today = now()->startOfDay();

        $tasks = $this->periodQuery([$member->id], $start, $end, $today)
            ->with(['fromUser:id,name', 'recurrence'])
            ->tap(fn ($query) => $this->applyOrdering($query))
            ->get()
            ->map(fn (DailyJournalTask $task) => $this->present($task, $start, $today, $user));

        return response()->json(['tasks' => $tasks]);
    }

    // ------------------------------------------------------------------ recurring

    public function recurrenceIndex(): JsonResponse
    {
        $userId = Auth::id();

        $items = DailyJournalRecurrence::query()
            ->with('user:id,name')
            ->where(fn ($q) => $q->where('user_id', $userId)->orWhere('from_user_id', $userId))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', now()->toDateString()))
            ->orderBy('title')
            ->get()
            ->map(fn (DailyJournalRecurrence $r) => [
                'id' => $r->id,
                'title' => $r->title,
                'note' => $r->note,
                'scheduled_time' => $r->scheduled_time ? substr((string) $r->scheduled_time, 0, 5) : null,
                'label' => $r->label(),
                'start_date' => $r->start_date?->toDateString(),
                'end_date' => $r->end_date?->toDateString(),
                'for' => (int) $r->user_id !== (int) $userId ? $r->user?->name : null,
            ]);

        return response()->json(['data' => $items]);
    }

    /** Stops a repetition; tasks already created stay. */
    public function recurrenceDestroy(DailyJournalRecurrence $recurrence): JsonResponse
    {
        $userId = (int) Auth::id();
        abort_unless((int) $recurrence->user_id === $userId || (int) $recurrence->from_user_id === $userId, 404);

        $recurrence->delete();

        return response()->json(['message' => 'Pengulangan dihentikan.']);
    }

    // ------------------------------------------------------------------ report

    /** Sends the day's summary to the direct manager(s) and marks those tasks reported. */
    public function report(Request $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validate(['date' => ['required', 'date_format:Y-m-d']]);

        $tasks = DailyJournalTask::query()
            ->where('user_id', $user->id)
            ->whereDate('task_date', $validated['date'])
            ->tap(fn ($query) => $this->applyOrdering($query))
            ->get();
        abort_if($tasks->isEmpty(), 422, 'Belum ada task di tanggal ini.');

        $managerIds = $this->team->managerUserIds($user);
        abort_if(empty($managerIds), 422, 'Atasan Anda belum diatur di struktur posisi HRD.');

        $counts = $tasks->countBy('status');
        $managers = User::query()->whereIn('id', $managerIds)->get();

        Notification::send($managers, new DailyJournalReportSent(
            $user->id,
            $user->name,
            $validated['date'],
            [
                'total' => $tasks->count(),
                'done' => (int) ($counts['done'] ?? 0),
                'in_progress' => (int) ($counts['in_progress'] ?? 0),
                'todo' => (int) ($counts['todo'] ?? 0),
                'skipped' => (int) ($counts['skipped'] ?? 0),
            ],
            $tasks->take(30)->map(fn ($task) => ['title' => $task->title, 'status' => $task->status])->values()->all(),
        ));

        DailyJournalTask::query()->whereIn('id', $tasks->pluck('id'))->update(['reported' => true]);

        return response()->json([
            'message' => 'Laporan terkirim ke ' . $managers->pluck('name')->implode(', ') . '.',
        ]);
    }

    // ------------------------------------------------------------------ reminder popup

    public function reminders(): JsonResponse
    {
        $user = Auth::user();
        $today = now()->toDateString();

        // Runs on page loads (throttled client side): one aggregate query + the unread notifications
        $counts = DailyJournalTask::query()
            ->where('user_id', $user->id)
            ->whereIn('status', self::OPEN_STATUSES)
            ->whereNotNull('deadline_date')
            ->where('deadline_date', '<=', $today)
            ->selectRaw('SUM(CASE WHEN deadline_date = ? THEN 1 ELSE 0 END) as due_today', [$today])
            ->selectRaw('SUM(CASE WHEN deadline_date < ? THEN 1 ELSE 0 END) as overdue', [$today])
            ->first();

        $notifications = $user->unreadNotifications()
            ->whereIn('type', self::NOTIFICATION_TYPES)
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'kind' => $n->data['type'] ?? '',
                'title' => $n->data['title'] ?? '',
                'message' => $n->data['message'] ?? '',
                'date' => $n->data['date'] ?? null,
                'created_at' => $n->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'today' => $today,
            'due_today' => (int) ($counts->due_today ?? 0),
            'overdue' => (int) ($counts->overdue ?? 0),
            'notifications' => $notifications,
        ]);
    }

    /**
     * Toast feed for managers / task givers: unread "task selesai" and "laporan harian", returned once
     * (marked read here). poll=false tells people without a team to stop asking.
     */
    public function updates(): JsonResponse
    {
        $user = Auth::user();

        $items = $user->unreadNotifications()
            ->whereIn('type', self::TOAST_TYPES)
            ->oldest()
            ->limit(10)
            ->get();

        if ($items->isNotEmpty()) {
            $user->unreadNotifications()->whereIn('id', $items->pluck('id'))->update(['read_at' => now()]);
        }

        return response()->json([
            'poll' => $items->isNotEmpty() || $this->team->memberUserIds($user) !== null,
            'items' => $items->map(fn ($n) => [
                'kind' => $n->data['type'] ?? '',
                'title' => $n->data['title'] ?? '',
                'message' => $n->data['message'] ?? '',
                'date' => $n->data['date'] ?? null,
            ])->values(),
        ]);
    }

    public function readReminders(Request $request): JsonResponse
    {
        $ids = array_filter((array) $request->input('ids', []), 'is_string');

        if ($ids) {
            Auth::user()->unreadNotifications()
                ->whereIn('type', self::NOTIFICATION_TYPES)
                ->whereIn('id', $ids)
                ->update(['read_at' => now()]);
        }

        return response()->json(['ok' => true]);
    }

    // ------------------------------------------------------------------ helpers

    /**
     * Tasks dated in the period, plus unfinished ones carried over from earlier days (when the
     * period reaches today): any with a deadline, and recent ones without (repeating copies excluded,
     * today's copy replaces them).
     */
    private function periodQuery(?array $userIds, Carbon $start, Carbon $end, Carbon $today): Builder
    {
        $carry = $end->gte($today);

        return DailyJournalTask::query()
            ->when($userIds !== null, fn ($q) => $q->whereIn('user_id', $userIds))
            ->where(function ($query) use ($start, $end, $carry) {
                $query->whereBetween('task_date', [$start->toDateString(), $end->toDateString()]);

                if ($carry) {
                    $query->orWhere(function ($carried) use ($start) {
                        $carried->where('task_date', '<', $start->toDateString())
                            ->whereIn('status', self::OPEN_STATUSES)
                            ->where(function ($which) use ($start) {
                                $which->whereNotNull('deadline_date')
                                    ->orWhere(function ($recent) use ($start) {
                                        $recent->whereNull('recurrence_id')
                                            ->where('task_date', '>=', $start->copy()->subDays(self::CARRY_DAYS)->toDateString());
                                    });
                            });
                    });
                }
            });
    }

    private function applyOrdering(Builder $query): void
    {
        $query->orderByRaw("CASE WHEN status IN ('done', 'skipped') THEN 1 ELSE 0 END")
            ->orderBy('task_date')
            ->orderByRaw('CASE WHEN scheduled_time IS NULL THEN 1 ELSE 0 END')
            ->orderBy('scheduled_time')
            ->orderBy('id');
    }

    private function countsPayload($counts): array
    {
        return [
            'all' => (int) collect($counts)->sum(),
            'todo' => (int) ($counts['todo'] ?? 0),
            'in_progress' => (int) ($counts['in_progress'] ?? 0),
            'done' => (int) ($counts['done'] ?? 0),
            'skipped' => (int) ($counts['skipped'] ?? 0),
        ];
    }

    private function rules(bool $creating): array
    {
        $required = $creating ? 'required' : 'sometimes';

        return [
            'task_date' => [$required, 'date'],
            'deadline_date' => ['nullable', 'date', ...($creating ? ['after_or_equal:task_date'] : [])],
            'title' => [$required, 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:2000'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'status' => [$creating ? 'nullable' : 'sometimes', Rule::in(DailyJournalTask::STATUSES)],
        ];
    }

    private function present(DailyJournalTask $task, Carbon $rangeStart, Carbon $today, User $viewer): array
    {
        $open = in_array($task->status, self::OPEN_STATUSES, true);

        return [
            'id' => $task->id,
            'title' => $task->title,
            'note' => $task->note,
            'status' => $task->status,
            'task_date' => $task->task_date?->toDateString(),
            'deadline_date' => $task->deadline_date?->toDateString(),
            'scheduled_time' => $task->scheduled_time ? substr((string) $task->scheduled_time, 0, 5) : null,
            'from' => $task->from_user_id && (int) $task->from_user_id !== (int) $task->user_id ? $task->fromUser?->name : null,
            'repeat' => $task->recurrence_id ? ($task->relationLoaded('recurrence') ? $task->recurrence?->label() : 'Berulang') : null,
            'reported' => (bool) $task->reported,
            'overdue' => $open && $task->deadline_date && $task->deadline_date->lt($today),
            'due_today' => $open && $task->deadline_date && $task->deadline_date->isSameDay($today),
            'carried' => $task->task_date && $task->task_date->lt($rangeStart),
            'can_edit' => $this->canEdit($task, $viewer),
            'can_delete' => $this->canDelete($task, $viewer),
        ];
    }

    private function canEdit(DailyJournalTask $task, User $user): bool
    {
        return (int) $task->user_id === (int) $user->id || (int) $task->from_user_id === (int) $user->id;
    }

    /** Only tasks not started yet; a task given by a manager can only be removed by that manager. */
    private function canDelete(DailyJournalTask $task, User $user): bool
    {
        if ($task->status !== 'todo') {
            return false;
        }

        $assignedByOther = $task->from_user_id && (int) $task->from_user_id !== (int) $task->user_id;

        return $assignedByOther
            ? (int) $task->from_user_id === (int) $user->id
            : (int) $task->user_id === (int) $user->id;
    }

    private function authorizeTask(DailyJournalTask $task): void
    {
        abort_unless($this->canEdit($task, Auth::user()), 404);
    }

    /**
     * Tells the task giver (if someone else gave it) and the direct manager(s) that a task is done.
     * Toggling done → not done → done again does not notify twice while the first one is unread.
     */
    private function notifyCompleted(DailyJournalTask $task, User $doer): void
    {
        $owner = (int) $task->user_id === (int) $doer->id ? $doer : User::find($task->user_id);
        if (!$owner) {
            return;
        }

        $recipientIds = collect($this->team->managerUserIds($owner))
            ->push($task->from_user_id ? (int) $task->from_user_id : null)
            ->filter(fn ($id) => $id && $id !== (int) $doer->id)
            ->unique()
            ->values();
        if ($recipientIds->isEmpty()) {
            return;
        }

        $alreadyTold = DB::table('notifications')
            ->where('type', DailyJournalTaskCompleted::class)
            ->whereNull('read_at')
            ->whereIn('notifiable_id', $recipientIds)
            ->where('data->task_id', $task->id)
            ->pluck('notifiable_id')
            ->map(fn ($id) => (int) $id);

        $recipients = User::query()->whereIn('id', $recipientIds->diff($alreadyTold))->get();
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new DailyJournalTaskCompleted($task, $owner->name));
        }
    }

    /** Sidebar badge (unfinished tasks with a deadline) is cached per user. */
    private function forgetNavBadges(int $userId): void
    {
        Cache::forget('main_nav_badges:' . $userId);
    }

    /** @return array{0: string, 1: Carbon, 2: Carbon, 3: Carbon} period, date, start, end */
    private function resolvePeriod(Request $request): array
    {
        $period = in_array($request->query('period'), self::PERIODS, true) ? $request->query('period') : 'day';
        $date = $this->parseDate($request->query('date')) ?? now()->startOfDay();
        [$start, $end] = $this->range($period, $date);

        return [$period, $date, $start, $end];
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function range(string $period, Carbon $date): array
    {
        return match ($period) {
            'week' => [$date->copy()->startOfWeek(Carbon::MONDAY), $date->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay()],
            'month' => [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()->startOfDay()],
            default => [$date->copy(), $date->copy()],
        };
    }

    private function label(string $period, Carbon $date, Carbon $start, Carbon $end): string
    {
        return match ($period) {
            'week' => $start->translatedFormat('d M') . ' – ' . $end->translatedFormat('d M Y'),
            'month' => $date->translatedFormat('F Y'),
            default => $date->isToday() ? 'Hari ini, ' . $date->translatedFormat('d M Y') : $date->translatedFormat('l, d M Y'),
        };
    }
}
