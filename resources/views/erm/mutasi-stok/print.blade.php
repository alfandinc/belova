<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Penyesuaian Stok - {{ $mutasi->nomor_mutasi }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 12px; }
        .header h3 { margin: 0 0 4px; }
        .meta { width: 100%; margin-bottom: 12px; border-collapse: collapse; }
        .meta td { padding: 3px 6px; vertical-align: top; border: none; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th, table.items td { border: 1px solid #333; padding: 5px; }
        table.items th { background: #f0f0f0; }
        .text-right { text-align: right; }
        .status { font-weight: bold; }
        .sign { width: 100%; margin-top: 28px; border-collapse: collapse; }
        .sign td { width: 50%; text-align: center; border: none; }
    </style>
</head>
<body>
    @php
        $statusLabel = [
            'pending' => 'MENUNGGU APPROVAL (stok belum berubah)',
            'done' => 'DIPOSTING',
            'rejected' => 'DITOLAK',
            'cancelled' => 'DIBATALKAN',
        ][$mutasi->status] ?? strtoupper($mutasi->status);
    @endphp

    <div class="header">
        <h3>Penyesuaian Stok {{ $mutasi->jenis_mutasi === 'masuk' ? 'Masuk' : 'Keluar' }}</h3>
        <div>No. Dokumen: <strong>{{ $mutasi->nomor_mutasi }}</strong></div>
        <div class="status">{{ $statusLabel }}</div>
    </div>

    <table class="meta">
        <tr>
            <td style="width: 50%;">
                <strong>Tanggal:</strong> {{ optional($mutasi->tanggal_mutasi)->format('d/m/Y H:i') ?? '-' }}<br>
                <strong>Tanggal Input:</strong> {{ optional($mutasi->tanggal_input)->format('d/m/Y H:i') ?? '-' }}<br>
                <strong>Gudang:</strong> {{ $mutasi->gudang->nama ?? '-' }}<br>
                <strong>Dibuat Oleh:</strong> {{ $mutasi->user->name ?? '-' }}
            </td>
            <td style="width: 50%;">
                <strong>Nilai Dokumen:</strong> Rp {{ number_format((float) $mutasi->total_nilai, 0, ',', '.') }}<br>
                @if ($mutasi->approvedBy)
                    <strong>Disetujui:</strong> {{ $mutasi->approvedBy->name }} ({{ optional($mutasi->approved_at)->format('d/m/Y H:i') }})<br>
                @endif
                @if ($mutasi->rejectedBy)
                    <strong>Ditolak:</strong> {{ $mutasi->rejectedBy->name }} ({{ optional($mutasi->rejected_at)->format('d/m/Y H:i') }})<br>
                    <strong>Alasan Tolak:</strong> {{ $mutasi->rejected_reason }}<br>
                @endif
                @if ($mutasi->cancelledBy)
                    <strong>Dibatalkan:</strong> {{ $mutasi->cancelledBy->name }} ({{ optional($mutasi->cancelled_at)->format('d/m/Y H:i') }})<br>
                @endif
                @if ($mutasi->revisedFrom)
                    <strong>Revisi Dari:</strong> {{ $mutasi->revisedFrom->nomor_mutasi }}
                @endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th>Nama Obat</th>
                <th style="width: 15%;">Batch</th>
                <th style="width: 11%;">Jumlah</th>
                <th style="width: 13%;">Alasan</th>
                <th style="width: 20%;">Keterangan</th>
                <th style="width: 12%;">Nilai (HPP)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mutasi->items as $index => $item)
                @php
                    $alasan = $item->alasan;
                    $keterangan = $item->keterangan;
                    if (!$alasan && $keterangan && preg_match('/^\[([^\]]+)\]\s*(.*)$/s', $keterangan, $m)) {
                        $alasan = $m[1];
                        $keterangan = $m[2];
                    }
                    $batch = $item->batch_dipilih ? ($item->batch ?? '(tanpa batch)') . ($item->batch_baru ? ' (baru)' : '') : 'ED terdekat';
                @endphp
                <tr>
                    <td style="text-align:center">{{ $index + 1 }}</td>
                    <td>{{ $item->obat->nama ?? ('Obat ID ' . $item->obat_id) }}</td>
                    <td>{{ $batch }}</td>
                    <td class="text-right">{{ rtrim(rtrim(number_format((float) $item->jumlah, 2, ',', '.'), '0'), ',') }} {{ $item->obat->satuan_stok_label ?? '' }}</td>
                    <td>{{ $alasan ?: '-' }}</td>
                    <td>{{ $keterangan ?: '-' }}</td>
                    <td class="text-right">{{ (float) $item->nilai > 0 ? 'Rp ' . number_format((float) $item->nilai, 0, ',', '.') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="sign">
        <tr>
            <td>Dibuat Oleh</td>
            <td>Disetujui Oleh</td>
        </tr>
        <tr>
            <td style="height: 60px;"></td>
            <td></td>
        </tr>
        <tr>
            <td><strong>{{ $mutasi->user->name ?? '-' }}</strong></td>
            <td><strong>{{ $mutasi->approvedBy->name ?? ($mutasi->butuh_approval ? '....................' : '(di bawah ambang approval)') }}</strong></td>
        </tr>
    </table>
</body>
</html>
