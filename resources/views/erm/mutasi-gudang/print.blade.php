<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Mutasi Gudang - {{ $mutasi->nomor_mutasi }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 10px }
        .meta { margin-bottom: 10px }
        .meta td { padding: 3px 8px }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 6px; }
        th { background: #f0f0f0; }
        .small { font-size: 11px }
    </style>
</head>
<body>
    <div class="header">
        <h3>Mutasi Gudang</h3>
        <div>No Mutasi: <strong>{{ $mutasi->nomor_mutasi }}</strong></div>
    </div>

    <table class="meta small" style="border: none; margin-bottom: 6px;">
        <tr style="border: none;">
            <td style="border: none; width: 50%">
                <strong>Tanggal:</strong> {{ $mutasi->created_at->format('d/m/Y H:i') }}<br>
                <strong>Gudang Asal:</strong> {{ $mutasi->gudangAsal->nama ?? '-' }}<br>
                <strong>Diminta Oleh:</strong> {{ $mutasi->requestedBy->name ?? '-' }}
            </td>
            <td style="border: none; width: 50%">
                <strong>Gudang Tujuan:</strong> {{ $mutasi->gudangTujuan->nama ?? '-' }}<br>
                <strong>Disetujui Oleh:</strong> {{ $mutasi->approvedBy->name ?? '-' }}<br>
                <strong>Status:</strong> {{ ucfirst($mutasi->status) }}
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width:6%;">#</th>
                <th>Nama Obat</th>
                <th style="width:8%;">Jumlah Diminta</th>
                <th style="width:8%;">Jumlah Disetujui</th>
                <th style="width:20%;">Keterangan</th>
                <th style="width:10%;">Stok Asal (Sebelum)</th>
                <th style="width:10%;">Stok Asal (Setelah)</th>
                <th style="width:10%;">Stok Tujuan (Sebelum)</th>
                <th style="width:10%;">Stok Tujuan (Setelah)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mutasi->items as $i => $item)
                <tr>
                    <td style="text-align:center">{{ $i + 1 }}</td>
                    <td>{{ $item->obat->nama ?? ('Obat ID ' . $item->obat_id) }}</td>
                    <td style="text-align:right">{{ $item->jumlah }}</td>
                    <td style="text-align:right">{{ $item->jumlah }}</td>
                    <td>{{ $item->keterangan ?? '-' }}</td>
                    <td style="text-align:right">{{ number_format($item->stok_asal_sebelum ?? 0, 2, ',', '.') }}</td>
                    <td style="text-align:right">{{ number_format($item->stok_asal_setelah ?? 0, 2, ',', '.') }}</td>
                    <td style="text-align:right">{{ number_format($item->stok_tujuan_sebelum ?? 0, 2, ',', '.') }}</td>
                    <td style="text-align:right">{{ number_format($item->stok_tujuan_setelah ?? 0, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px;">
        <strong>Keterangan Mutasi:</strong>
        <div style="min-height:40px">{{ $mutasi->keterangan ?? '-' }}</div>
    </div>

    <div style="margin-top:30px; display:flex; justify-content:space-between;">
        <div style="text-align:center">
            <div>Diminta oleh</div>
            <div style="height:60px"></div>
            <div><strong>{{ $mutasi->requestedBy->name ?? '-' }}</strong></div>
        </div>
        <div style="text-align:center">
            <div>Disetujui oleh</div>
            <div style="height:60px"></div>
            <div><strong>{{ $mutasi->approvedBy->name ?? '-' }}</strong></div>
        </div>
    </div>
</body>
</html>
