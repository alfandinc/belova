<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Jadwal Karyawan Mingguan</title>
    <style>
    body { font-family: Arial, sans-serif; font-size: 12px; }
    .table { border-collapse: collapse; width: 100%; margin-bottom: 16px; }
    .table th, .table td { border: 1px solid #333; padding: 4px 7px; text-align: center; font-size: 11px; }
    .table th { background: #f5f5f5; font-weight: bold; font-size: 12px; }
    .division-header { background: #e9ecef; font-weight: bold; color: #333; text-align: left; font-size: 12px; }
    .shift-pagi-office { background: #a3cfbb; color: #212529; }
    .shift-pagi-service { background: #a3cfbb; color: #212529; }
    .shift-middle-office { background: #90caf9; color: #212529; }
    .shift-middle-service { background: #90caf9; color: #212529; }
    .shift-siang-office { background: #ffe082; color: #212529; }
    .shift-siang-service { background: #ffe082; color: #212529; }
    .shift-malam { background: #b39ddb; color: #212529; }
    .shift-long { background: #f48fb1; color: #212529; }
    .shift-khusus-1 { background: #f48fb1; color: #212529; }
    .shift-khusus-2 { background: #f48fb1; color: #212529; }
    .shift-praktek-pagi { background: #a3cfbb; color: #212529; }

    .legend-box { border: 1px solid #ccc; border-radius: 8px; padding: 7px; margin-bottom: 7px; }
    .legend-item { display: flex; align-items: center; margin-bottom: 3px; }
    .legend-color { width: 18px; height: 18px; border-radius: 5px; margin-right: 6px; display: inline-block; }
    </style>
</head>
<body>
    <h2 style="text-align:center; font-size:16px; margin-bottom:12px;">
        Jadwal Karyawan Mingguan (Periode: {{ $startOfWeek->format('d M Y') }} - {{ $startOfWeek->copy()->addDays(6)->format('d M Y') }})
    </h2>
    <div class="legend-box">
    <!-- ...existing code... -->
    <table class="table">
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
                    <td colspan="{{ count($dates) + 1 }}" class="division-header" style="text-align:left;">{{ $divisionName }}</td>
                </tr>
                @foreach($employees as $employee)
                    @php
                        $hasSchedule = false;
                        foreach($dates as $date) {
                            $key = $employee->id . '_' . $date;
                            if (!empty($schedules[$key][0] ?? null)) {
                                $hasSchedule = true;
                                break;
                            }
                        }
                    @endphp
                    @if($hasSchedule)
                        <tr>
                            <td style="text-align:left;">{{ $employee->nama }}</td>
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
                                    $isLibur = false;
                                    $cellClass = '';

                                    if ($firstSchedule) {
                                        $isLibur = isset($firstSchedule->is_libur) && $firstSchedule->is_libur;

                                        if ($isLibur) {
                                            $cellClass = 'bg-danger text-white';
                                        } else {
                                            // For working days, we will draw per-shift rows inside the cell
                                            // and keep the td background neutral.
                                        }
                                    } else {
                                        // No schedule for this day: treat as Libur/Cuti
                                        $isLibur = true;
                                        $cellClass = 'bg-danger text-white';
                                    }
                                @endphp
                                <td class="{{ $isLibur ? 'bg-danger' : '' }}" style="{{ $isLibur ? 'background:#e74c3c;color:#fff;' : '' }}">
                                    @if($isLibur)
                                        <strong>{{ $firstSchedule->label ?? 'Libur/Cuti' }}</strong>
                                    @else
                                        @foreach($daySchedules as $scheduleItem)
                                            @if($scheduleItem->shift)
                                                @php
                                                    $shiftName = strtolower($scheduleItem->shift->name);
                                                    $start = \Carbon\Carbon::createFromFormat('H:i:s', $scheduleItem->shift->start_time)->format('H:i');
                                                    $end   = \Carbon\Carbon::createFromFormat('H:i:s', $scheduleItem->shift->end_time)->format('H:i');
                                                @endphp
                                                <div class="shift-{{ $shiftName }}" style="margin-bottom:2px; padding:2px 4px; border-radius:2px;">
                                                    <strong>{{ $start }} - {{ $end }}</strong>
                                                </div>
                                            @endif
                                        @endforeach
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endif
                @endforeach
            @endforeach
        </tbody>
    </table>
    <p style="font-size:11px;color:#888;text-align:right;margin-top:30px;">Generated at {{ date('d M Y H:i') }}</p>
</body>
</html>
