<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 6px; font-size: 12px; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h2>Laporan Rekap Penjualan Obat</h2>
    <table>
        <thead>
            <tr>
                <th>Nama Obat</th>
                <th>Harga Jual</th>
                <th>Diskon Obat Saat Pelayanan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                @php
                    $hargaJual = $item->obat->harga_nonfornas ?? $item->obat->harga_net ?? 0;
                    $diskon = ($item->diskon ?? 0) > 0 ? 'Ada' : 'Tidak';
                @endphp
                <tr>
                    <td>{{ optional($item->obat)->nama }}</td>
                    <td>{{ number_format($hargaJual, 2) }}</td>
                    <td>{{ $diskon }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
