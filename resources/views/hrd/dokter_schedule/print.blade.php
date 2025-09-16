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
            /* use translucent white so underlying cell color (solid or gradient) shows through */
            background: rgba(255,255,255,0.85);
            border-radius: 6px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            margin-bottom: 7px;
            padding: 6px 8px 6px 8px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .doctor-name {
            font-weight: 600;
            font-size: 1em;
            color: #0d335b; /* darker for contrast */
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
                @endphp
                @php
                    // collect unique dokter models for this date
                    $uniqueDokters = $jadwal->pluck('dokter')->filter()->unique('id')->values();
                    $colors = $uniqueDokters->map(function($dok) {
                        // deterministic hue based on dokter id (or 0 if missing)
                        $id = $dok->id ?? 0;
                        $h = ($id * 137) % 360; // pseudo-random spread
                        // softer pastel-like color for cell backgrounds
                        return "hsl({$h}deg, 65%, 92%)";
                    })->toArray();

                    $cellStyle = '';
                    if(count($colors) === 1) {
                        $cellStyle = "background: {$colors[0]};";
                    } elseif(count($colors) > 1) {
                        // build gradient stops evenly
                        $n = count($colors);
                        $stops = [];
                        foreach($colors as $idx => $c) {
                            $pos = (int) round(($idx / ($n - 1)) * 100);
                            $stops[] = "$c $pos%";
                        }
                        $cellStyle = "background: linear-gradient(135deg, " . implode(', ', $stops) . ");";
                    }
                @endphp
                <td style="{{ $cellStyle }}">
                    <div class="calendar-day-number">{{ $d }}</div>
                    <div class="doctor-list">
                        @foreach($jadwal as $j)
                            <div class="doctor-entry">
                                <div class="doctor-name">{{ $j->dokter->user->name ?? '-' }}</div>
                                <div class="doctor-time">{{ $j->jam_mulai }} - {{ $j->jam_selesai }}</div>
                            </div>
                        @endforeach
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
