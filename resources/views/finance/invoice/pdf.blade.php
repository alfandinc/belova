<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page { 
            margin: 12px; /* unify with container/footer offsets for symmetric margins */
            size: a5 portrait;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.25;
            margin: 0;
            padding: 0;
            color: #333;
            position: relative;
        }
        .container {
            position: relative;
            /* symmetric horizontal gutter matching @page margin */
            padding: 0 12px 110px 12px; /* top right bottom left */
            box-sizing: border-box;
        }
        .header-table {
            width: 100%;
            border-bottom: 1px solid #3498db;
            margin-bottom: 8px; /* reduced space below header */
        }
        .header-table td {
            vertical-align: middle;
            padding: 4px 0; /* reduced vertical padding for tighter header */
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
        .invoice-number-value {
            font-weight: 700;
            color: #333;
        }
        .status-badge {
            display: inline-block;
            padding: 1px 5px;
            background-color: #f1f9ff;
            border: 1px solid #3498db;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
            vertical-align: middle;
            margin-left: 8px; /* small gap from invoice number */
        }
        .status-badge.paid {
            color: #27ae60 !important;
            border-color: #27ae60 !important;
            background-color: #eafaf1 !important;
        }

        .info-section {
            width: 100%;
            display: table;
            margin-top: 4px; /* small gap below header */
            margin-bottom: 20px; /* gap between info and items */
            border-collapse: collapse; /* ensure no extra border spacing */
        }
        .info-box {
            display: table-cell;
            width: 48%;
            vertical-align: top;
            background-color: transparent; /* remove filled box */
            border-radius: 0; /* remove rounded corners */
            box-shadow: none; /* remove shadow */
            overflow: visible;
        }
        .info-box-header {
            color: #3498db; /* colored text instead of filled header */
            background: transparent;
            padding: 6px 8px; /* keep spacing but no background */
            font-weight: 700;
            font-size: 10pt; /* slightly larger header */
            border-radius: 0;
            margin-bottom: 6px; /* small gap before content */
        }
        /* Unified content style for patient, invoice details, and payment info */
        .info-box-content,
        .payment-info,
        .info-table td {
            /* use the same left inset as items table for perfect alignment */
            padding: 10px 0 10px 12px; /* top right bottom left */
            font-size: 9pt; /* unified text size */
            line-height: 1.25; /* consistent line height */
            color: #333; /* unified color */
        }
        .patient-name {
            font-size: 9pt; /* same as other content */
            font-weight: 400; /* make name/id normal weight */
            margin-bottom: 6px;
            color: #333; /* same color */
        }
        .bill-to {
            font-size: 9pt;
            font-weight: 700;
            color: #333;
            margin-bottom: 4px;
        }
        .patient-address {
            font-size: 9pt;
            color: #333;
            margin-top: 4px;
        }
        .patient-phone {
            font-size: 9pt;
            color: #333;
            margin-top: 4px;
            margin-bottom: 6px;
            font-weight: 400;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 4px 0; /* more vertical spacing between rows */
            vertical-align: top;
            font-size: 9pt;
            color: #333;
        }
        /* Info table: structured 3-column layout for label / colon / value */
        .info-table td.label {
            font-weight: 600;
            color: #333;
            width: 32%;
            padding-right: 6px;
            text-align: left;
        }
        .info-table td.colon {
            width: 4%;
            text-align: center;
            color: #333;
            font-weight: 600;
            padding: 0 4px;
        }
        .info-table td.value {
            width: 64%;
            text-align: right; /* align values to the right */
            color: #333;
            white-space: nowrap; /* keep value on single line */
        }
        .label-column {
            width: 42%;
            color: #555;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 80px; /* further increased space after items before footer/line */
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            box-sizing: border-box;
            word-wrap: break-word;
        }
        .items-table th {
            background-color: transparent;
            color: #333;
            padding: 8px 0 8px 12px; /* align left inset with info content */
            text-align: left;
            font-size: 10pt;
            border-bottom: 1px solid #ccc;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        /* Ensure header alignment follows column classes (override table-level rule) */
        .items-table th.text-right,
        .items-table td.text-right {
            text-align: right;
        }
        .items-table th.text-center,
        .items-table td.text-center {
            text-align: center;
        }
        /* Explicitly align first column (item) to the left */
        .items-table th:first-child,
        .items-table td:first-child {
            text-align: left;
        }
        .items-table td {
            padding: 10px 0 10px 12px; /* align left inset with info content */
            border: none;
            font-size: 9pt;
            vertical-align: top;
            box-sizing: border-box;
        }
        .item-desc {
            font-weight: 600;
            margin-bottom: 3px;
            display: block;
            width: 100%;
        }
        .item-note {
            font-size: 8pt;
            color: #666;
            margin-top: 2px;
        }
        .items-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .finance-summary {
            position: static;
            bottom: auto;
            right: auto;
            width: 100%;
            margin-top: 18px;
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
        .summary-box {
            padding: 0;
            border: none;
            background-color: transparent;
            border-radius: 0;
        }
        .summary-grid {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        .summary-grid thead th {
            font-size: 11pt;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 6px 8px;
            color: #333;
            border-bottom: 1px solid #333;
        }
        .summary-grid tbody td {
            padding: 12px 8px 18px 8px;
            font-size: 10pt;
            color: #333;
        }
        /* Bottom footer row: use table for dompdf compatibility (payment left + summary right) */
        .footer-bottom {
            position: absolute;
            bottom: 18px;
            left: 12px; /* match container horizontal padding */
            right: 12px; /* match container horizontal padding */
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-table td {
            vertical-align: top;
            padding: 0 6px;
        }
        .footer-table .footer-payment {
            width: 52%;
            padding-left: 0;
        }
        .footer-table .footer-summary {
            width: 48%;
            padding-right: 0;
            text-align: right;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            background: transparent;
        }
        .summary-table td {
            padding: 6px 4px;
            font-size: 9pt; /* unified footer font size */
            line-height: 1.2;
            vertical-align: middle;
            color: #333;
        }
        .summary-table td.label {
            width: 60%;
            text-align: left;
            white-space: nowrap;
            font-weight: 600;
            padding-right: 8px;
            font-size: 9pt;
            text-transform: uppercase;
        }
        .summary-table td.value {
            width: 40%;
            text-align: right;
            font-weight: 600;
            padding-left: 8px;
            font-size: 9pt;
        }
        .summary-total {
            font-size: 9pt; /* keep same size but bold for emphasis */
            font-weight: 800;
        }
        .summary-terbilang {
            text-align: right;
            font-style: italic;
            font-size: 9pt;
            color: #333;
            background-color: #f1f9ff;
            padding: 6px 8px;
            margin-top: 6px;
            border-radius: 3px;
            display: block;
            white-space: normal; /* allow terbilang to wrap if needed */
        }
        .summary-after-items {
            width: 100%;
            text-align: left; /* labels at left of page */
            margin-top: 30px; /* gap from items table */
            border-top: 1px solid #3498db; /* blue separator like header */
            padding-top: 28px; /* further increased space between line and summary content */
        }
        .clinic-info {
            font-size: 9pt;
            color: #333;
            display: inline-block; /* allow block to sit at right while internal text is left-aligned */
            text-align: left; /* keep clinic text left-aligned inside the block */
            max-width: 360px; /* increased width so text wraps more naturally */
            line-height: 1.1;
        }
        .clinic-info .clinic-name {
            font-weight: 600;
            font-size: 9pt;
        }
    .clinic-info .clinic-address,
    .clinic-info .clinic-phone,
    .clinic-info .clinic-website {
            font-size: 8pt;
            color: #666;
            margin-top: 4px;
        }
        .summary-amount {
            text-align: left;
            color: #333;
            font-weight: 600;
        }
        .summary-amount-right {
            text-align: right;
        }
        .summary-totals {
            text-align: right;
            font-weight: 800;
            font-size: 11pt;
            color: #000;
        }
        .terbilang {
            font-style: italic;
            font-size: 8.5pt;
            color: #555;
            text-align: right;
            padding: 3px 6px;
            background-color: #f1f9ff;
            border-top: 1px dashed #bbb;
        }
        .payment-info {
            margin-top: 6px;
            padding: 6px 6px 2px 6px;
            font-size: 9pt;
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
            border: none;
            border-radius: 0;
            background-color: transparent;
            padding: 0;
        }
        .payment-info {
            border-top: 1px solid #ddd;
            padding-top: 8px;
        }
        .payment-info .pay-name {
            font-weight: 600;
            margin-bottom: 4px;
            font-size: 9pt; /* unified footer font */
            color: #333; /* same color */
        }
        .payment-info .pay-bank,
        .payment-info .pay-acc {
            font-size: 9pt; /* unified footer font */
            color: #333;
            margin-bottom: 2px;
        }
        .payment-box .payment-info {
            margin-top: 0;
            padding: 6px 8px;
            border-top: none;
        }
        .footer-container {
            position: absolute;
            bottom: 20px;        /* Adjusted bottom position */
            left: 12px;          /* match container padding */
            right: 12px;         /* match container padding */
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
            font-size: 8pt;
            color: #777;
            flex-grow: 0;
            padding-top: 0;
            width: 0;
        }
        .signature-right {
            text-align: center;
            font-size: 9pt;
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
        .payment-left {
            position: static;
            width: 100%;
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
                    <div class="invoice-number-row"></div>
                </td>
            </tr>
        </table>

        <!-- Patient Information and Invoice Details side by side -->
        <div class="info-section">
            <div class="info-box">
                <div class="info-box-header"></div>
                <div class="info-box-content">
                    @php
                        $pasien = $invoice->visitation->pasien ?? null;
                        $alamatRaw = trim($pasien->alamat ?? '');
                        // Normalize commas/spaces and trim leading/trailing commas
                        $alamatClean = $alamatRaw !== '' ? preg_replace('/\s*,\s*/', ', ', trim($alamatRaw, " ,")) : '';
                        $village = optional($pasien->village)->name;
                        $district = optional(optional($pasien->village)->district)->name;
                        $regency = optional(optional(optional($pasien->village)->district)->regency)->name;

                        // Build address parts, avoid duplicates if already present in the raw address
                        $parts = [];
                        if ($alamatClean !== '') {
                            $parts[] = $alamatClean;
                        }
                        foreach ([$village, $district, $regency] as $part) {
                            if ($part && ($alamatClean === '' || stripos($alamatClean, $part) === false)) {
                                $parts[] = $part;
                            }
                        }

                        $fullAddress = $parts ? implode(', ', $parts) : 'N/A';
                        $phone = $pasien->no_hp ?? $pasien->no_hp2 ?? 'N/A';
                    @endphp
                    <p class="bill-to">BILL TO :</p>
                    <p class="patient-name">{{ $pasien->nama ?? 'N/A' }} ({{ $pasien->id ?? 'N/A' }})</p>
                    <p class="patient-phone">{{ $phone }}</p>
                    <p class="patient-address">{{ $fullAddress }}</p>
                </div>
            </div>
            <div class="info-box">
                <div class="info-box-header"></div>
                <div class="info-box-content">
                    <!-- Summary Section - Fixed at bottom right -->
                    <table class="info-table">
                        <tr>
                            <td class="label">&nbsp;</td>
                            <td class="colon">&nbsp;</td>
                            <td class="value">
                                <table style="width:100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="text-align:left; padding:0; vertical-align:middle;"><span class="invoice-number-value">{{ $invoice->invoice_number }}</span></td>
                                        <td style="text-align:right; padding:0; vertical-align:middle;"><span class="status-badge{{ strtolower($invoice->status) == 'paid' ? ' paid' : '' }}">{{ strtoupper($invoice->status) }}</span></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td class="label">&nbsp;</td>
                            <td class="colon">&nbsp;</td>
                            <td class="value">{{ \Carbon\Carbon::parse($invoice->visitation->tanggal_visitation)->format('d F Y') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:50%;">Item</th>
                    <th style="width:10%;" class="text-center">Qty</th>
                    <th style="width:20%;" class="text-right">Price</th>
                    <th style="width:20%;" class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $index => $item)
                <tr>
                    <td>
                        <div class="item-desc">{{ $item->name }}</div>
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->final_amount, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>       
        @php
            $klinik = $invoice->visitation->klinik ?? null;
            $clinicId = $invoice->visitation->klinik_id ?? ($klinik->id ?? null);

            if ($clinicId == 1) {
                // Payment info for Premiere Belova
                $paymentBank = 'BNI';
                $paymentAccNo = '4713131515';
                $paymentAccName = 'CV BELOVA INDONESIA';

                // Clinic display info for klinik id 1
                $clinicDisplayName = 'PREMIERE BELOVA';
                $clinicAddress = 'Jl. Melon Raya no 27, Karangasem, Kec. Laweyan, Kota Surakarta, Jwa Tengah, 57145';
                $clinicPhone = '0821 1600 0093';
                $clinicWebsite = 'www.premierebelova.id';
            } elseif ($clinicId == 2) {
                // Payment info fallback for klinik id 2 (use clinic values if present)
                $paymentBank = $klinik->bank_name ?? 'BNI';
                $paymentAccName = $klinik->bank_account_name ?? ($klinik->rekening_nama ?? 'CV BELIA ABADI');
                $paymentAccNo = $klinik->bank_account_number ?? ($klinik->rekening_nomer ?? ($klinik->no_rekening ?? '3113131515'));

                // Clinic display info for klinik id 2 (Belova Skin)
                $clinicDisplayName = 'BELOVA SKIN';
                $clinicAddress = 'Jl. Melon Raya no 29, Karangasem, Kec. Laweyan, Kota Surakarta, Jwa Tengah, 57145';
                $clinicPhone = '085 100 990 139 / 0812 2870 6886';
                $clinicWebsite = 'www.belovaskin.id';
            } else {
                $paymentBank = $klinik->bank_name ?? 'BNI';
                $paymentAccName = $klinik->bank_account_name ?? ($klinik->rekening_nama ?? 'CV BELIA ABADI');
                $paymentAccNo = $klinik->bank_account_number ?? ($klinik->rekening_nomer ?? ($klinik->no_rekening ?? '3113131515'));

                $clinicDisplayName = $klinik->nama ?? $klinik->name ?? 'Klinik';
                // prefer alamat or address fields if available
                $clinicAddress = $klinik->alamat ?? $klinik->address ?? '';
                $clinicPhone = $klinik->telp ?? $klinik->phone ?? '';
                $clinicIg = '';
                $clinicWebsite = $klinik->website ?? $klinik->web ?? '';
            }

            $taxPct = $invoice->tax_percentage ?? 0;
            $taxAmount = $invoice->tax ?? 0;
        @endphp

        <div class="summary-after-items">
            <table class="summary-table" style="width:100%;">
                <tr>
                    <td class="label" style="text-align:left;">SUBTOTAL</td>
                    <td class="value" style="text-align:right;">Rp&nbsp;{{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label" style="text-align:left;">TAX ({{ $taxPct }}%)</td>
                    <td class="value" style="text-align:right;">Rp&nbsp;{{ number_format($taxAmount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label summary-total" style="text-align:left;">TOTAL AMOUNT</td>
                    <td class="value summary-total" style="text-align:right;">Rp&nbsp;{{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align:right; padding-top:8px;">
                        <div class="summary-terbilang" style="display:inline-block;">
                            <em>{{ ucfirst(terbilang((int)$invoice->total_amount)) }} rupiah</em>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        <!-- Footer: payment info fixed at page bottom -->
        <div class="footer-bottom">
            <table class="footer-table">
                <tr>
                    <!-- Payment information on the left -->
                    <td class="footer-payment" style="width:52%; padding-left:0; vertical-align:bottom;">
                        <div class="payment-box">
                            <div class="payment-info">
                                <div class="payment-info-title">Payment Information</div>
                                <div class="payment-line">
                                    <div class="pay-name">{{ $paymentAccName }}</div>
                                    <div class="pay-bank">Bank: {{ $paymentBank }}</div>
                                    <div class="pay-acc">Account No: {{ $paymentAccNo }}</div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <!-- Clinic information on the right (name + address), aligned bottom-right -->
                    <td class="footer-clinic" style="width:48%; padding-right:0; text-align:right; vertical-align:bottom;">
                        <div class="clinic-info">
                            <div class="clinic-name">{{ $clinicDisplayName }}</div>
                                        @if(!empty($clinicAddress))
                                            <div class="clinic-address">{{ $clinicAddress }}</div>
                                        @endif
                                        @if(!empty($clinicPhone))
                                            <div class="clinic-phone">Tel: {{ $clinicPhone }}</div>
                                        @endif
                                        @if(!empty($clinicWebsite))
                                            <div class="clinic-website">{{ $clinicWebsite }}</div>
                                        @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>