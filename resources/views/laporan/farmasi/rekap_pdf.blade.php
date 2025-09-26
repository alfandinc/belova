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
    <h2>Laporan Rekap Pembelian Farmasi</h2>
    <table>
        <thead>
            <tr>
                <th>Nama Pemasok</th>
                <th>Nama Obat</th>
                <th>Harga Beli/Satuan</th>
                <th>Diskon Nominal</th>
                <th>Diskon (%)</th>
                <th>Harga Jadi (Setelah Diskon + PPN)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                @php
                    $harga = $item->harga;
                    $diskon = $item->diskon ?? 0;
                    $diskonType = $item->diskon_type ?? 'nominal';
                    $tax = $item->tax ?? 0;
                    $taxType = $item->tax_type ?? 'nominal';
                    $qty = $item->qty ?? 1;
                    $base = $harga * $qty;
                    $dt = strtolower(trim((string) $diskonType));
                    $isPercent = in_array($dt, ['persen', 'percent', '%', 'pct', 'pc', 'per']);
                    $diskonValue = $isPercent ? ($base * $diskon / 100) : $diskon;
                    $taxValue = $taxType === 'persen' ? ($base * $tax / 100) : $tax;
                    $hargaJadi = $base - $diskonValue + $taxValue;
                @endphp
                <tr>
                    <td>{{ optional($item->fakturbeli->pemasok)->nama }}</td>
                    <td>{{ optional($item->obat)->nama }}</td>
                    <td>{{ number_format($harga, 2) }}</td>
                    <td>{{ number_format($diskonValue, 2) }}</td>
                    <td>{{ $isPercent ? $diskon : '' }}</td>
                    <td>{{ number_format($hargaJadi, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
