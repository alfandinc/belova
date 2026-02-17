<form id="jadwal-form" method="POST" action="{{ route('hrd.schedule.store') }}">
    @csrf
    <input type="hidden" id="week-start" value="{{ $startOfWeek->toDateString() }}">
    <input type="hidden" id="week-end" value="{{ \Carbon\Carbon::parse($dates[count($dates)-1])->toDateString() }}">
    <div class="table-responsive">
        <style>
            /* Keep table cell background neutral; color only the selects */
            .table td.shift-pagi-office,
            .table td.shift-pagi-service,
            .table td.shift-middle-office,
            .table td.shift-middle-service,
            .table td.shift-siang-office,
            .table td.shift-siang-service,
            .table td.shift-malam,
            .table td.shift-long,
            .table td.shift-khusus-1,
            .table td.shift-khusus-2,
            .table td.shift-praktek-pagi,
            .table td.shift-praktek-malam {
                background: transparent !important;
                color: inherit !important;
            }
            .shift-cell { transition: background 0.2s; }
            .shift-select.shift-pagi-office { background: #28a745 !important; color: #fff !important; }
            .shift-select.shift-pagi-service { background: #68b800 !important; color: #fff !important; }
            .shift-select.shift-middle-office { background: #007bff !important; color: #fff !important; }
            .shift-select.shift-middle-service { background: #2890ff !important; color: #fff !important; }
            .shift-select.shift-siang-office { background: #ffc107 !important; color: #212529 !important; }
            .shift-select.shift-siang-service { background: #ffd54f !important; color: #212529 !important; }
            .shift-select.shift-malam { background: #6f42c1 !important; color: #fff !important; }
            .shift-select.shift-long { background: #b10085 !important; color: #fff !important; }
            .shift-select.shift-khusus-1 { background: #f080ff !important; color: #212529 !important; }
            .shift-select.shift-khusus-2 { background: #ff8bff !important; color: #212529 !important; }
            .shift-select.shift-praktek-pagi { background: #9dff90 !important; color: #212529 !important; }
            .shift-select.shift-praktek-malam { background: #6f42c1 !important; color: #fff !important; }
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
                        <tr>
                            <td>{{ $employee->nama }}</td>
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
                                    $firstShiftName = $firstSchedule && $firstSchedule->shift ? strtolower($firstSchedule->shift->name) : '';
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
                                                        <option value="{{ $shift->id }}" data-shift-name="{{ strtolower($shift->name) }}" {{ ($firstShiftId == $shift->id) ? 'selected' : '' }}>
                                                            {{ $shift->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if($firstSchedule && !$isLibur && isset($firstSchedule->id))
                                                    <button type="button" class="btn btn-sm btn-danger ml-2 delete-schedule-btn"
                                                            data-employee-id="{{ $employee->id }}"
                                                            data-date="{{ $date }}"
                                                            data-schedule-id="{{ $firstSchedule->id }}"
                                                            title="Hapus Jadwal">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                @endif
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <select
                                                    name="schedule[{{ $employee->id }}][{{ $date }}][]"
                                                    class="form-control shift-select"
                                                    data-employee-id="{{ $employee->id }}"
                                                    data-date="{{ $date }}">
                                                    <option value="">-</option>
                                                    @foreach($shifts as $shift)
                                                        <option value="{{ $shift->id }}" data-shift-name="{{ strtolower($shift->name) }}" {{ ($secondShiftId == $shift->id) ? 'selected' : '' }}>
                                                            {{ $shift->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if($secondSchedule && !$isLibur && isset($secondSchedule->id))
                                                    <button type="button" class="btn btn-sm btn-danger ml-2 delete-schedule-btn"
                                                            data-employee-id="{{ $employee->id }}"
                                                            data-date="{{ $date }}"
                                                            data-schedule-id="{{ $secondSchedule->id }}"
                                                            title="Hapus Jadwal Kedua">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
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
                                    <th style="width:25%;">Nama Shift</th>
                                    <th style="width:20%;">Jam Mulai</th>
                                    <th style="width:20%;">Jam Selesai</th>
                                    <th style="width:15%;">Status</th>
                                    <th style="width:20%;">Aksi</th>
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
                                        <td>{{ $shift->name }}</td>
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
