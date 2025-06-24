<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Nota {{ $invoice->invoice_number }}</title>
    <style>
        html, body {
            width: 100%;
            margin: 20px;
            padding: 0;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            margin: 0 14px; /* Increased left and right margin */
            padding: 0;
            width: auto;
            line-height: 1.7;
        }        .header {
            width: 100%;
            margin-bottom: 10px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
            padding: 0;
        }
        .logo-cell {
            width: 65px;
            padding-right: 10px;
        }
        .info-cell {
            width: auto;
        }
        .header h3 {
            margin: 0;
            font-size: 12pt;
        }
        .header p {
            margin: 0;
            font-size: 9pt;
        }
        .info {
            margin-bottom: 2px;
            font-size: 9pt;
            padding: 0;
        }
        .info-row {
            margin: 2px 0;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin: 0;
            padding: 0;
        }
        table.items th, table.items td {
            text-align: left;
            padding: 6px 0; /* Increased vertical padding for more space between items */
        }
        .item-row td {
            border-top: 1px dotted #ccc;
            border-bottom: 1px dotted #ccc;
        }
        .items td.text-right, .items th.text-right {
            text-align: right;
        }
        .total-section {
            margin-top: 1px;
            text-align: right;
            padding: 0;
        }
        .total-row {
            font-size: 9pt;
            margin: 0;
        }
        .grand-total {
            font-weight: bold;
            font-size: 10pt;
            margin-top: 0;
        }
        .footer {
            margin-top: 2px;
            text-align: center;
            font-size: 9pt;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if(!empty($logoBase64))
                        <img src="{{ $logoBase64 }}" alt="Logo" style="width:55px; height:auto;">
                    @endif
                </td>
                <td class="info-cell">
                    <p style="font-weight: bold; font-size: 11pt; margin: 0; line-height: 1.2;">KLINIK PRATAMA BELOVA SKIN & BEAUTY CENTER</p>
                    <p style="font-size: 9pt; margin-top: 5px;">{{ Carbon\Carbon::parse($invoice->created_at)->format('d/m/Y H:i') }}</p>
                </td>
            </tr>        </table>
    </div>

    <hr style="border: 0; border-top: 1px solid #000; margin: 6px 0;">

    <div class="info">
        <div class="info-row">
            <strong>No:</strong> {{ $invoice->invoice_number }}
        </div>
        <div class="info-row">
            <strong>ID Pasien:</strong> {{ $invoice->visitation->pasien->id ?? '-' }}
        </div>
        <div class="info-row">
            <strong>Pasien:</strong> {{ $invoice->visitation->pasien->nama ?? '-' }}
        </div>
        <div class="info-row">
            <strong>Dokter:</strong> {{ $invoice->visitation->dokter->user->name ?? '-' }}
        </div>
        <div class="info-row">
            <strong>Kasir:</strong> {{ auth()->user()->name ?? '-' }}
        </div>
    </div>

    <hr style="border: 0; border-top: 1px solid #000; margin: 6px 0;">

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
        <p>Barang yang sudah dibeli tidak dapat dikembalikan</p>
    </div>
</body>
</html>