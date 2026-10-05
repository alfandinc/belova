@php
    $shiftPayload = collect($allShifts ?? $shifts)->map(fn($s) => [
        'id' => $s->id,
        'name' => $s->name,
        'start' => substr($s->start_time, 0, 5),
        'end' => substr($s->end_time, 0, 5),
        'color' => $s->color,
        'active' => (bool) $s->active,
    ])->values();
    $today = now()->toDateString();
    $holidays = \App\Models\HRD\LiburNasional::namesByDate($dates[0], $dates[count($dates) - 1]);
    $totalEmployees = collect($employeesByDivision)->flatten(1)->count();
@endphp
<input type="hidden" id="week-start" value="{{ $startOfWeek->toDateString() }}">
<input type="hidden" id="week-end" value="{{ \Carbon\Carbon::parse($dates[count($dates)-1])->toDateString() }}">
<script type="application/json" id="shift-data">{!! json_encode($shiftPayload, JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>

<div class="sched-scroll" id="sched-scroll">
    <table class="table table-bordered sched-table mb-0" id="sched-table" data-total="{{ $totalEmployees }}">
        <thead>
            <tr>
                <th class="sched-name-col">Karyawan</th>
                @foreach($dates as $i => $date)
                    @php
                        $d = \Carbon\Carbon::parse($date)->locale('id');
                        $holidayName = $holidays[$date] ?? null;
                        $hariGantiLibur = $d->isSunday() || $holidayName;
                    @endphp
                    <th class="sched-day-head {{ $date === $today ? 'is-today' : '' }} {{ $d->isWeekend() ? 'is-weekend' : '' }}"
                        data-col="{{ $i }}" @if($hariGantiLibur) data-hari-ganti-libur="1" @endif
                        title="Klik untuk memilih seluruh kolom{{ $holidayName ? ' · ' . $holidayName : '' }}">
                        <div>{{ $d->isoFormat('dddd') }}</div>
                        <small>{{ $d->isoFormat('D MMM') }}</small>
                        @if($holidayName)
                            <small class="d-block text-danger text-truncate" style="max-width:140px;margin:auto">{{ $holidayName }}</small>
                        @endif
                        @if($hariGantiLibur)
                            <span class="badge badge-warning d-block mt-1" style="font-size:10px;">+1 Ganti Libur</span>
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($employeesByDivision as $divisionName => $employees)
                <tr class="sched-division" data-division="{{ $divisionName }}">
                    <td colspan="{{ count($dates) + 1 }}">
                        <i class="fa fa-chevron-down sched-caret"></i>
                        {{ $divisionName }}
                        <span class="badge badge-light ml-1">{{ count($employees) }}</span>
                    </td>
                </tr>
                @foreach($employees as $employee)
                    <tr class="employee-row" data-division="{{ $divisionName }}" data-name="{{ strtolower($employee->nama) }}">
                        <td class="sched-name-col sched-emp" title="Klik untuk memilih satu minggu">
                            {{ $employee->nama }}
                            @if($employee->schedule_position_name)
                                <small class="d-block text-muted">{{ $employee->schedule_position_name }}</small>
                            @endif
                        </td>
                        @foreach($dates as $i => $date)
                            @php
                                $daySchedules = collect($schedules[$employee->id . '_' . $date] ?? [])->values();
                                $first = $daySchedules[0] ?? null;
                                $isLibur = $first && !empty($first->is_libur);
                                $ids = $first && !empty($first->is_ganti_libur)
                                    ? 'GL'
                                    : ($isLibur ? '' : $daySchedules->pluck('shift_id')->filter()->take(2)->implode(','));
                            @endphp
                            @if($isLibur)
                                <td class="sc sc-libur {{ $date === $today ? 'is-today' : '' }}" data-col="{{ $i }}" data-libur="1">{{ $first->label ?? 'Libur/Cuti' }}</td>
                            @else
                                <td class="sc {{ $date === $today ? 'is-today' : '' }}" data-col="{{ $i }}"
                                    data-emp="{{ $employee->id }}" data-date="{{ $date }}"
                                    data-shifts="{{ $ids }}" data-orig="{{ $ids }}"></td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td class="sched-name-col text-muted small">Terjadwal</td>
                @foreach($dates as $i => $date)
                    <td class="sched-count text-center small" data-col="{{ $i }}">-</td>
                @endforeach
            </tr>
        </tfoot>
    </table>
</div>

<!-- Manajemen Shift (collapsible) -->
<div class="card shadow-sm mt-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
        <a href="#shift-mgmt-body" data-toggle="collapse" class="font-weight-bold text-dark" id="shift-mgmt-toggle">
            <i class="fa fa-cog mr-1"></i> Manajemen Shift
        </a>
        <div class="d-flex align-items-center">
            <select id="shift-status-filter" class="form-control form-control-sm mr-2" style="width:auto;">
                <option value="active" selected>Aktif</option>
                <option value="inactive">Tidak Aktif</option>
                <option value="all">Semua</option>
            </select>
            <button type="button" class="btn btn-sm btn-primary" id="btn-add-shift">
                <i class="fa fa-plus"></i> Tambah Shift
            </button>
        </div>
    </div>
    <div id="shift-mgmt-body" class="collapse">
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
                        @foreach(($allShifts ?? $shifts) as $shift)
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
                                            data-shift-color="{{ $shift->color }}">Edit</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger shift-delete-btn"
                                            data-shift-id="{{ $shift->id }}">Hapus</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
