<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Jadwal Dokter - Kalender</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f8f9fa;
            color: #222;
            margin: 0;
        }
        .header {
            margin-bottom: 18px;
            padding: 0;
            background: none;
            color: inherit;
            border-radius: 0;
            text-align: left;
            font-size: 1.1em;
            font-weight: 600;
        }
        .calendar-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: fixed;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .calendar-table th, .calendar-table td {
            border: 1px solid #e0e0e0;
            text-align: left;
            vertical-align: top;
            height: 90px;
            width: 16.5%;
            padding: 0.5rem 0.3rem 0.2rem 0.3rem;
            font-size: 13px;
            word-break: break-word;
            line-height: 1.3;
            max-height: 90px;
            overflow: hidden;
            background: #fcfcfc;
        }
        .calendar-table th {
            text-align: center;
            font-weight: 600;
            background: #1976d2;
            color: #fff;
            height: 32px;
            font-size: 16px;
            letter-spacing: 0.5px;
        }
        .calendar-table tr:nth-child(even) td {
            background: #f3f6fa;
        }
        .calendar-day-number {
            font-size: 1.1rem;
            font-weight: bold;
            color: #1976d2;
            margin-bottom: 4px;
            text-align: right;
        }
        .doctor-list {
            margin-top: 0.1rem;
        }
        .doctor-entry {
            background: #e3f2fd;
            border-radius: 6px;
            box-shadow: 0 1px 2px rgba(25,118,210,0.07);
            margin-bottom: 7px;
            padding: 6px 8px 6px 8px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .doctor-name {
            font-weight: 600;
            font-size: 1em;
            color: #1565c0;
            margin-bottom: 2px;
            white-space: pre-line;
            word-break: break-word;
            display: block;
        }
        .doctor-time {
            font-size: 0.97em;
            color: #333;
            margin-bottom: 0;
            padding-top: 2px;
            word-break: break-word;
            display: block;
        }
        @media print {
            body { background: #fff; }
            .header { background: none !important; color: inherit !important; }
            .calendar-table { box-shadow: none; }
            .doctor-entry { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        Jadwal Dokter Bulan {{ \Carbon\Carbon::parse($month.'-01')->translatedFormat('F Y') }}
        @if($clinic)
            - {{ $clinic->nama }}
        @endif
    </div>
    @php
        // Build a map of dokter_id => color. Use a deterministic palette so colors are stable between runs.
        $palette = [
            '#e3f2fd', // light blue
            '#fce4ec', // pink
            '#e8f5e9', // light green
            '#fff3e0', // light orange
            '#ede7f6', // light purple
            '#f3e5f5', // lavender
            '#e0f7fa', // cyan
            '#fffde7', // yellow
            '#fbe9e7', // salmon
            '#f9fbe7', // lime
        ];
        $paletteText = [
            '#1565c0', '#ad1457', '#2e7d32', '#ef6c00', '#5e35b1', '#6a1b9a', '#00796b', '#f9a825', '#d84315', '#827717'
        ];
        $doctorColors = [];
        $allDoctors = $schedules->map(function($s){ return $s->dokter; })->filter()->unique('id')->values();
        foreach($allDoctors as $i => $dokter){
            $id = $dokter->id ?? $i;
            $color = $palette[$i % count($palette)];
            $text = $paletteText[$i % count($paletteText)];
            $doctorColors[$id] = ['bg' => $color, 'text' => $text];
        }
    @endphp
    <table class="calendar-table">
        <thead>
            <tr>
                <th>Minggu</th>
                <th>Senin</th>
                <th>Selasa</th>
                <th>Rabu</th>
                <th>Kamis</th>
                <th>Jumat</th>
                <th>Sabtu</th>
            </tr>
        </thead>
        <tbody>
            @php
                $y = intval(substr($month,0,4));
                $m = intval(substr($month,5,2));
                $days = \Carbon\Carbon::create($y, $m, 1)->daysInMonth;
                $firstDay = \Carbon\Carbon::create($y, $m, 1)->dayOfWeek;
                $dayCell = 0;
            @endphp
            <tr>
            @for($i=0; $i<$firstDay; $i++)
                <td></td>@php $dayCell++; @endphp
            @endfor
            @for($d=1; $d<=$days; $d++)
                @php
                    $dateStr = sprintf('%04d-%02d-%02d', $y, $m, $d);
                    $jadwal = $schedules->where('date', $dateStr);
                    $entries = $jadwal->values();
                    $count = $entries->count();
                    // compute TD style and text color
                    $tdStyle = '';
                    $dayTextColor = '#222';
                    if($count === 1){
                        $j = $entries[0];
                        $doc = $j->dokter;
                        $c = $doctorColors[$doc->id] ?? ['bg' => '#e3f2fd','text'=>'#1565c0'];
                        $tdStyle = "background: {$c['bg']};";
                        $dayTextColor = $c['text'];
                    } elseif($count > 1) {
                        $stops = [];
                        foreach($entries as $idx => $entry){
                            $doc = $entry->dokter;
                            $col = $doctorColors[$doc->id]['bg'] ?? '#e3f2fd';
                            $start = intval($idx / $count * 100);
                            $end = intval((($idx + 1) / $count) * 100);
                            $stops[] = "$col $start% $end%";
                        }
                        $gradient = 'linear-gradient(90deg, '.implode(', ', $stops).')';
                        $tdStyle = "background: {$gradient};";
                        // Use first doctor's text color for labels
                        $firstDoc = $entries[0]->dokter;
                        $dayTextColor = $doctorColors[$firstDoc->id]['text'] ?? '#222';
                    }
                @endphp
                <td style="{{ $tdStyle }}">
                    <div class="calendar-day-number" style="color: {{ $dayTextColor }};">{{ $d }}</div>
                    <div class="doctor-list">
                        @if($count === 1)
                            @php $j = $entries[0]; $doc = $j->dokter; $c = $doctorColors[$doc->id] ?? ['bg' => '#e3f2fd','text'=>'#1565c0']; @endphp
                            <div class="doctor-entry" style="background: transparent; color: {{ $c['text'] }}; border-radius:6px; box-shadow:none; padding:0;">
                                <div style="padding:6px 0 0 0;">
                                    <div class="doctor-name" style="color: {{ $c['text'] }};">{{ $j->dokter->user->name ?? '-' }}</div>
                                    <div class="doctor-time" style="color: {{ $c['text'] }};">{{ $j->jam_mulai }} - {{ $j->jam_selesai }}</div>
                                </div>
                            </div>
                        @elseif($count > 1)
                            @php
                                // entries exist, but TD already has gradient background. make inner transparent.
                            @endphp
                            <div class="doctor-entry" style="background: transparent; box-shadow:none; padding:0; border-radius:6px;">
                                <div style="width:100%; padding:6px; border-radius:6px; background: transparent;">
                                    @foreach($entries as $j)
                                        @php $doc = $j->dokter; $c = $doctorColors[$doc->id] ?? ['text'=>'#1565c0']; @endphp
                                        <div style="color: {{ $c['text'] }}; padding:4px 6px;">{{ $j->dokter->user->name ?? '-' }} — {{ $j->jam_mulai }} - {{ $j->jam_selesai }}</div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            {{-- no entries --}}
                        @endif
                    </div>
                </td>
                @php $dayCell++; @endphp
                @if($dayCell % 7 == 0 && $d != $days)
                    </tr><tr>
                @endif
            @endfor
            @while($dayCell % 7 != 0)
                <td></td>@php $dayCell++; @endphp
            @endwhile
            </tr>
        </tbody>
    </table>
</body>
</html>
