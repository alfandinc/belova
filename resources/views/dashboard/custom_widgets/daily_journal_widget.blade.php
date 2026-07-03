@php
    use App\Models\DailyJournalTask;
    use App\Models\HRD\Employee;
    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Support\Facades\Auth;

    $periodStart = ($dashboardFilter['period_start'] ?? now()->startOfDay())->copy()->startOfDay();
    $periodEnd = ($dashboardFilter['period_end'] ?? now()->endOfDay())->copy()->endOfDay();
    $actor = Auth::user();
    $actorEmployee = $actor?->employee;

    $allTaskAccessRoles = ['Hrd', 'HRD', 'hrd', 'Admin', 'admin', 'Ceo', 'CEO', 'ceo'];
    $canViewAllTasks = (bool) ($actor?->hasRole($allTaskAccessRoles));
    $canManageTeam = $canViewAllTasks;

    if (! $canManageTeam && $actor) {
        $canManageTeam = $actor->roles()
            ->pluck('name')
            ->contains(fn ($roleName) => str_contains(strtolower($roleName), 'manager'));
    }

    $requestedMode = request()->query('daily_journal_mode', 'my');
    $mode = in_array($requestedMode, ['my', 'team'], true) ? $requestedMode : 'my';
    if ($mode === 'team' && ! $canManageTeam) {
        $mode = 'my';
    }

    $teamMemberQuery = null;
    $teamMemberUserIds = collect();

    if ($canManageTeam) {
        $teamMemberQuery = Employee::active();

        if (! $canViewAllTasks) {
            $managerPositionIds = $actorEmployee
                ? $actorEmployee->positions()->pluck('hrd_position.id')->filter()->unique()->values()
                : collect();

            if ($managerPositionIds->isEmpty()) {
                $teamMemberQuery = null;
            } else {
                $teamMemberQuery = $teamMemberQuery->whereHas('positions', function (Builder $positionQuery) use ($managerPositionIds) {
                    $positionQuery->whereIn('hrd_position.parent_id', $managerPositionIds->all());
                });
            }
        }

        if ($teamMemberQuery) {
            $teamMembers = (clone $teamMemberQuery)
                ->whereNotNull('user_id')
                ->with(['user:id,name', 'positions.division:id,name'])
                ->get()
                ->filter(fn ($employee) => $employee->user !== null)
                ->unique('user_id')
                ->values();

            $teamMemberUserIds = $teamMembers->pluck('user_id')->filter()->unique()->values();

            $divisionOptions = $teamMembers
                ->flatMap(function ($employee) {
                    return $employee->positions
                        ->map(fn ($position) => $position->division)
                        ->filter();
                })
                ->unique('id')
                ->sortBy('name')
                ->values();
        } else {
            $teamMembers = collect();
            $divisionOptions = collect();
        }
    } else {
        $teamMembers = collect();
        $divisionOptions = collect();
    }

    $selectedDivisionId = request()->query('daily_journal_division_id');
    $selectedDivisionId = ($mode === 'team' && $selectedDivisionId !== null && $selectedDivisionId !== '')
        ? (int) $selectedDivisionId
        : null;
    $selectedStatus = in_array(request()->query('daily_journal_status'), DailyJournalTask::STATUSES, true)
        ? request()->query('daily_journal_status')
        : null;

    if ($selectedDivisionId && $divisionOptions->every(fn ($division) => (int) $division->id !== $selectedDivisionId)) {
        $selectedDivisionId = null;
    }

    $widgetTasksQuery = DailyJournalTask::query()
        ->with([
            'fromUser:id,name',
        ])
        ->where('user_id', Auth::id())
        ->where(function (Builder $query) use ($periodStart, $periodEnd) {
            $query->whereBetween('task_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->orWhere(function (Builder $deadlineQuery) {
                    $deadlineQuery->whereNotNull('deadline_date')
                        ->where('status', '!=', 'done');
                });
        });

    $tasksQuery = DailyJournalTask::query()
        ->with([
            'user:id,name',
            'user.employee:id,user_id,photo',
            'fromUser:id,name',
        ])
        ->where(function (Builder $query) use ($periodStart, $periodEnd) {
            $query->whereBetween('task_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->orWhere(function (Builder $deadlineQuery) {
                    $deadlineQuery->whereNotNull('deadline_date')
                        ->where('status', '!=', 'done');
                });
        });

    if ($mode === 'team' && $canManageTeam) {
        if ($canViewAllTasks) {
            if ($selectedDivisionId) {
                $tasksQuery->whereHas('user.employee.positions', function (Builder $positionQuery) use ($selectedDivisionId) {
                    $positionQuery->where('hrd_position.division_id', $selectedDivisionId)
                        ->where('hrd_employee_position.is_primary', 1);
                });
            }
        } else {
            if ($teamMemberUserIds->isEmpty()) {
                $tasksQuery->whereRaw('1 = 0');
            } else {
                $tasksQuery->whereIn('user_id', $teamMemberUserIds->all());

                if ($selectedDivisionId) {
                    $tasksQuery->whereHas('user.employee.positions', function (Builder $positionQuery) use ($selectedDivisionId) {
                        $positionQuery->where('hrd_position.division_id', $selectedDivisionId)
                            ->where('hrd_employee_position.is_primary', 1);
                    });
                }
            }
        }
    } else {
        $tasksQuery->where('user_id', Auth::id());
    }

    $statusBadgeClass = [
        'todo' => 'secondary',
        'in_progress' => 'warning',
        'done' => 'success',
        'skipped' => 'dark',
    ];

    $statusLabels = [
        'todo' => 'Todo',
        'in_progress' => 'In Progress',
        'done' => 'Done',
        'skipped' => 'Skipped',
    ];

    $statusRowStyles = [
        'todo' => 'background: #f8fafc;',
        'in_progress' => 'background: #fff8eb;',
        'done' => 'background: #edfdf5;',
        'skipped' => 'background: #f4f7fb; opacity: 0.9;',
    ];

    $selectedDivisionName = $selectedDivisionId
        ? optional($divisionOptions->firstWhere('id', $selectedDivisionId))->name
        : null;

    $fullJournalUrl = $mode === 'team' && $canManageTeam
        ? route('daily-journal.division.index', array_filter([
            'filter' => 'custom',
            'start_date' => $periodStart->toDateString(),
            'end_date' => $periodEnd->toDateString(),
            'status' => $selectedStatus,
        ], fn ($value) => $value !== null && $value !== ''))
        : route('daily-journal.index', array_filter([
            'filter' => 'custom',
            'start_date' => $periodStart->toDateString(),
            'end_date' => $periodEnd->toDateString(),
            'status' => $selectedStatus,
        ], fn ($value) => $value !== null && $value !== ''));

    $modalId = 'dailyJournalModal-' . ($widget->id ?? 'widget');
    $tableId = 'dailyJournalTable-' . ($widget->id ?? 'widget');
    $modalTableId = 'dailyJournalModalTable-' . ($widget->id ?? 'widget');
    $createModalId = 'dailyJournalCreateModal-' . ($widget->id ?? 'widget');

    if ($selectedStatus) {
        $widgetTasksQuery->where('status', $selectedStatus);
        $tasksQuery->where('status', $selectedStatus);
    }

    $widgetTotalTasks = (clone $widgetTasksQuery)->count();

    $widgetTasks = $widgetTasksQuery
        ->orderByRaw("CASE WHEN status IN ('todo', 'in_progress') THEN 0 ELSE 1 END")
        ->orderByDesc('task_date')
        ->orderByRaw('CASE WHEN scheduled_time IS NULL THEN 1 ELSE 0 END')
        ->orderBy('scheduled_time')
        ->orderByDesc('id')
        ->get();

    $allTasks = $tasksQuery
        ->orderByRaw("CASE WHEN status IN ('todo', 'in_progress') THEN 0 ELSE 1 END")
        ->orderByDesc('task_date')
        ->orderByRaw('CASE WHEN scheduled_time IS NULL THEN 1 ELSE 0 END')
        ->orderBy('scheduled_time')
        ->orderByDesc('id')
        ->get();
@endphp

<div class="card h-100 border-0 shadow-sm dashboard-widget-card dashboard-journal-widget" style="border-radius: 18px; overflow: hidden;">
    <div class="card-body" style="padding: 16px 18px 14px;">
        <div class="mb-1">
            <div class="d-md-flex justify-content-between align-items-start" style="gap: 10px;">
                <div class="pr-3 mb-1 mb-md-0" style="min-width: 0; flex: 1 1 auto;">
                    <div class="d-flex align-items-center justify-content-between" style="gap: 12px;">
                        <h5 class="card-title mb-0">{{ $widget->widget_name ?? 'Daily Journal' }}</h5>
                        <form method="GET" action="{{ route('dashboard.index') }}" class="mb-0 d-none d-md-block" data-dashboard-ajax-form="daily-journal">
                            <input type="hidden" name="start_date" value="{{ $dashboardFilter['start_date'] ?? $periodStart->toDateString() }}">
                            <input type="hidden" name="end_date" value="{{ $dashboardFilter['end_date'] ?? $periodEnd->toDateString() }}">

                            <div class="d-flex align-items-center justify-content-end flex-nowrap" style="gap: 6px; max-width: 100%;">
                                <select name="daily_journal_status" class="form-control form-control-sm" data-dashboard-filter-input="1" aria-label="Status filter" title="Status filter" style="width: 108px; min-width: 0; height: 34px;">
                                    <option value="">All Status</option>
                                    @foreach (DailyJournalTask::STATUSES as $statusOption)
                                        <option value="{{ $statusOption }}" {{ $selectedStatus === $statusOption ? 'selected' : '' }}>{{ $statusLabels[$statusOption] ?? ucfirst($statusOption) }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-sm btn-primary px-2" style="height: 34px; min-width: 64px; flex: 0 0 auto;" data-toggle="modal" data-target="#{{ $createModalId }}">Add</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary px-2" style="height: 34px; min-width: 72px; flex: 0 0 auto;" data-toggle="modal" data-target="#{{ $modalId }}">Show All</button>
                            </div>
                        </form>
                    </div>
                    {{-- <p class="text-muted mb-0 mt-0 small" style="line-height: 1.15;">{{ $periodStart->translatedFormat('d M Y') }} - {{ $periodEnd->translatedFormat('d M Y') }}</p> --}}
                </div>
            </div>

            <form method="GET" action="{{ route('dashboard.index') }}" class="mb-0 d-md-none mt-2" data-dashboard-ajax-form="daily-journal">
                <input type="hidden" name="start_date" value="{{ $dashboardFilter['start_date'] ?? $periodStart->toDateString() }}">
                <input type="hidden" name="end_date" value="{{ $dashboardFilter['end_date'] ?? $periodEnd->toDateString() }}">

                <div class="d-flex align-items-center flex-wrap" style="gap: 2px;">
                    <select name="daily_journal_status" class="form-control form-control-sm" data-dashboard-filter-input="1" aria-label="Status filter" title="Status filter" style="width: 136px; height: 34px;">
                        <option value="">All Status</option>
                        @foreach (DailyJournalTask::STATUSES as $statusOption)
                            <option value="{{ $statusOption }}" {{ $selectedStatus === $statusOption ? 'selected' : '' }}>{{ $statusLabels[$statusOption] ?? ucfirst($statusOption) }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-sm btn-primary px-2" style="height: 34px; min-width: 64px;" data-toggle="modal" data-target="#{{ $createModalId }}">Add</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary px-2" style="height: 34px; min-width: 78px;" data-toggle="modal" data-target="#{{ $modalId }}">Show All</button>
                </div>
            </form>
        </div>

        @if ($widgetTasks->isEmpty())
            <div class="alert alert-light border mb-0">
                Tidak ada task Daily Journal untuk filter yang dipilih.
            </div>
        @else
            <div class="table-responsive mt-0">
                <table id="{{ $tableId }}" class="table table-sm mb-0" data-dashboard-journal-table="1" style="border-collapse: separate; border-spacing: 0 6px; width: 100%; margin-top: -2px;">
                    <thead>
                        <tr>
                            <th class="border-0 small text-muted text-uppercase font-weight-bold" style="padding-top: 2px; padding-bottom: 4px;">Date</th>
                            <th class="border-0 small text-muted text-uppercase font-weight-bold" style="padding-top: 2px; padding-bottom: 4px;">Task</th>
                            <th class="border-0 small text-muted text-uppercase font-weight-bold" style="padding-top: 2px; padding-bottom: 4px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($widgetTasks as $task)
                            @php
                                $taskEditModalId = 'dailyJournalTaskEditModal-' . $task->id;
                                $canEditTask = (int) $task->user_id === (int) Auth::id();
                            @endphp
                            <tr
                                style="{{ $statusRowStyles[$task->status] ?? 'background: #f8fafc;' }}{{ $canEditTask ? ' cursor: pointer;' : '' }}"
                                @if ($canEditTask)
                                    data-toggle="modal"
                                    data-target="#{{ $taskEditModalId }}"
                                @endif
                            >
                                <td class="align-middle border-0 pl-3 small text-muted" style="border-top-left-radius: 12px; border-bottom-left-radius: 12px; width: 96px;">
                                    {{ optional($task->task_date)->translatedFormat('d M Y') }}
                                    @if ($task->scheduled_time)
                                        <div>{{ substr($task->scheduled_time, 0, 5) }}</div>
                                    @endif
                                </td>
                                <td class="align-middle border-0" style="border-top-right-radius: 12px; border-bottom-right-radius: 12px;">
                                    <div class="font-weight-bold text-dark" style="line-height: 1.35;">{{ $task->title }}</div>
                                    <div class="small text-muted mt-1">
                                        @if ($task->note)
                                            <span>{{ $task->note }}</span>
                                        @else
                                            <span>Belum ada catatan task.</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="align-middle border-0 small text-right pr-3" style="border-top-right-radius: 12px; border-bottom-right-radius: 12px; width: 110px;">
                                    <span class="badge badge-{{ $statusBadgeClass[$task->status] ?? 'secondary' }}">{{ $statusLabels[$task->status] ?? ucfirst($task->status) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="small text-muted mt-3">Total {{ number_format($widgetTotalTasks, 0, ',', '.') }} task.</div>
        @endif
    </div>
</div>

<div class="modal fade" id="{{ $createModalId }}" tabindex="-1" role="dialog" aria-labelledby="{{ $createModalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document" style="max-width: 760px;">
        <div class="modal-content border-0" style="border-radius: 18px; overflow: hidden;">
            <div class="modal-header border-0 pb-2">
                <div>
                    <h5 class="modal-title mb-1" id="{{ $createModalId }}Label">Add Task</h5>
                    <div class="small text-muted">Tambahkan task baru ke My Journal.</div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body pt-2" style="padding-bottom: 20px;">
                <form method="POST" action="{{ route('daily-journal.store') }}" class="js-dashboard-journal-create-form mb-0" data-modal-id="{{ $createModalId }}">
                    @csrf
                    <input type="hidden" name="redirect_filter" value="custom">
                    <input type="hidden" name="redirect_start_date" value="{{ $periodStart->toDateString() }}">
                    <input type="hidden" name="redirect_end_date" value="{{ $periodEnd->toDateString() }}">

                    <div class="form-row">
                        <div class="form-group col-md-6 mb-3">
                            <label class="small text-muted font-weight-bold text-uppercase mb-1">Task Date</label>
                            <input type="date" name="task_date" class="form-control form-control-sm" value="{{ $periodEnd->toDateString() }}" required>
                        </div>
                        <div class="form-group col-md-6 mb-3">
                            <label class="small text-muted font-weight-bold text-uppercase mb-1">Deadline</label>
                            <input type="date" name="deadline_date" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="small text-muted font-weight-bold text-uppercase mb-1">Title</label>
                        <input type="text" name="title" class="form-control form-control-sm" maxlength="120" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6 mb-3">
                            <label class="small text-muted font-weight-bold text-uppercase mb-1">Status</label>
                            <select name="status" class="form-control form-control-sm">
                                @foreach (DailyJournalTask::STATUSES as $statusOption)
                                    <option value="{{ $statusOption }}" {{ $statusOption === 'todo' ? 'selected' : '' }}>{{ $statusLabels[$statusOption] ?? ucfirst($statusOption) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6 mb-3">
                            <label class="small text-muted font-weight-bold text-uppercase mb-1">Time</label>
                            <input type="time" name="scheduled_time" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="small text-muted font-weight-bold text-uppercase mb-1">Note</label>
                        <textarea name="note" class="form-control form-control-sm" rows="3" maxlength="180"></textarea>
                    </div>
                    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 8px;">
                        <div class="small text-muted">Task baru akan langsung masuk ke My Journal.</div>
                        <button type="submit" class="btn btn-sm btn-primary px-3">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@foreach ($widgetTasks as $task)
    @php
        $taskEditModalId = 'dailyJournalTaskEditModal-' . $task->id;
        $canEditTask = (int) $task->user_id === (int) Auth::id();
    @endphp
    @if ($canEditTask)
        <div class="modal fade" id="{{ $taskEditModalId }}" tabindex="-1" role="dialog" aria-labelledby="{{ $taskEditModalId }}Label" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document" style="max-width: 760px;">
                <div class="modal-content border-0" style="border-radius: 18px; overflow: hidden;">
                    <div class="modal-header border-0 pb-2">
                        <div>
                            <h5 class="modal-title mb-1" id="{{ $taskEditModalId }}Label">Edit Task</h5>
                            <div class="small text-muted">{{ optional($task->task_date)->translatedFormat('d M Y') }}</div>
                        </div>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body pt-2" style="padding-bottom: 20px;">
                        <form method="POST" action="{{ route('daily-journal.update', $task) }}" class="js-dashboard-journal-edit-form mb-0" data-modal-id="{{ $taskEditModalId }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="filter" value="custom">
                            <input type="hidden" name="date" value="{{ optional($task->task_date)->toDateString() ?: $periodStart->toDateString() }}">
                            <input type="hidden" name="start_date" value="{{ $periodStart->toDateString() }}">
                            <input type="hidden" name="end_date" value="{{ $periodEnd->toDateString() }}">

                            <div class="form-group mb-3">
                                <label class="small text-muted font-weight-bold text-uppercase mb-1">Title</label>
                                <input type="text" name="title" class="form-control form-control-sm" maxlength="120" value="{{ $task->title }}" required>
                            </div>
                            <div class="form-group mb-3">
                                <label class="small text-muted font-weight-bold text-uppercase mb-1">Status</label>
                                <select name="status" class="form-control form-control-sm">
                                    @foreach (DailyJournalTask::STATUSES as $statusOption)
                                        <option value="{{ $statusOption }}" {{ $task->status === $statusOption ? 'selected' : '' }}>{{ $statusLabels[$statusOption] ?? ucfirst($statusOption) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-3">
                                <label class="small text-muted font-weight-bold text-uppercase mb-1">Note</label>
                                <textarea name="note" class="form-control form-control-sm" rows="3" maxlength="180">{{ $task->note }}</textarea>
                            </div>
                            <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 8px;">
                                @if ($task->fromUser)
                                    <div class="small text-muted">Assigned by <span class="font-weight-bold text-dark">{{ $task->fromUser->name }}</span></div>
                                @else
                                    <div class="small text-muted">Click save to refresh the widget.</div>
                                @endif
                                <button type="submit" class="btn btn-sm btn-primary px-3">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach

<div class="modal fade" id="{{ $modalId }}" data-dashboard-journal-modal="1" tabindex="-1" role="dialog" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content border-0" style="border-radius: 18px; overflow: hidden;">
            <div class="modal-header border-0 pb-2">
                <div>
                    <h5 class="modal-title mb-1" id="{{ $modalId }}Label">All Journal</h5>
                    <div class="small text-muted">{{ $periodStart->translatedFormat('d M Y') }} - {{ $periodEnd->translatedFormat('d M Y') }}</div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body pt-2">
                <form method="GET" action="{{ route('dashboard.index') }}" class="mb-3" data-dashboard-ajax-form="daily-journal">
                    <input type="hidden" name="daily_journal_modal_open" value="1" data-dashboard-filter-input="1">

                    <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                        <select name="daily_journal_mode" class="form-control form-control-sm" data-dashboard-filter-input="1" aria-label="Journal mode" title="Journal mode" style="width: 132px; height: 34px;">
                            <option value="my" {{ $mode === 'my' ? 'selected' : '' }}>My Journal</option>
                            @if ($canManageTeam)
                                <option value="team" {{ $mode === 'team' ? 'selected' : '' }}>Team Journal</option>
                            @endif
                        </select>
                        <select name="daily_journal_division_id" class="form-control form-control-sm" data-dashboard-filter-input="1" aria-label="Division filter" title="Division filter" style="width: 160px; height: 34px;" {{ $mode !== 'team' || ! $canManageTeam ? 'disabled' : '' }}>
                            <option value="">All Divisions</option>
                            @foreach ($divisionOptions as $division)
                                <option value="{{ $division->id }}" {{ $selectedDivisionId === (int) $division->id ? 'selected' : '' }}>{{ $division->name }}</option>
                            @endforeach
                        </select>
                        @if ($mode === 'team' && $selectedDivisionName)
                            <div class="small text-muted">Division: <span class="font-weight-bold text-dark">{{ $selectedDivisionName }}</span></div>
                        @endif
                    </div>
                </form>

                @if ($allTasks->isEmpty())
                    <div class="alert alert-light border mb-0">Tidak ada task Daily Journal untuk filter yang dipilih.</div>
                @else
                    <div class="table-responsive">
                        <table id="{{ $modalTableId }}" class="table table-sm mb-0" data-dashboard-journal-modal-table="1" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Task</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($allTasks as $task)
                                    @php
                                        $taskOwnerName = optional($task->user)->name ?? ($mode === 'my' ? 'Me' : '-');
                                        $canEditTask = (int) $task->user_id === (int) Auth::id();
                                        $taskActionUrl = (int) $task->user_id === (int) Auth::id()
                                            ? route('daily-journal.index', array_filter([
                                                'filter' => 'custom',
                                                'date' => optional($task->task_date)->toDateString(),
                                                'start_date' => $periodStart->toDateString(),
                                                'end_date' => $periodEnd->toDateString(),
                                                'status' => $selectedStatus,
                                            ], fn ($value) => $value !== null && $value !== ''))
                                            : $fullJournalUrl;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div>{{ optional($task->task_date)->translatedFormat('d M Y') }}</div>
                                            @if ($task->scheduled_time)
                                                <div class="small text-muted">{{ substr($task->scheduled_time, 0, 5) }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="font-weight-bold text-dark">{{ $task->title }}</div>
                                            @if ($mode === 'team')
                                                <div class="small text-muted mt-1">{{ $taskOwnerName }}</div>
                                            @endif
                                            @if ($task->note)
                                                <div class="small text-muted mt-1">{{ $task->note }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $statusBadgeClass[$task->status] ?? 'secondary' }}">{{ $statusLabels[$task->status] ?? ucfirst($task->status) }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ $taskActionUrl }}" class="btn btn-sm btn-outline-primary">{{ $canEditTask ? 'Edit' : 'Open' }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>