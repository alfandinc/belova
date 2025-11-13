<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Retur Pembelian - {{ $retur->retur_number ?? $retur->id }}</title>
    <style>
        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 8px }
        .muted { color: #666; font-size: 11px }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px 8px; border: 1px solid #ddd; }
        th { background: #f5f5f5; }
        .right { text-align: right }
    </style>
</head>
<body>
    <div class="header">
        <h3>Retur Pembelian</h3>
        <div class="muted">No: {{ $retur->retur_number }}</div>
    </div>

    <div style="margin-bottom:10px;">
        <strong>No. Invoice:</strong> {{ $retur->invoice->invoice_number ?? '-' }}<br>
        <strong>Tanggal:</strong> {{ $retur->processed_date ? $retur->processed_date->format('d/m/Y H:i') : '-' }}<br>
        <strong>User:</strong> {{ $retur->user->name ?? '-' }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th class="right">Original</th>
                <th class="right">After Cut</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($retur->items as $it)
            <tr>
                <td>{{ $it->name }}</td>
                <td class="right">{{ $it->quantity_returned }}</td>
                <td class="right">Rp {{ number_format($it->original_unit_price, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($it->unit_price, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($it->total_amount, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="right"><strong>Total</strong></td>
                <td class="right"><strong>Rp {{ number_format($retur->total_amount, 0, ',', '.') }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top:10px;">
        <strong>Alasan:</strong> {{ $retur->reason }}<br>
        <strong>Catatan:</strong> {{ $retur->notes ?? '-' }}
    </div>
</body>
</html>