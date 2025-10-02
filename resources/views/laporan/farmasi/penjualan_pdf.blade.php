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
                <th>Diskon Nominal</th>
                <th>Diskon (%)</th>
                <th>Diskon Obat Saat Pelayanan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                @php
                    // Support both InvoiceItem-shaped items (with billable->obat) and legacy FakturBeliItem
                    $obat = $item->billable->obat ?? ($item->obat ?? null);
                    $hargaJual = $item->unit_price ?? ($obat->harga_nonfornas ?? $obat->harga_net ?? 0);
                    // If controller provided precomputed fields (string formatted), use them, otherwise compute
                    $diskonNominal = $item->diskon_nominal ?? null;
                    $diskonPersen = $item->diskon_persen ?? null;
                    if (is_null($diskonNominal) || is_null($diskonPersen)) {
                        $discount = $item->discount ?? ($item->diskon ?? 0);
                        $discountType = $item->discount_type ?? ($item->diskon_type ?? 'nominal');
                        $qty = $item->quantity ?? ($item->qty ?? 1);
                        $base = ($item->unit_price ?? ($item->harga ?? 0)) * $qty;
                        $dt = strtolower(trim((string) $discountType));
                        $isPercent = in_array($dt, ['persen', 'percent', '%', 'pct', 'pc', 'per']);
                        $computedNominal = $isPercent ? ($base * $discount / 100) : $discount;
                        $diskonNominal = number_format($computedNominal, 2);
                        $diskonPersen = $isPercent ? $discount : '';
                    }
                    $diskonLabel = (($item->discount ?? $item->diskon ?? 0) > 0) ? 'Ada' : 'Tidak';
                @endphp
                <tr>
                    <td>{{ optional($obat)->nama }}</td>
                    <td>{{ number_format($hargaJual, 2) }}</td>
                    <td>{{ $diskonNominal }}</td>
                    <td>{{ $diskonPersen }}</td>
                    <td>{{ $diskonLabel }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
