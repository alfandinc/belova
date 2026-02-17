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

    .legend-box { border: 1px solid #ccc; border-radius: 8px; padding: 7px; margin-bottom: 7px; }
    .legend-item { display: flex; align-items: center; margin-bottom: 3px; }
    .legend-color { width: 18px; height: 18px; border-radius: 5px; margin-right: 6px; display: inline-block; }
    </style>
</head>
<body>
@php
    $resolveShiftColors = function($shift) {
        $bg = $shift && !empty($shift->color) ? trim($shift->color) : '#a3cfbb';
        if ($bg[0] !== '#') {
            $bg = '#' . $bg;
        }
        $hex = ltrim($bg, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) !== 6) {
            return ['bg' => $bg, 'text' => '#000000'];
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $brightness = ($r * 299 + $g * 587 + $b * 114) / 1000;
        $text = $brightness > 150 ? '#000000' : '#ffffff';
        return ['bg' => $bg, 'text' => $text];
    };
@endphp
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

                                    if ($firstSchedule) {
                                        $isLibur = isset($firstSchedule->is_libur) && $firstSchedule->is_libur;
                                    } else {
                                        // No schedule for this day: treat as Libur/Cuti
                                        $isLibur = true;
                                    }

                                    // For working days, see if there is exactly one shift so we can color the whole cell
                                    $singleShift = null;
                                    $singleShiftColors = null;
                                    if (!$isLibur) {
                                        $workShifts = $daySchedules->filter(function($item) {
                                            return $item->shift !== null;
                                        });
                                        if ($workShifts->count() === 1) {
                                            $singleShift = $workShifts->first();
                                            $singleShiftColors = $resolveShiftColors($singleShift->shift);
                                        }
                                    }
                                @endphp
                                <td style="@if($isLibur)background:#e74c3c;color:#fff;@elseif($singleShift)background:{{ $singleShiftColors['bg'] }};color:{{ $singleShiftColors['text'] }};@elseif(!$isLibur && !$singleShift && $daySchedules->count() > 0)padding:0;@endif">
                                    @if($isLibur)
                                        <strong>{{ $firstSchedule->label ?? 'Libur/Cuti' }}</strong>
                                    @elseif($singleShift)
                                        @php
                                            $start = \Carbon\Carbon::createFromFormat('H:i:s', $singleShift->shift->start_time)->format('H:i');
                                            $end   = \Carbon\Carbon::createFromFormat('H:i:s', $singleShift->shift->end_time)->format('H:i');
                                        @endphp
                                        <strong>{{ $start }} - {{ $end }}</strong>
                                    @else
                                        @foreach($daySchedules as $scheduleItem)
                                            @if($scheduleItem->shift)
                                                @php
                                                    $start = \Carbon\Carbon::createFromFormat('H:i:s', $scheduleItem->shift->start_time)->format('H:i');
                                                    $end   = \Carbon\Carbon::createFromFormat('H:i:s', $scheduleItem->shift->end_time)->format('H:i');
                                                    $colors = $resolveShiftColors($scheduleItem->shift);
                                                @endphp
                                                <div style="padding:4px 7px; background: {{ $colors['bg'] }}; color: {{ $colors['text'] }}; @if(!$loop->last)border-bottom:1px solid #333;@endif">
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
