<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Mutasi Stok - {{ $mutasi->nomor_mutasi }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 12px; }
        .meta { width: 100%; margin-bottom: 12px; border-collapse: collapse; }
        .meta td { padding: 4px 6px; vertical-align: top; border: none; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 6px; }
        th { background: #f0f0f0; }
        .small { font-size: 11px; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h3>Mutasi Stok</h3>
        <div>No. Mutasi: <strong>{{ $mutasi->nomor_mutasi }}</strong></div>
    </div>

    <table class="meta small">
        <tr>
            <td style="width: 50%;">
                <strong>Tanggal:</strong> {{ optional($mutasi->created_at)->format('d/m/Y H:i') }}<br>
                <strong>Gudang:</strong> {{ $mutasi->gudang->nama ?? '-' }}<br>
                <strong>Jenis:</strong> {{ ucfirst($mutasi->jenis_mutasi) }}<br>
                <strong>Dibuat Oleh:</strong> {{ $mutasi->user->name ?? '-' }}
            </td>
            <td style="width: 50%;">
                <strong>Status:</strong> {{ ucfirst($mutasi->status) }}<br>
                <strong>Dibatalkan Oleh:</strong> {{ $mutasi->cancelledBy->name ?? '-' }}<br>
                <strong>Tanggal Batal:</strong> {{ optional($mutasi->cancelled_at)->format('d/m/Y H:i') ?? '-' }}<br>
                <strong>Revisi Dari:</strong> {{ $mutasi->revisedFrom->nomor_mutasi ?? '-' }}
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width: 6%;">#</th>
                <th>Nama Obat</th>
                <th style="width: 14%;">Jumlah</th>
                <th style="width: 18%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mutasi->items as $index => $item)
                <tr>
                    <td style="text-align:center">{{ $index + 1 }}</td>
                    <td>{{ $item->obat->nama ?? ('Obat ID ' . $item->obat_id) }}</td>
                    <td class="text-right">{{ number_format((float) $item->jumlah, 2, ',', '.') }} {{ $item->obat->satuan ?? '' }}</td>
                    <td>{{ $item->keterangan ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 24px; display: flex; justify-content: space-between;">
        <div style="text-align: center; width: 40%;">
            <div>Dibuat Oleh</div>
            <div style="height: 60px;"></div>
            <div><strong>{{ $mutasi->user->name ?? '-' }}</strong></div>
        </div>
        <div style="text-align: center; width: 40%;">
            <div>Diketahui</div>
            <div style="height: 60px;"></div>
            <div><strong>{{ $mutasi->cancelledBy->name ?? '-' }}</strong></div>
        </div>
    </div>
</body>
</html>