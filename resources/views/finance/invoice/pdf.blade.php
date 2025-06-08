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
            margin-bottom: 8px;
        }
        .header-table td {
            vertical-align: middle;
            padding: 0;
        }
        .logo-cell {
            width: 40%;
            text-align: left;
        }
        .title-cell {
            width: 60%;
            text-align: right;
        }
        .invoice-title {
            font-size: 22pt;
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
            color: #3498db;
            font-weight: bold;
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
        .footer-container {
            position: absolute;
            bottom: 20px;        /* Adjusted bottom position */
            left: 0;
            right: 0;
        }
        .payment-info {
            position: relative;  /* Changed from absolute to relative */
            margin-top: 20px;    /* Add margin instead of absolute positioning */
            width: 60%;
            padding: 4px 6px;
            background-color: #f8f9fa;
            border-left: 2px solid #3498db;
            font-size: 8pt;
            border-radius: 3px;
            line-height: 1.3;
        }
        .signature-row {
            position: relative;  /* Changed from absolute to relative */
            margin-top: 15px;    /* Add margin for spacing */
            width: 100%;
            display: flex;
            justify-content: space-between;
            padding-top: 3px;
            border-top: 1px solid #ddd;
        }
        .signature-left {
            font-size: 7pt;
            color: #777;
            flex-grow: 1;
            padding-top: 5px;
        }
        .signature-right {
            text-align: right;
            font-size: 8pt;
            width: 180px;
        }
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
                    <img src="{{ public_path('img/logo.png') }}" alt="Belova Logo" height="35">
                </td>
                <td class="title-cell">
                    <div class="invoice-title">INVOICE</div>
                    <div class="invoice-number-row">
                        <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                        <div class="status-badge">{{ strtoupper($invoice->status) }}</div>
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
                <!-- Terbilang (Amount in Words) - directly below total -->
                <div class="terbilang">
                    <em>{{ ucfirst(terbilang((int)$invoice->total_amount)) }} rupiah</em>
                </div>
            </div>
        </div>
        
        <!-- Fixed Footer Elements -->
        <div class="footer-container">
            <div class="payment-info">
                <strong>Payment Information:</strong><br>
                Please make payment to: <strong>BCA</strong> | <strong>Account Name:</strong> Belova Clinic<br>
                <strong>Account Number:</strong> 123-456-7890
            </div>
            
            <div class="signature-row">
                <div class="signature-right">
                    <p>Surakarta, {{ \Carbon\Carbon::now()->format('d F Y') }}</p>
                    <div class="signature-line">
                        <p>Finance Officer</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>