<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Nota {{ $invoice->invoice_number }}</title>
    <style>
        html, body {
            width: 100%;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            margin: 0;
            padding: 8px;
            width: auto;
            line-height: 1.9;
            max-width: 100%;
            color: #000;
            box-sizing: border-box;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #000;
        }
        .header img {
            max-width: 250px;
            height: auto;
            margin: 0 auto;
        }
        .company-name {
            font-weight: bold;
            font-size: 11pt;
            margin: 0 0 2px 0;
            letter-spacing: 0.5px;
        }
        .company-tagline {
            font-size: 8pt;
            margin: 0 0 3px 0;
            font-weight: normal;
        }
        .company-id {
            font-size: 7pt;
            margin: 0;
            font-weight: normal;
        }
        .receipt-info {
            margin-bottom: 10px;
            font-size: 8pt;
            line-height: 1.8;
            border-bottom: 1px solid #000;
            padding-bottom: 6px;
        }
        .receipt-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .receipt-info td {
            padding: 2px 0;
            vertical-align: top;
        }
        .receipt-info .label {
            width: 40px;
            font-weight: bold;
            padding-right: 4px;
        }
        .receipt-info .colon {
            width: 10px;
            text-align: left;
            padding-right: 4px;
        }
        .receipt-info .value {
            width: auto;
        }
        .items-section {
            margin-bottom: 10px;
            border-bottom: 1px solid #000;
            padding-bottom: 6px;
        }
        .item-line {
            margin: 3px 0;
            font-size: 8pt;
        }
        .item-name {
            font-weight: normal;
            margin-bottom: 1px;
        }
        .item-table {
            width: 100%;
            margin-left: 6px;
        }
        .item-table td {
            padding: 0;
            vertical-align: top;
        }
        .item-table .qty-price {
            width: 60%;
            font-size: 8pt;
        }
        .item-table .amount {
            width: 40%;
            font-size: 8pt;
            text-align: right;
            font-weight: bold;
        }
        .totals-section {
            font-size: 8pt;
            margin-bottom: 10px;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 1px 0;
            vertical-align: top;
        }
        .totals-table .total-label {
            width: 60%;
            font-weight: normal;
        }
        .totals-table .total-amount {
            width: 40%;
            font-weight: normal;
            text-align: right;
        }
        .totals-table .total-amount.bold {
            font-weight: bold;
        }
        .totals-table .payment-separator {
            border-top: 1px solid #000;
            padding-top: 5px;
        }
        .totals-table .total-row {
            padding-bottom: 3px;
        }
        .message-section {
            text-align: center;
            margin: 100px 0 8px 0;
            font-size: 7pt;
            line-height: 1.2;
        }
        .thank-you {
            font-weight: bold;
            margin-top: 3px;
        }
        .footer {
            text-align: center;
            font-size: 6pt;
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px solid #000;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        @php
            $klinikId = $invoice->visitation->klinik_id ?? 2;
            $logoBase64 = '';
            
            switch($klinikId) {
                case 1:
                    $logoPath = public_path('img/logo-premiere.png');
                    break;
                case 2:
                    $logoPath = public_path('img/logo-belovaskin.png');
                    break;
                default:
                    $logoPath = public_path('img/logo-belovaskin.png');
                    break;
            }
            
            // Convert image to base64
            if (file_exists($logoPath)) {
                $imageData = base64_encode(file_get_contents($logoPath));
                $imageMimeType = mime_content_type($logoPath);
                $logoBase64 = 'data:' . $imageMimeType . ';base64,' . $imageData;
            }
        @endphp
        
        @if(!empty($logoBase64))
            <img src="{{ $logoBase64 }}" alt="Logo" />
        @else
            <div class="company-name">BELOVA</div>
            <div class="company-tagline">SKIN & BEAUTY CENTER</div>
        @endif
    </div>

    <div class="receipt-info">
        <table>
            <tr>
                <td class="label">Tgl</td>
                <td class="colon"> : </td>
                <td class="value">{{ Carbon\Carbon::parse($invoice->created_at)->format('d M Y H:i') }}</td>
            </tr>
            <tr>
                <td class="label">No RM</td>
                <td class="colon"> : </td>
                <td class="value">{{ $invoice->visitation->pasien->id ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Nama</td>
                <td class="colon"> : </td>
                <td class="value">{{ strtoupper($invoice->visitation->pasien->nama ?? '-') }}</td>
            </tr>
            <tr>
                <td class="label">Dokter</td>
                <td class="colon"> : </td>
                <td class="value">{{ $invoice->visitation->dokter->user->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Kasir</td>
                <td class="colon"> : </td>
                <td class="value">{{ auth()->user()->name ?? 'administrator' }}</td>
            </tr>
        </table>
    </div>

    <div class="items-section">
        @foreach($invoice->items as $item)
        <div class="item-line">
            <div class="item-name">{{ $item->name }}</div>
            <table class="item-table">
                <tr>
                    <td class="qty-price">{{ $item->quantity }} x {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="amount">{{ number_format($item->final_amount, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>
        @endforeach
    </div>

    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td class="total-label">Subtotal</td>
                <td class="total-amount">{{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
            </tr>
            @if($invoice->discount > 0)
            <tr>
                <td class="total-label">Diskon</td>
                <td class="total-amount">-{{ number_format($invoice->discount, 0, ',', '.') }}</td>
            </tr>
            @endif
            @if($invoice->tax > 0)
            <tr>
                <td class="total-label">Pajak</td>
                <td class="total-amount">{{ number_format($invoice->tax, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr>
                <td class="total-label total-row"><strong>Total</strong></td>
                <td class="total-amount bold total-row">{{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="total-label payment-separator">{{ $invoice->payment_method === 'cash' ? 'Tunai' : ($invoice->payment_method === 'non_cash' ? 'Non Tunai' : 'Campuran') }}</td>
                <td class="total-amount payment-separator">{{ number_format($invoice->amount_paid, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="total-label">Kembali</td>
                <td class="total-amount">{{ number_format($invoice->change_amount, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="message-section">
        <div>Senyum Anda adalah Semangat</div>
        <div>kami dalam Melayani</div>
        <div class="thank-you">TERIMA KASIH</div>
    </div>

    <div class="footer">
        *) Barang yang sudah dibeli/tidak<br>
        dapat dikembalikan
    </div>
</body>
</html>