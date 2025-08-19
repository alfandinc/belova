<form id="jadwal-form" method="POST" action="{{ route('hrd.schedule.store') }}">
    @csrf
    <input type="hidden" id="week-start" value="{{ $startOfWeek->toDateString() }}">
    <input type="hidden" id="week-end" value="{{ \Carbon\Carbon::parse($dates[count($dates)-1])->toDateString() }}">
    <div class="table-responsive">
        <style>
            .table td.shift-pagi-office { background: #28a745 !important; color: #fff !important; }
            .table td.shift-pagi-service { background: #68b800 !important; color: #fff !important; }
            .table td.shift-middle { background: #007bff !important; color: #fff !important; }
            .table td.shift-siang { background: #ffc107 !important; color: #212529 !important; }
            .table td.shift-malam { background: #6f42c1 !important; color: #fff !important; }
            .shift-cell { transition: background 0.2s; }
            .shift-select.shift-pagi-office { background: #28a745 !important; color: #fff !important; }
            .shift-select.shift-pagi-service { background: #68b800 !important; color: #fff !important; }
            .shift-select.shift-middle { background: #007bff !important; color: #fff !important; }
            .shift-select.shift-siang { background: #ffc107 !important; color: #212529 !important; }
            .shift-select.shift-malam { background: #6f42c1 !important; color: #fff !important; }
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
                                    $schedule = $schedules[$key][0] ?? null;
                                    $shiftId = $schedule ? $schedule->shift_id : '';
                                    $shiftName = $schedule && $schedule->shift ? strtolower($schedule->shift->name) : '';
                                @endphp
                                <td class="shift-cell {{ $shiftName ? 'shift-' . $shiftName : '' }}">
                                    <select name="schedule[{{ $employee->id }}][{{ $date }}]" class="form-control shift-select">
                                        <option value="">-</option>
                                        @foreach($shifts as $shift)
                                            <option value="{{ $shift->id }}" data-shift-name="{{ strtolower($shift->name) }}" {{ ($shiftId == $shift->id) ? 'selected' : '' }}>
                                                {{ $shift->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
    <!-- Legenda Shift dan Tombol Simpan sejajar -->
    <div class="row mt-3 align-items-start">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white" style="font-weight:bold;">Legenda Shift</div>
                <div class="card-body p-2">
                    <ul class="list-unstyled mb-0">
                        @foreach($shifts as $shift)
                            @php
                                $shiftClass = 'shift-' . strtolower($shift->name);
                                $color = '#fff';
                                $bg = '#f5f5f5';
                                if($shiftClass == 'shift-pagi-office') { $bg = '#28a745'; $color = '#fff'; }
                                elseif($shiftClass == 'shift-pagi-service') { $bg = '#68b800'; $color = '#fff'; }
                                elseif($shiftClass == 'shift-middle') { $bg = '#007bff'; $color = '#fff'; }
                                elseif($shiftClass == 'shift-siang') { $bg = '#ffc107'; $color = '#212529'; }
                                elseif($shiftClass == 'shift-malam') { $bg = '#6f42c1'; $color = '#fff'; }
                            @endphp
                            <li class="mb-2">
                                <div style="background:{{ $bg }};color:{{ $color }};border-radius:8px;padding:7px 12px;display:flex;align-items:center;">
                                    <span style="font-weight:bold;font-size:13px;width:70px;">{{ $shift->name }}</span>
                                    <span style="font-size:12px;margin-left:10px;">{{ $shift->start_time }} - {{ $shift->end_time }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6 text-right d-flex align-items-start justify-content-end">
            <button type="submit" class="btn btn-primary" style="margin-top:10px;">Simpan Jadwal</button>
            <a href="{{ route('hrd.schedule.print', ['start_date' => $startOfWeek->toDateString()]) }}" target="_blank" class="btn btn-outline-secondary ml-2" style="margin-top:10px;">
                <i class="fa fa-print"></i> Print Jadwal
            </a>
        </div>
    </div>
</form>
