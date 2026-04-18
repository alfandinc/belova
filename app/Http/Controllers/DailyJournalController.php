<?php

namespace App\Http\Controllers;

use App\Models\DailyJournalTask;
use App\Models\HRD\Employee;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DailyJournalController extends Controller
{
    private const FILTERS = [
        'today',
        'week',
        'month',
        'year',
        'custom',
    ];

    public function index(Request $request): View
    {
        $selectedDate = $this->resolveSelectedDate($request->query('date'));
        $filter = in_array($request->query('filter'), self::FILTERS, true) ? $request->query('filter') : 'today';
        $selectedStatus = in_array($request->query('status'), DailyJournalTask::STATUSES, true)
            ? $request->query('status')
            : null;
        $weekStart = $selectedDate->copy()->startOfWeek(Carbon::MONDAY);
        $weekDays = collect(range(0, 6))->map(function (int $offset) use ($weekStart) {
            return $weekStart->copy()->addDays($offset);
        });
        [$rangeStart, $rangeEnd] = $this->resolveDateRange($request, $selectedDate, $filter);
        $filterLabel = $this->resolveFilterLabel($filter);
        $periodTitle = $this->resolvePeriodTitle($filter, $selectedDate, $rangeStart, $rangeEnd);
        $periodDescription = $this->resolvePeriodDescription($filter, $selectedDate, $rangeStart, $rangeEnd);

        $tasksQuery = DailyJournalTask::query()
            ->with([
                'fromUser:id,name',
                'fromUser.employee:id,user_id,photo',
            ])
            ->where('user_id', Auth::id())
            ->where(function ($query) use ($rangeStart, $rangeEnd) {
                $query->whereBetween('task_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
                    ->orWhere(function ($deadlineQuery) {
                        $deadlineQuery->whereNotNull('deadline_date')
                            ->where('status', '!=', 'done');
                    });
            });

        $statusCounts = [
            'todo' => (clone $tasksQuery)->where('status', 'todo')->count(),
            'in_progress' => (clone $tasksQuery)->where('status', 'in_progress')->count(),
            'done' => (clone $tasksQuery)->where('status', 'done')->count(),
            'skipped' => (clone $tasksQuery)->where('status', 'skipped')->count(),
        ];

        if ($selectedStatus) {
            $tasksQuery->where('status', $selectedStatus);
        }

        $tasks = $tasksQuery
            ->orderByDesc('task_date')
            ->orderByRaw('CASE WHEN scheduled_time IS NULL THEN 1 ELSE 0 END')
            ->orderBy('scheduled_time')
            ->orderByDesc('id')
            ->get();

        return view('daily_journal.index', [
            'filter' => $filter,
            'filterLabel' => $filterLabel,
            'periodTitle' => $periodTitle,
            'periodDescription' => $periodDescription,
            'selectedDate' => $selectedDate,
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
            'tasks' => $tasks,
            'weekDays' => $weekDays,
            'statusCounts' => $statusCounts,
            'selectedStatus' => $selectedStatus,
            'statusOptions' => DailyJournalTask::STATUSES,
        ]);
    }

    public function divisionIndex(Request $request): View
    {
        $actor = Auth::user();
        $canViewAllTasks = $actor?->hasRole('Hrd') || $actor?->hasRole('Admin');
        $canAssignTasks = $actor?->hasRole('Manager') || $canViewAllTasks;

        abort_unless($canAssignTasks || $canViewAllTasks, 403);

        $selectedDate = $this->resolveSelectedDate($request->query('date'));
        $filter = in_array($request->query('filter'), self::FILTERS, true) ? $request->query('filter') : 'today';
        $selectedStatus = in_array($request->query('status'), DailyJournalTask::STATUSES, true)
            ? $request->query('status')
            : null;
        $weekStart = $selectedDate->copy()->startOfWeek(Carbon::MONDAY);
        $weekDays = collect(range(0, 6))->map(function (int $offset) use ($weekStart) {
            return $weekStart->copy()->addDays($offset);
        });
        [$rangeStart, $rangeEnd] = $this->resolveDateRange($request, $selectedDate, $filter);

        $divisionId = optional($actor->employee)->division_id;
        $divisionName = $canViewAllTasks
            ? 'All Divisions'
            : optional(optional($actor->employee)->division)->name;

        $divisionMembers = collect();
        $selectedUserId = null;
        $tasks = collect();
        $totalCount = 0;
        $statusCounts = [
            'todo' => 0,
            'in_progress' => 0,
            'done' => 0,
            'skipped' => 0,
        ];

        if ($canViewAllTasks || $divisionId) {
            $divisionMembers = Employee::query()
                ->when(!$canViewAllTasks, function ($query) use ($divisionId) {
                    $query->where('division_id', $divisionId);
                })
                ->whereNotNull('user_id')
                ->with('user:id,name')
                ->get()
                ->filter(fn (Employee $employee) => $employee->user !== null)
                ->unique('user_id')
                ->sortBy(fn (Employee $employee) => strtolower($employee->user->name))
                ->values();

            $memberUserIds = $divisionMembers->pluck('user_id')->filter()->unique()->values();
            $requestedUserId = (int) $request->query('user_id', 0);
            $selectedUserId = $memberUserIds->contains($requestedUserId) ? $requestedUserId : null;

            $tasksQuery = DailyJournalTask::query()
                ->with([
                    'user:id,name',
                    'user.employee:id,user_id,photo',
                    'fromUser:id,name',
                    'fromUser.employee:id,user_id,photo',
                ])
                ->when(!$canViewAllTasks, function ($query) use ($memberUserIds) {
                    $query->whereIn('user_id', $memberUserIds->all());
                })
                ->where(function ($query) use ($rangeStart, $rangeEnd) {
                    $query->whereBetween('task_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
                        ->orWhere(function ($deadlineQuery) {
                            $deadlineQuery->whereNotNull('deadline_date')
                                ->where('status', '!=', 'done');
                        });
                });

            if ($selectedUserId) {
                $tasksQuery->where('user_id', $selectedUserId);
            }

            $totalCount = (clone $tasksQuery)->count();

            $statusCounts = [
                'todo' => (clone $tasksQuery)->where('status', 'todo')->count(),
                'in_progress' => (clone $tasksQuery)->where('status', 'in_progress')->count(),
                'done' => (clone $tasksQuery)->where('status', 'done')->count(),
                'skipped' => (clone $tasksQuery)->where('status', 'skipped')->count(),
            ];

            if ($selectedStatus) {
                $tasksQuery->where('status', $selectedStatus);
            }

            $tasks = $tasksQuery
                ->orderByDesc('task_date')
                ->orderByRaw('CASE WHEN scheduled_time IS NULL THEN 1 ELSE 0 END')
                ->orderBy('scheduled_time')
                ->orderByDesc('id')
                ->get();
        }

        return view('daily_journal.division_index', [
            'filter' => $filter,
            'filterLabel' => $this->resolveFilterLabel($filter),
            'periodTitle' => $this->resolvePeriodTitle($filter, $selectedDate, $rangeStart, $rangeEnd),
            'periodDescription' => $this->resolvePeriodDescription($filter, $selectedDate, $rangeStart, $rangeEnd),
            'selectedDate' => $selectedDate,
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
            'weekDays' => $weekDays,
            'tasks' => $tasks,
            'totalCount' => $totalCount,
            'statusCounts' => $statusCounts,
            'selectedStatus' => $selectedStatus,
            'statusOptions' => DailyJournalTask::STATUSES,
            'divisionMembers' => $divisionMembers,
            'selectedUserId' => $selectedUserId,
            'divisionName' => $divisionName,
            'canAssignTasks' => $canAssignTasks,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'integer'],
            'task_date' => ['required', 'date'],
            'deadline_date' => ['nullable', 'date', 'after_or_equal:task_date'],
            'title' => ['required', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:180'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'status' => ['required', Rule::in(DailyJournalTask::STATUSES)],
            'color_theme' => ['nullable', Rule::in(DailyJournalTask::THEMES)],
            'icon' => ['nullable', 'string', 'max:16'],
        ]);

        $actor = Auth::user();
        $userId = Auth::id();
        $fromUserId = null;
        $canViewAllTasks = $actor?->hasRole('Hrd') || $actor?->hasRole('Admin');

        if ($canViewAllTasks && !empty($validated['user_id'])) {
            $userId = (int) $validated['user_id'];
            $fromUserId = $actor->id;
        } elseif ($actor?->hasRole('Manager') && !empty($validated['user_id'])) {
            $managerDivisionId = optional($actor->employee)->division_id;

            $divisionMemberIds = Employee::query()
                ->where('division_id', $managerDivisionId)
                ->whereNotNull('user_id')
                ->pluck('user_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->all();

            $requestedUserId = (int) $validated['user_id'];
            abort_unless(in_array($requestedUserId, $divisionMemberIds, true), 403);

            $userId = $requestedUserId;
            $fromUserId = $actor->id;
        }

        $themeIndex = DailyJournalTask::query()->where('user_id', $userId)->count() % count(DailyJournalTask::THEMES);

        DailyJournalTask::create([
            'user_id' => $userId,
            'from_user_id' => $fromUserId,
            'task_date' => $validated['task_date'],
            'deadline_date' => $validated['deadline_date'] ?? null,
            'title' => $validated['title'],
            'note' => $validated['note'] ?? null,
            'scheduled_time' => $validated['scheduled_time'] ?? null,
            'status' => $validated['status'],
            'color_theme' => $validated['color_theme'] ?? DailyJournalTask::THEMES[$themeIndex],
            'icon' => $validated['icon'] ?: '📝',
        ]);

        if ($fromUserId) {
            return redirect()
                ->route('daily-journal.division.index', [
                    'filter' => $request->input('redirect_filter', $request->input('filter', 'today')),
                    'date' => $request->input('redirect_date', $validated['task_date']),
                    'start_date' => $request->input('redirect_start_date', $request->input('start_date')),
                    'end_date' => $request->input('redirect_end_date', $request->input('end_date')),
                    'user_id' => $request->input('redirect_user_id', $request->input('user_id')),
                    'status' => $request->input('redirect_status', $request->input('status_filter')),
                ])
                ->with('success', 'Task berhasil diberikan ke employee.');
        }

        return redirect()
            ->route('daily-journal.index', $this->buildRedirectParams($request, $validated['task_date']))
            ->with('success', 'Task Daily Journal berhasil ditambahkan.');
    }

    public function update(Request $request, DailyJournalTask $dailyJournalTask): RedirectResponse
    {
        $this->authorizeTask($dailyJournalTask);

        $validated = $request->validate([
            'status' => [$request->exists('status') ? 'required' : 'nullable', Rule::in(DailyJournalTask::STATUSES)],
            'title' => [$request->exists('title') ? 'required' : 'nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:180'],
            'icon' => ['nullable', 'string', 'max:16'],
            'color_theme' => [$request->exists('color_theme') ? 'required' : 'nullable', Rule::in(DailyJournalTask::THEMES)],
        ]);

        $updates = [];
        $successMessage = 'Task berhasil diperbarui.';

        if ($request->exists('status')) {
            $updates['status'] = $validated['status'];
            $successMessage = 'Status task berhasil diperbarui.';
        }

        if ($request->exists('title')) {
            $updates['title'] = $validated['title'];
            $successMessage = 'Task berhasil diperbarui.';
        }

        if ($request->exists('note')) {
            $updates['note'] = $validated['note'] ?? null;
            $successMessage = 'Task berhasil diperbarui.';
        }

        if ($request->exists('icon')) {
            $updates['icon'] = $validated['icon'] ?: '📝';
            $successMessage = 'Task berhasil diperbarui.';
        }

        if ($request->exists('color_theme')) {
            $updates['color_theme'] = $validated['color_theme'];
            $successMessage = 'Task berhasil diperbarui.';
        }

        abort_if($updates === [], 422);

        $dailyJournalTask->update($updates);

        return redirect()
            ->route('daily-journal.index', $this->buildRedirectParams(
                $request,
                $request->input('date', optional($dailyJournalTask->task_date)->toDateString())
            ))
            ->with('success', $successMessage);
    }

    public function destroy(Request $request, DailyJournalTask $dailyJournalTask): RedirectResponse
    {
        $this->authorizeTask($dailyJournalTask);

        $taskDate = optional($dailyJournalTask->task_date)->toDateString();
        $dailyJournalTask->delete();

        return redirect()
            ->route('daily-journal.index', $this->buildRedirectParams(
                $request,
                $request->input('date', $taskDate)
            ))
            ->with('success', 'Task berhasil dihapus.');
    }

    private function authorizeTask(DailyJournalTask $dailyJournalTask): void
    {
        abort_if($dailyJournalTask->user_id !== Auth::id(), 404);
    }

    private function resolveSelectedDate(?string $date): Carbon
    {
        if (!$date) {
            return now()->startOfDay();
        }

        try {
            return Carbon::parse($date)->startOfDay();
        } catch (\Throwable $exception) {
            return now()->startOfDay();
        }
    }

    private function resolveDateRange(Request $request, Carbon $selectedDate, string $filter): array
    {
        if ($filter === 'week') {
            return [
                $selectedDate->copy()->startOfWeek(Carbon::MONDAY),
                $selectedDate->copy()->endOfWeek(Carbon::SUNDAY),
            ];
        }

        if ($filter === 'month') {
            return [
                $selectedDate->copy()->startOfMonth(),
                $selectedDate->copy()->endOfMonth(),
            ];
        }

        if ($filter === 'year') {
            return [
                $selectedDate->copy()->startOfYear(),
                $selectedDate->copy()->endOfYear(),
            ];
        }

        if ($filter === 'custom') {
            $start = $this->resolveSelectedDate($request->query('start_date'));
            $end = $this->resolveSelectedDate($request->query('end_date', $start->toDateString()));

            if ($end->lt($start)) {
                $end = $start->copy();
            }

            return [$start, $end];
        }

        return [$selectedDate->copy(), $selectedDate->copy()];
    }

    private function resolveFilterLabel(string $filter): string
    {
        return match ($filter) {
            'week' => 'This Week',
            'month' => 'This Month',
            'year' => 'This Year',
            'custom' => 'Custom',
            default => 'Today',
        };
    }

    private function resolvePeriodTitle(string $filter, Carbon $selectedDate, Carbon $rangeStart, Carbon $rangeEnd): string
    {
        return match ($filter) {
            'week' => $rangeStart->translatedFormat('d M') . ' - ' . $rangeEnd->translatedFormat('d M Y'),
            'month' => $selectedDate->translatedFormat('F Y'),
            'year' => $selectedDate->translatedFormat('Y'),
            'custom' => $rangeStart->isSameDay($rangeEnd)
                ? $rangeStart->translatedFormat('d M Y')
                : $rangeStart->translatedFormat('d M Y') . ' - ' . $rangeEnd->translatedFormat('d M Y'),
            default => $selectedDate->isToday() ? 'Today' : $selectedDate->translatedFormat('d M Y'),
        };
    }

    private function resolvePeriodDescription(string $filter, Carbon $selectedDate, Carbon $rangeStart, Carbon $rangeEnd): string
    {
        return match ($filter) {
            'week' => 'Menampilkan semua task untuk minggu berjalan.',
            'month' => 'Menampilkan semua task untuk bulan ' . $selectedDate->translatedFormat('F Y') . '.',
            'year' => 'Menampilkan semua task sepanjang tahun ' . $selectedDate->translatedFormat('Y') . '.',
            'custom' => 'Menampilkan task dari ' . $rangeStart->translatedFormat('d M Y') . ' sampai ' . $rangeEnd->translatedFormat('d M Y') . '.',
            default => 'Fokus pada task harian untuk ' . $selectedDate->translatedFormat('l, d F Y') . '.',
        };
    }

    private function buildRedirectParams(Request $request, ?string $date): array
    {
        $filter = $request->input('redirect_filter', $request->input('filter', 'today'));

        $params = [
            'filter' => in_array($filter, self::FILTERS, true) ? $filter : 'today',
            'date' => $date ?: now()->toDateString(),
        ];

        $startDate = $request->input('start_date', $request->input('redirect_start_date'));
        $endDate = $request->input('end_date', $request->input('redirect_end_date'));

        if ($params['filter'] === 'custom') {
            $params['start_date'] = $startDate ?: $params['date'];
            $params['end_date'] = $endDate ?: $params['start_date'];
        }

        return $params;
    }

    private function buildDivisionRedirectParams(Request $request, ?string $date): array
    {
        $params = $this->buildRedirectParams($request, $date);

        $userId = $request->input('redirect_user_id', $request->input('user_id'));
        $status = $request->input('redirect_status', $request->input('status'));

        if ($userId !== null && $userId !== '') {
            $params['user_id'] = $userId;
        }

        if ($status !== null && $status !== '') {
            $params['status'] = $status;
        }

        return $params;
    }
}