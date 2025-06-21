<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Nota {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            margin: 0;
            padding: 0;
            width: 100%;
        }
        .header {
            text-align: center;
            margin-bottom: 8px;
        }
        .header h3 {
            margin: 0;
            font-size: 10pt;
        }
        .header p {
            margin: 0;
            font-size: 7pt;
        }
        .info {
            margin-bottom: 8px;
            font-size: 7pt;
        }
        .info-row {
            margin: 2px 0;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            font-size: 7pt;
        }
        table.items th, table.items td {
            text-align: left;
            padding: 1px 2px;
        }
        .item-row td {
            border-top: 1px dotted #ccc;
            border-bottom: 1px dotted #ccc;
        }
        .total-section {
            margin-top: 5px;
            text-align: right;
        }
        .total-row {
            font-size: 7pt;
            margin: 2px 0;
        }
        .grand-total {
            font-weight: bold;
            font-size: 8pt;
            margin-top: 3px;
        }
        .footer {
            margin-top: 10px;
            text-align: center;
            font-size: 7pt;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h3>KLINIK BELOVA</h3>
        <p>{{ $invoice->visitation->klinik->nama ?? 'KLINIK' }}</p>
        <p>{{ Carbon\Carbon::parse($invoice->created_at)->format('d/m/Y H:i') }}</p>
    </div>

    <div class="info">
        <div class="info-row">
            <strong>No:</strong> {{ $invoice->invoice_number }}
        </div>
        <div class="info-row">
            <strong>Pasien:</strong> {{ $invoice->visitation->pasien->nama ?? '-' }}
        </div>
    </div>

    <table class="items">
        <tr>
            <th width="55%">Item</th>
            <th width="15%">Qty</th>
            <th width="30%" class="text-right">Harga</th>
        </tr>
        
        @foreach($invoice->items as $item)
        <tr class="item-row">
            <td>{{ $item->name }}</td>
            <td>{{ $item->quantity }}</td>
            <td class="text-right">{{ number_format($item->final_amount, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </table>

    <div class="total-section">
        <div class="total-row">
            <strong>Subtotal:</strong> Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}
        </div>
        @if($invoice->discount > 0)
        <div class="total-row">
            <strong>Diskon:</strong> Rp {{ number_format($invoice->discount, 0, ',', '.') }}
        </div>
        @endif
        @if($invoice->tax > 0)
        <div class="total-row">
            <strong>Pajak:</strong> Rp {{ number_format($invoice->tax, 0, ',', '.') }}
        </div>
        @endif
        <div class="grand-total">
            <strong>TOTAL:</strong> Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
        </div>
    </div>

    <div class="footer">
        <p>Terima kasih atas kunjungan Anda</p>
        <p>{{ date('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>