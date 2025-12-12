<!doctype html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Export Inventaris</title>
    <style>
        @page { size: A5 portrait; margin: 8mm 8mm; }
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size:10px; margin:0; }
        .pdf-title { font-size:14px; font-weight:700; margin:18px 0 6px 0; }
        table { width:100%; border-collapse: collapse; font-size:10px }
        th, td { border: 1px solid #444; padding:4px; }
        th { background:#eee; }
        .room-header { background:#ddd; font-weight:600; padding:6px; }
        .page-break { page-break-after: always; }
        /* print date styled to appear below each table */
        .print-date { font-size:9px; text-align:right; margin-top:6px; }
        .content { margin-top: 0; }
    </style>
</head>
<body>
        @if(!empty($roomTitle) && empty($groups))
            <h3 class="pdf-title">Daftar Barang Kamar {{ $roomTitle }}</h3>
        @endif

    <div class="content">
        @foreach($groups as $gindex => $group)
            <h3 class="pdf-title">Daftar Barang Kamar {{ $group['room_name'] }}{{ $group['category_name'] ? ' (' . $group['category_name'] . ')' : '' }}</h3>

            <table>
                <thead>
                    <tr>
                        <th style="width:6%">No</th>
                        <th style="width:28%">Nomor Inv</th>
                        <th style="width:44%">Nama</th>
                        <th style="width:11%">Check In</th>
                        <th style="width:11%">Check Out</th>
                    </tr>
                </thead>
                <tbody>
                    @php $no = 1; @endphp
                    @foreach($group['items'] as $inv)
                    <tr>
                        <td class="text-center">{{ $no++ }}</td>
                        <td>{{ $inv->inv_number }}</td>
                        <td>{{ $inv->name }}</td>
                        <td class="text-center">&nbsp;</td>
                        <td class="text-center">&nbsp;</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="print-date">Tanggal Cetak: {{ \Carbon\Carbon::now()->format('Y-m-d H:i') }}</div>

            @if($gindex < count($groups) - 1)
                <div class="page-break"></div>
            @endif
        @endforeach
    
</body>
</html>