<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar SPK Tindakan</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #111; }
        .container { width: 100%; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .header .title { font-size: 18px; font-weight: bold; }
        .meta { margin-bottom: 12px; }
        .meta-grid { display: grid; grid-template-columns: 1fr; gap: 6px; }
        /* Each row renders two left-aligned columns */
        .meta-grid .grid-row { display: grid; grid-template-columns: 1fr 1fr; align-items: baseline; }
        .meta-grid .item { white-space: nowrap; }
        .visit-block { border: none; padding: 8px 0; margin-bottom: 18px; }
        .tindakan-title { text-align: center; font-weight: 700; padding: 8px 4px; }
        .tindakan-section { text-align: left; font-weight: 700; padding: 6px 4px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 6px; vertical-align: top; }
        th { text-align: left; }
        .col-header td { font-weight: 700; background: #e9ecef; }
        .check-cell { text-align: center; }
        .check-box { display: inline-block; width: 12px; height: 12px; border: 1px solid #000; }
        .no-border { border: none !important; }
        .small { font-size: 11px; color: #333; }
        .print-controls { margin: 10px 0; }
        @media print { .print-controls { display: none; } body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="title">Daftar SPK Tindakan</div>
        <div class="print-controls">
            <button onclick="window.print()">Print</button>
            <button onclick="window.close()">Close</button>
        </div>
    </div>
    <div class="meta">
        @php
            try {
                $headerTanggal = \Carbon\Carbon::parse($currentVisitation->tanggal_visitation)->locale('id')->isoFormat('D MMMM YYYY');
            } catch (\Exception $e) { $headerTanggal = $currentVisitation->tanggal_visitation; }
            $headerDokter = $currentVisitation->dokter->user->name ?? '-';
        @endphp
        <div class="meta-grid">
            <div class="grid-row">
                <div class="item"><strong>Pasien:</strong> {{ $currentVisitation->pasien->nama ?? '-' }}</div>
                <div class="item"><strong>Dokter:</strong> {{ strtoupper($headerDokter) }}</div>
            </div>
            <div class="grid-row">
                <div class="item"><strong>No. RM:</strong> {{ $currentVisitation->pasien->id ?? '-' }}</div>
                <div class="item"><strong>Kunjungan:</strong> {{ $headerTanggal }}</div>
            </div>
        </div>
    </div>

    @foreach($groups as $group)
        @php
            $visit = $group['visitation'];
            try {
                $tanggal = \Carbon\Carbon::parse($visit->tanggal_visitation)->locale('id')->isoFormat('D MMMM YYYY');
            } catch (\Exception $e) { $tanggal = $visit->tanggal_visitation; }
        @endphp
        <div class="visit-block">
            @if(!empty($group['riwayats']))
                <table class="tindakan-table">
                    <tbody>
                        @foreach($group['riwayats'] as $r)
                            <tr>
                                <td colspan="4" class="tindakan-section">{{ strtoupper($r['tindakan_nama']) }}</td>
                            </tr>
                            <tr class="col-header">
                                <td style="width: 35%">Kode Tindakan</td>
                                <td style="width: 40%">Obat/BHP</td>
                                <td style="width: 20%">Dosis</td>
                                <td style="width: 5%">Check</td>
                            </tr>
                            @forelse($r['kode_items'] as $item)
                                @php $rows = max(count($item['obats']), 1); @endphp
                                @if($rows === 1)
                                    @php
                                        $ob = $item['obats'][0] ?? null;
                                        $dText = '';
                                        if ($ob) {
                                            if (!empty($ob['qty'])) { $dText = 'x '. $ob['qty'] . (!empty($ob['satuan']) ? (' ' . strtolower($ob['satuan'])) : ''); }
                                            if (!empty($ob['dosis'])) { $dText = trim($dText . (empty($dText) ? '' : ' — ') . $ob['dosis']); }
                                            if (empty($dText)) { $dText = '-'; }
                                        }
                                    @endphp
                                    <tr>
                                        <td><div><strong>{{ $item['kode'] }}</strong></div><div class="small">{{ $item['nama'] }}</div></td>
                                        <td>{{ $ob['nama'] ?? '' }}</td>
                                        <td>{{ $dText }}</td>
                                        <td class="check-cell"><span class="check-box"></span></td>
                                    </tr>
                                @else
                                    @foreach($item['obats'] as $idx => $ob)
                                        <tr>
                                            @if($idx === 0)
                                                <td rowspan="{{ $rows }}"><div><strong>{{ $item['kode'] }}</strong></div><div class="small">{{ $item['nama'] }}</div></td>
                                            @endif
                                            <td>{{ $ob['nama'] }}</td>
                                            <td>
                                                @php
                                                    $dText = '';
                                                    if (!empty($ob['qty'])) { $dText = 'x '. $ob['qty'] . (!empty($ob['satuan']) ? (' ' . strtolower($ob['satuan'])) : ''); }
                                                    if (!empty($ob['dosis'])) { $dText = trim($dText . (empty($dText) ? '' : ' — ') . $ob['dosis']); }
                                                    if (empty($dText)) { $dText = '-'; }
                                                @endphp
                                                {{ $dText }}
                                            </td>
                                            @if($idx === 0)
                                                <td class="check-cell" rowspan="{{ $rows }}"><span class="check-box"></span></td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @endif
                            @empty
                                <tr><td colspan="4" class="small">Tidak ada kode tindakan.</td></tr>
                            @endforelse
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="small">Tidak ada tindakan pada kunjungan ini.</div>
            @endif
        </div>
    @endforeach
</div>
<script>
// Auto-print on load for convenience
window.addEventListener('load', function(){ setTimeout(function(){ window.print(); }, 200); });
</script>
</body>
</html>
