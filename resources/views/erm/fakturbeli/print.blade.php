<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Faktur Pembelian - {{ $faktur->no_faktur }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #333; padding: 6px; }
        .table th { background: #eee; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Faktur Pembelian</h2>
        <div>No Faktur: <b>{{ $faktur->no_faktur }}</b></div>
        <div>Tanggal Terima: {{ $faktur->received_date }}</div>
        <div>Pemasok: {{ $faktur->pemasok->nama ?? '-' }}</div>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>No</th>
                <th>Obat</th>
                <th>Diminta</th>
                <th>Diterima</th>
                <th>Amount</th>
                <th>Diskon</th>
                <th>Tax</th>
                <th>Total Amount</th>
                <th>Gudang</th>
                <th>Batch</th>
                <th>Exp. Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($faktur->items as $i => $item)
            <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ $item->obat->nama ?? '-' }}</td>
                <td class="right">{{ $item->diminta }}</td>
                <td class="right">{{ $item->qty }}</td>
                <td class="right">{{ number_format($item->harga, 2, ',', '.') }}</td>
                <td class="right">{{ $item->diskon }} {{ $item->diskon_type == 'percent' ? '%' : 'Rp' }}</td>
                <td class="right">{{ $item->tax }} {{ $item->tax_type == 'percent' ? '%' : 'Rp' }}</td>
                <td class="right">{{ number_format($item->total_amount, 2, ',', '.') }}</td>
                <td>{{ $item->gudang->nama ?? '-' }}</td>
                <td>{{ $item->batch }}</td>
                <td>{{ $item->expiration_date }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <table style="width: 100%;">
        <tr>
            <td class="right bold">Subtotal:</td>
            <td class="right">{{ number_format($faktur->subtotal, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="right bold">Global Diskon:</td>
            <td class="right">{{ number_format($faktur->global_diskon, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="right bold">Global Pajak:</td>
            <td class="right">{{ number_format($faktur->global_pajak, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="right bold">Total:</td>
            <td class="right">{{ number_format($faktur->total, 2, ',', '.') }}</td>
        </tr>
    </table>
    <div style="margin-top:40px;">
        <div>Catatan: {{ $faktur->notes }}</div>
    </div>
</body>
</html>
