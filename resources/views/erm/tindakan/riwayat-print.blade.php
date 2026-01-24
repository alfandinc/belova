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
        .obat-small { font-size: 9px; color: #333; line-height:1.1; }
        .keterangan { float: right; width: 36%; font-size: 11px; margin-top: 12px; }
        .keterangan table { border: none !important; }
        .keterangan table td { vertical-align: top; padding: 2px 6px; border: none !important; }
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
            $selectedBeautician = null;
            if (request()->get('beautician_id')) {
                try { $selectedBeautician = \App\Models\User::find(request()->get('beautician_id')); } catch (\Exception $e) { $selectedBeautician = null; }
            }
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
                
                @if($selectedBeautician)
                    <div class="grid-row">
                        <div class="item"></div>
                        <div class="item"><div class="small mt-1"><strong>Beautician :</strong> {{ $selectedBeautician->name }}</div></div>
                    </div>
                @endif
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
                                <td colspan="3" class="tindakan-section">{{ strtoupper($r['tindakan_nama']) }}</td>
                                <td class="tindakan-section" style="text-align:center; vertical-align:middle; font-weight:700;">Checklist Cuci Tangan</td>
                            </tr>
                            <tr class="col-header">
                                <td style="width:4%">No</td>
                                <td style="width:70%">Kode Tindakan</td>
                                <td style="width:21%">Penanggung Jawab</td>
                                <td style="width:5%">
                                    <table style="width:100%; border-collapse:collapse; font-size:10px; border:none;">
                                        <tr>
                                            <td style="border:none; font-weight:700; text-align:center;">SBK</td>
                                            <td style="border:none; font-weight:700; text-align:center;">SBA</td>
                                            <td style="border:none; font-weight:700; text-align:center;">SDC</td>
                                            <td style="border:none; font-weight:700; text-align:center;">SDK</td>
                                            <td style="border:none; font-weight:700; text-align:center;">SDL</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            @forelse($r['kode_items'] as $item)
                                <tr>
                                        <td style="text-align:center; vertical-align:top;">{{ $loop->iteration }}</td>
                                        <td>
                                        <div><strong>{{ $item['kode'] }}</strong></div>
                                        <div class="small"><strong>{{ $item['nama'] }}</strong></div>
                                        @if(!empty($item['obats']) && count($item['obats']) > 0)
                                            <div class="small" style="margin-top:6px;">
                                                @foreach($item['obats'] as $ob)
                                                    @php
                                                        $dText = '';
                                                        if (!empty($ob['qty'])) { $dText = 'x '. $ob['qty'] . (!empty($ob['satuan']) ? (' ' . strtolower($ob['satuan'])) : ''); }
                                                        if (!empty($ob['dosis'])) { $dText = trim($dText . (empty($dText) ? '' : ' — ') . $ob['dosis']); }
                                                        if (empty($dText)) { $dText = ''; }
                                                    @endphp
                                                    <div class="obat-small">- {{ $ob['nama'] ?? '' }}{!! $dText ? ': <strong>' . e($dText) . '</strong>' : '' !!}</div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td></td>
                                    <td class="check-cell">
                                        <div style="font-size:10px; text-align:center; width:100%;">
                                            <table style="width:100%; border-collapse:collapse; font-size:10px; border:none;">
                                                <tr>
                                                    @for($j = 0; $j < 5; $j++)
                                                        <td style="padding:6px; text-align:center; border:none;"><span style="display:inline-block; width:14px; height:14px; border:1px solid #000;"></span></td>
                                                    @endfor
                                                </tr>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
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
    <div class="keterangan">
        <div style="font-weight:700; margin-bottom:6px;">Keterangan:</div>
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="width:50%;">
                    <div><strong>SBK:</strong> sebelum kontak</div>
                    <div style="margin-top:6px;"><strong>SBA:</strong> sebelum tindakan</div>
                    <div style="margin-top:6px;"><strong>SDC:</strong> sebelum terkena</div>
                </td>
                <td style="width:50%;">
                    <div><strong>SDK:</strong> setelah kontak</div>
                    <div style="margin-top:6px;"><strong>SDL:</strong> setelah keluar area</div>
                </td>
            </tr>
        </table>
    </div>
</div>
<script>
// Auto-print on load for convenience
window.addEventListener('load', function(){ setTimeout(function(){ window.print(); }, 200); });
</script>
</body>
</html>
