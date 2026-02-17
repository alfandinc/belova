<form id="jadwal-form" method="POST" action="{{ route('hrd.schedule.store') }}">
    @csrf
    <input type="hidden" id="week-start" value="{{ $startOfWeek->toDateString() }}">
    <input type="hidden" id="week-end" value="{{ \Carbon\Carbon::parse($dates[count($dates)-1])->toDateString() }}">
    <div class="table-responsive">
        <style>
            .shift-cell { transition: background 0.2s; }
            /* Stronger separator between employees */
            tr.employee-row > td {
                border-top: 2px solid #c0c0c0 !important;
            }
        </style>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Karyawan</th>
                    @foreach($dates as $date)
                        <th>{{ \Carbon\Carbon::parse($date)->format('D, d M') }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($employeesByDivision as $divisionName => $employees)
                    <tr>
                        <td colspan="{{ count($dates) + 1 }}" style="background:#f5f5f5;font-weight:bold;color:#333;">
                            {{ $divisionName }}
                        </td>
                    </tr>
                    @foreach($employees as $employee)
                        <tr class="employee-row">
                            <td>
                                <div class="d-flex align-items-center">
                                    <span>{{ $employee->nama }}</span>
                                </div>
                            </td>
                            @foreach($dates as $date)
                                @php
                                    $key = $employee->id . '_' . $date;
                                    $daySchedules = $schedules[$key] ?? collect();
                                    if ($daySchedules instanceof \Illuminate\Support\Collection) {
                                        $daySchedules = $daySchedules->values();
                                    } else {
                                        $daySchedules = collect($daySchedules);
                                    }
                                    $firstSchedule = $daySchedules[0] ?? null;
                                    $secondSchedule = $daySchedules[1] ?? null;
                                    $isLibur = $firstSchedule && isset($firstSchedule->is_libur) && $firstSchedule->is_libur;
                                    $firstShiftId = $firstSchedule ? ($firstSchedule->shift_id ?? '') : '';
                                    $secondShiftId = $secondSchedule ? ($secondSchedule->shift_id ?? '') : '';
                                    $firstShiftName = $firstSchedule && $firstSchedule->shift
                                        ? \Illuminate\Support\Str::slug($firstSchedule->shift->name)
                                        : '';
                                @endphp
                                <td class="shift-cell {{ $isLibur ? 'bg-danger text-white' : ($firstShiftName ? 'shift-' . $firstShiftName : '') }}">
                                    @if($isLibur)
                                        <span style="font-weight:bold;">{{ $firstSchedule->label ?? 'Libur/Cuti' }}</span>
                                    @else
                                        <div class="d-flex flex-column w-100">
                                            <div class="d-flex align-items-center mb-1">
                                                <select
                                                    name="schedule[{{ $employee->id }}][{{ $date }}][]"
                                                    class="form-control shift-select"
                                                    data-employee-id="{{ $employee->id }}"
                                                    data-date="{{ $date }}">
                                                    <option value="">-</option>
                                                    @foreach($shifts as $shift)
                                                        <option value="{{ $shift->id }}"
                                                                data-shift-name="{{ \Illuminate\Support\Str::slug($shift->name) }}"
                                                                data-shift-color="{{ $shift->color }}"
                                                                {{ ($firstShiftId == $shift->id) ? 'selected' : '' }}>
                                                            {{ $shift->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <div class="btn-group ml-2">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <i class="fa fa-ellipsis-v"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-right">
                                                        @if($firstSchedule && !$isLibur && isset($firstSchedule->id))
                                                            <a href="#" class="dropdown-item delete-schedule-btn"
                                                               data-employee-id="{{ $employee->id }}"
                                                               data-date="{{ $date }}"
                                                               data-schedule-id="{{ $firstSchedule->id }}">
                                                                Hapus Shift
                                                            </a>
                                                        @endif
                                                        @if(!$secondSchedule)
                                                            <a href="#" class="dropdown-item option-double-shift"
                                                               data-employee-id="{{ $employee->id }}">
                                                                Double Shift
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="align-items-center second-shift-row second-shift-employee-{{ $employee->id }} {{ $secondSchedule ? 'd-flex' : 'd-none' }}">
                                                <select
                                                    name="schedule[{{ $employee->id }}][{{ $date }}][]"
                                                    class="form-control shift-select"
                                                    data-employee-id="{{ $employee->id }}"
                                                    data-date="{{ $date }}">
                                                    <option value="">-</option>
                                                    @foreach($shifts as $shift)
                                                        <option value="{{ $shift->id }}"
                                                                data-shift-name="{{ \Illuminate\Support\Str::slug($shift->name) }}"
                                                                data-shift-color="{{ $shift->color }}"
                                                                {{ ($secondShiftId == $shift->id) ? 'selected' : '' }}>
                                                            {{ $shift->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if($secondSchedule && !$isLibur && isset($secondSchedule->id))
                                                    <div class="btn-group ml-2">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                            <i class="fa fa-ellipsis-v"></i>
                                                        </button>
                                                        <div class="dropdown-menu dropdown-menu-right">
                                                            <a href="#" class="dropdown-item delete-schedule-btn"
                                                               data-employee-id="{{ $employee->id }}"
                                                               data-date="{{ $date }}"
                                                               data-schedule-id="{{ $secondSchedule->id }}">
                                                                Hapus Shift Kedua
                                                            </a>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
    <!-- Legenda / Manajemen Shift menggunakan DataTable -->
    <div class="row mt-3 align-items-start">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white" style="font-weight:bold;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <span>Manajemen Shift</span>
                            <div class="ml-3">
                                <select id="shift-status-filter" class="form-control form-control-sm">
                                    <option value="active" selected>Aktif</option>
                                    <option value="inactive">Tidak Aktif</option>
                                    <option value="all">Semua</option>
                                </select>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" id="btn-add-shift">
                            <i class="fa fa-plus"></i> Tambah Shift
                        </button>
                    </div>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table id="shift-table" class="table table-sm table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th style="width:30%;">Nama Shift</th>
                                    <th style="width:20%;">Jam Mulai</th>
                                    <th style="width:20%;">Jam Selesai</th>
                                    <th style="width:15%;">Status</th>
                                    <th style="width:15%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    // $shifts berisi hanya shift aktif untuk dropdown,
                                    // $allShifts (jika ada) digunakan untuk manajemen shift.
                                    $managementShifts = isset($allShifts) ? $allShifts : $shifts;
                                @endphp
                                @foreach($managementShifts as $shift)
                                    <tr>
                                        <td>
                                            @if(!empty($shift->color))
                                                <span class="d-inline-block mr-2" style="width:18px;height:14px;border-radius:3px;background: {{ $shift->color }};"></span>
                                            @endif
                                            {{ $shift->name }}
                                        </td>
                                        <td>{{ substr($shift->start_time, 0, 5) }}</td>
                                        <td>{{ substr($shift->end_time, 0, 5) }}</td>
                                        <td class="text-center">
                                            @if($shift->active)
                                                <span class="badge badge-success">Aktif</span>
                                            @else
                                                <span class="badge badge-secondary">Tidak Aktif</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-primary shift-edit-btn mr-1"
                                                    data-shift-id="{{ $shift->id }}"
                                                    data-shift-name="{{ $shift->name }}"
                                                    data-shift-start="{{ substr($shift->start_time, 0, 5) }}"
                                                    data-shift-end="{{ substr($shift->end_time, 0, 5) }}"
                                                    data-shift-active="{{ $shift->active ? 1 : 0 }}"
                                                    data-shift-color="{{ $shift->color }}"
                                                    title="Edit Shift">
                                                Edit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger shift-delete-btn"
                                                    data-shift-id="{{ $shift->id }}"
                                                    title="Hapus Shift">
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 text-right d-flex align-items-start justify-content-end">
            <!-- Buttons removed: jadwal now auto-saves and print is accessed elsewhere if needed -->
        </div>
    </div>
</form>
