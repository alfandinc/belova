<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 8px;
            size: a5 landscape;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.2;
            margin: 0;
            padding: 0;
            color: #333;
            position: relative;
        }
        .container {
            position: relative;
            padding-bottom: 90px; /* Increased space for footer elements */
        }
        .header-table {
            width: 100%;
            border-bottom: 1px solid #3498db;
            margin-bottom: 15px;
        }
        .header-table td {
            vertical-align: middle;
            padding: 0;
        }
        .logo-cell {
            width: 50%;
            text-align: left;
        }
        .title-cell {
            width: 50%;
            text-align: right;
        }
        .invoice-title {
            font-size: 28pt;
            font-weight: bold;
            color: #3498db;
            margin: 0;
            text-align: right;
        }
        .invoice-number-row {
            text-align: right;
        }
        .invoice-number {
            font-size: 9pt;
            color: #555;
            margin-right: 6px;
            display: inline-block;
        }
        .status-badge {
            display: inline-block;
            padding: 1px 5px;
            background-color: #f1f9ff;
            border: 1px solid #3498db;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }
        .status-badge.paid {
            color: #27ae60 !important;
            border-color: #27ae60 !important;
            background-color: #eafaf1 !important;
        }

        .info-section {
            width: 100%;
            display: table;
            margin-bottom: 8px;
            border-collapse: separate;
            border-spacing: 6px 0;
        }
        .info-box {
            display: table-cell;
            width: 48%;
            vertical-align: top;
            background-color: #f8f9fa;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .info-box-header {
            background-color: #3498db;
            color: white;
            padding: 2px 5px;
            font-weight: bold;
            font-size: 8pt;
            border-radius: 3px 3px 0 0;
        }
        .info-box-content {
            padding: 4px 5px;
            font-size: 8pt;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 1px 0;
            vertical-align: top;
            font-size: 8pt;
        }
        .label-column {
            width: 35%;
            color: #555;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        .items-table th {
            background-color: #3498db;
            color: white;
            padding: 3px;
            text-align: left;
            font-size: 8pt;
            border: 1px solid #2980b9;
        }
        .items-table td {
            padding: 3px;
            border: 1px solid #ddd;
            font-size: 8pt;
            vertical-align: top;
        }
        .items-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .finance-summary {
            position: absolute;
            bottom: 70px;
            right: 0;
            width: 36%;
        }
        .summary-box {
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f9f9f9;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table td {
            padding: 2px 4px;
            font-size: 8pt;
            border-bottom: 1px solid #eee;
        }
        .summary-table tr:last-child td {
            border-bottom: none;
        }
        .summary-table .total-row td {
            font-weight: bold;
            background-color: #f1f9ff;
            border-top: 1px solid #3498db;
            border-bottom: none;
        }
        .terbilang {
            font-style: italic;
            font-size: 7.5pt;
            color: #555;
            text-align: right;
            padding: 2px 4px;
            background-color: #f1f9ff;
            border-top: 1px dashed #bbb;
        }
        .payment-info {
            margin-top: 6px;
            padding: 6px 6px 2px 6px;
            font-size: 8pt;
            background-color: transparent;
            border-top: 1px solid #ddd;
            border-radius: 0;
        }
        .payment-info-title {
            font-weight: 600;
            color: #555;
            margin-bottom: 2px;
            text-transform: none;
            letter-spacing: 0;
            text-align: left;
        }
        .payment-line {
            text-align: left;
            font-weight: normal;
            color: #333;
        }
        .payment-box {
            margin-top: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #fff;
        }
        .payment-box .payment-info {
            margin-top: 0;
            padding: 6px 8px;
            border-top: none;
        }
        .footer-container {
            position: absolute;
            bottom: 20px;        /* Adjusted bottom position */
            left: 0;
            right: 0;
        }
        .signature-row {
            position: relative;  /* Changed from absolute to relative */
            margin-top: 15px;    /* Add margin for spacing */
            width: 100%;
            display: flex;
            justify-content: center;
            padding-top: 3px;
            border-top: none;
        }
        .signature-left {
            font-size: 7pt;
            color: #777;
            flex-grow: 0;
            padding-top: 0;
            width: 0;
        }
        .signature-right {
            text-align: center;
            font-size: 8pt;
            width: 240px;
        }
        .signature-date { white-space: nowrap; }
        .signature-line {
            display: inline-block;
            border-top: 1px solid #333;
            width: 120px;
            margin-top: 80px;
            text-align: center;
        }
        .signature-line p {
            margin: 3px 0 0;
        }
        p {
            margin: 0 0 2px 0;
        }
    </style>
</head>
<body>
    <!-- Helper function to convert numbers to Indonesian words -->
    @php
    function terbilang($number) {
        $angka = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas"];
        
        if ($number < 12) {
            return $angka[$number];
        } elseif ($number < 20) {
            return terbilang($number - 10) . " belas";
        } elseif ($number < 100) {
            return terbilang((int)($number / 10)) . " puluh " . terbilang($number % 10);
        } elseif ($number < 200) {
            return "seratus " . terbilang($number - 100);
        } elseif ($number < 1000) {
            return terbilang((int)($number / 100)) . " ratus " . terbilang($number % 100);
        } elseif ($number < 2000) {
            return "seribu " . terbilang($number - 1000);
        } elseif ($number < 1000000) {
            return terbilang((int)($number / 1000)) . " ribu " . terbilang($number % 1000);
        } elseif ($number < 1000000000) {
            return terbilang((int)($number / 1000000)) . " juta " . terbilang($number % 1000000);
        } elseif ($number < 1000000000000) {
            return terbilang((int)($number / 1000000000)) . " milyar " . terbilang($number % 1000000000);
        }
        
        return "";
    }
    @endphp

    <div class="container">
        <!-- Header with Logo and Invoice Title -->
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @php
                        $logoPath = '';
                        if ($invoice->visitation->klinik_id == 1) {
                            $logoPath = public_path('img/header-premiere.png');
                            $altText = 'Premiere Logo';
                        } elseif ($invoice->visitation->klinik_id == 2) {
                            $logoPath = public_path('img/header-belova.png');
                            $altText = 'Belova Logo';
                        } else {
                            $logoPath = public_path('img/logo.png');
                            $altText = 'Default Logo';
                        }
                        
                        $logoBase64 = '';
                        if (file_exists($logoPath)) {
                            $logoData = file_get_contents($logoPath);
                            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
                        }
                    @endphp
                    
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="{{ $altText }}" height="80" style="max-width: 240px;">
                    @else
                        <div style="font-weight: bold; color: #3498db; font-size: 18px;">{{ $altText }}</div>
                    @endif
                </td>
                <td class="title-cell">
                    <div class="invoice-title">INVOICE</div>
                    <div class="invoice-number-row">
                        <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                        <div class="status-badge{{ strtolower($invoice->status) == 'paid' ? ' paid' : '' }}">{{ strtoupper($invoice->status) }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Patient Information and Invoice Details side by side -->
        <div class="info-section">
            <div class="info-box">
                <div class="info-box-header">Patient Information</div>
                <div class="info-box-content">
                    <p><strong>{{ $invoice->visitation->pasien->nama ?? 'N/A' }}</strong></p>
                    <p>Medical Record: {{ $invoice->visitation->pasien->id ?? 'N/A' }}</p>
                    <p>{{ $invoice->visitation->pasien->alamat ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="info-box">
                <div class="info-box-header">Invoice Details</div>
                <div class="info-box-content">
                    <!-- Summary Section - Fixed at bottom right -->
                    <table class="info-table">
                        <tr>
                            <td class="label-column">Invoice Date</td>
                            <td>: {{ \Carbon\Carbon::parse($invoice->created_at)->format('d F Y') }}</td>
                        </tr>
                        <tr>
                            <td>Visit Date</td>
                            <td>: {{ \Carbon\Carbon::parse($invoice->visitation->tanggal_visitation)->format('d F Y') }}</td>
                        </tr>
                        <tr>
                            <td>Clinic</td>
                            <td>: {{ $invoice->visitation->klinik->nama ?? 'N/A' }}</td>
                        </tr>
                        @if($invoice->payment_date)
                        <tr>
                            <td>Payment Date</td>
                            <td>: {{ \Carbon\Carbon::parse($invoice->payment_date)->format('d F Y') }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 4%;">No</th>
                    <th style="width: 18%;">Item</th>
                    <th style="width: 38%;">Description</th>
                    <th style="width: 7%;">Qty</th>
                    <th style="width: 16%;" class="text-right">Price</th>
                    <th style="width: 16%;" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $item->name }}</strong></td>
                    <td>{{ $item->description ?? '-' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td class="text-right">
                        Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                        @if($item->discount > 0)
                            <br><span style="color:#e74c3c; font-size:7pt">
                                Disc: {{ $item->discount_type == '%' ? $item->discount.'%' : 'Rp '.number_format($item->discount, 0, ',', '.') }}
                            </span>
                        @endif
                    </td>
                    <td class="text-right">Rp {{ number_format($item->final_amount, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>       
        <!-- Summary Section - Fixed at bottom right -->
        <div class="finance-summary">
            @php
                $klinik = $invoice->visitation->klinik ?? null;
                $paymentBank = $klinik->bank_name ?? 'BNI';
                $paymentAccName = $klinik->bank_account_name ?? ($klinik->rekening_nama ?? 'CV BELIA ABADI');
                $paymentAccNo = $klinik->bank_account_number ?? ($klinik->rekening_nomor ?? ($klinik->no_rekening ?? '3113131515'));
            @endphp
            <div class="summary-box">
                <table class="summary-table">
                    <tr>
                        <td class="label-column">Subtotal</td>
                        <td class="text-right">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @if($invoice->discount_value > 0)
                    <tr>
                        <td class="label-column">
                            Discount {{ $invoice->discount_type == '%' ? '('.$invoice->discount_value.'%)' : '' }}
                        </td>
                        <td class="text-right">Rp {{ number_format($invoice->discount, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($invoice->tax > 0)
                    <tr>
                        <td class="label-column">Tax ({{ $invoice->tax_percentage }}%)</td>
                        <td class="text-right">Rp {{ number_format($invoice->tax, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td class="label-column">Total Amount</td>
                        <td class="text-right">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                    </tr>
                </table>
                <!-- Terbilang (Amount in Words) - directly below total inside summary box -->
                <div class="terbilang">
                    <em>{{ ucfirst(terbilang((int)$invoice->total_amount)) }} rupiah</em>
                </div>
            </div>
            <!-- Payment Box - placed after terbilang as requested -->
            <div class="payment-box">
                <div class="payment-info">
                    <div class="payment-info-title">Payment Information</div>
                    <div class="payment-line">
                        <span class="name" style="font-weight: 600;">{{ $paymentAccName }}</span>
                        <span class="acc">: {{ $paymentAccNo }}</span>
                        <span class="bank"> ({{ $paymentBank }})</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Fixed Footer Elements -->
        <div class="footer-container">
            <div class="signature-row">
                <div class="signature-left"></div>
                <div class="signature-right">
                    <p class="signature-date">Surakarta, {{ \Carbon\Carbon::now()->format('d F Y') }}</p>
                    <div class="signature-line">
                        <p>Finance Officer</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>