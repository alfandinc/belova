<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Jadwal Karyawan Mingguan</title>
    <style>
    body { font-family: Arial, sans-serif; font-size: 10px; }
    .table { border-collapse: collapse; width: 100%; margin-bottom: 16px; }
    .table th, .table td { border: 1px solid #333; padding: 3px 5px; text-align: center; font-size: 9px; }
    .table th { background: #f5f5f5; font-weight: bold; font-size: 10px; }
    .division-header { background: #e9ecef; font-weight: bold; color: #333; text-align: left; font-size: 10px; }
    .shift-pagi-office { background: #28a745; color: #fff; }
    .shift-pagi-service { background: #28a745; color: #fff; }
    .shift-middle-office { background: #007bff; color: #fff; }
    .shift-middle-service { background: #007bff; color: #fff; }
    .shift-siang-office { background: #ffc107; color: #212529; }
    .shift-siang-service { background: #ffc107; color: #212529; }
    .shift-malam { background: #6f42c1; color: #fff; }
    .shift-long { background: #b10085; color: #fff; }
    .shift-khusus-1 { background: #b10085; color: #212529; }
    .shift-khusus-2 { background: #b10085; color: #212529; }
    .shift-praktek-pagi { background: #28a745; color: #212529; }

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
                    <td colspan="{{ count($dates) + 1 }}" class="division-header">{{ $divisionName }}</td>
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
                                    $schedule = $schedules[$key][0] ?? null;
                                    $isLibur = false;
                                    $shiftName = '';
                                    $cellClass = '';
                                    $shiftLabel = '';
                                    if ($schedule) {
                                        $isLibur = isset($schedule->is_libur) && $schedule->is_libur;
                                        $shiftName = $schedule->shift ? strtolower($schedule->shift->name) : '';
                                        $cellClass = $isLibur ? 'bg-danger text-white' : ($shiftName ? 'shift-' . $shiftName : '');
                                        $shiftLabel = $isLibur
                                            ? ($schedule->label ?? 'Libur/Cuti')
                                            : (($schedule && $schedule->shift)
                                                ? (\Carbon\Carbon::createFromFormat('H:i:s', $schedule->shift->start_time)->format('H:i')
                                                    . ' - ' .
                                                    \Carbon\Carbon::createFromFormat('H:i:s', $schedule->shift->end_time)->format('H:i'))
                                                : '-');
                                    } else {
                                        // No schedule for this day: treat as Libur/Cuti
                                        $isLibur = true;
                                        $cellClass = 'bg-danger text-white';
                                        $shiftLabel = 'Libur';
                                    }
                                @endphp
                                <td class="{{ $isLibur ? 'bg-danger' : $cellClass }}" style="{{ $isLibur ? 'background:#e74c3c;color:#fff;' : '' }}"><strong>{{ $shiftLabel }}</strong></td>
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
