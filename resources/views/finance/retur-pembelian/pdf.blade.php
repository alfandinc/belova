<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Retur {{ $retur->retur_number ?? $retur->id }}</title>
    <style>
        html, body { width: 100%; margin: 5px; }
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
        .header { text-align: center; margin-bottom: 10px; padding-bottom: 6px; border-bottom: 1px solid #000; }
        .header img { max-width: 250px; height: auto; margin: 0 auto; }
        .company-name { font-weight: bold; font-size: 11pt; margin: 0 0 2px 0; letter-spacing: 0.5px; }
        .company-tagline { font-size: 8pt; margin: 0 0 3px 0; font-weight: normal; }
        .receipt-info { margin-bottom: 10px; font-size: 8pt; line-height: 1.8; border-bottom: 1px solid #000; padding-bottom: 6px; }
        .receipt-info table { width: 100%; border-collapse: collapse; }
        .receipt-info td { padding: 2px 0; vertical-align: top; }
        .receipt-info .label { width: 60px; font-weight: bold; padding-right: 4px; }
        .receipt-info .colon { width: 10px; text-align: left; padding-right: 4px; }
        .receipt-info .value { width: auto; }
        .items-section { margin-bottom: 10px; border-bottom: 1px solid #000; padding-bottom: 6px; }
        .item-line { margin: 3px 0; font-size: 8pt; }
        .item-name { font-weight: normal; margin-bottom: 1px; }
        .item-table { width: 100%; margin-left: 6px; }
        .item-table td { padding: 0; vertical-align: top; }
        .item-table .discount-inline { white-space: nowrap; display: inline-block; font-size: 7pt; font-weight: normal; }
        .item-table .qty-price { width: 60%; font-size: 8pt; }
        .item-table .amount { width: 40%; font-size: 8pt; text-align: right; font-weight: bold; }
        .totals-section { font-size: 8pt; margin-bottom: 10px; }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 1px 0; vertical-align: top; }
        .totals-table .total-label { width: 60%; font-weight: normal; }
        .totals-table .total-amount { width: 40%; font-weight: normal; text-align: right; }
        .totals-table .total-amount.bold { font-weight: bold; }
        .message-section { text-align: center; margin: 100px 0 8px 0; font-size: 7pt; line-height: 1.2; }
        .thank-you { font-weight: bold; margin-top: 3px; }
        .footer { text-align: center; font-size: 6pt; margin-top: 10px; padding-top: 6px; border-top: 1px solid #000; font-style: italic; }
    </style>
    @php use Carbon\Carbon; @endphp
    </head>
<body>
    <div class="header">
        @php
            $klinikId = $retur->invoice->visitation->klinik_id ?? 2;
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
                <td class="value">{{ $retur->processed_date ? Carbon::parse($retur->processed_date)->format('d M Y H:i') : '-' }}</td>
            </tr>
            <tr>
                <td class="label">No RM</td>
                <td class="colon"> : </td>
                <td class="value">{{ $retur->invoice->visitation->pasien->id ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Nama</td>
                <td class="colon"> : </td>
                <td class="value">{{ strtoupper($retur->invoice->visitation->pasien->nama ?? '-') }}</td>
            </tr>
            <tr>
                <td class="label">Dokter</td>
                <td class="colon"> : </td>
                <td class="value">{{ $retur->invoice->visitation->dokter->user->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Kasir</td>
                <td class="colon"> : </td>
                <td class="value">{{ $retur->user->name ?? 'administrator' }}</td>
            </tr>
            <tr>
                <td class="label">Retur</td>
                <td class="colon"> : </td>
                <td class="value">{{ $retur->retur_number ?? $retur->id }}</td>
            </tr>
        </table>
    </div>

    <div class="items-section">
        @foreach($retur->items as $item)
        @php
            $qty = $item->quantity_returned ?? 1;
            $unitOriginal = $item->original_unit_price ?? 0;
            $lineNoDisc = $unitOriginal * $qty;
            $unitReduced = $item->unit_price ?? $unitOriginal;
            $lineFinal = $item->total_amount ?? ($unitReduced * $qty);
            $lineDisc = max(0, $lineNoDisc - $lineFinal);
            $displayPercent = null;
            if (isset($item->percentage_cut) && is_numeric($item->percentage_cut)) {
                $displayPercent = floatval($item->percentage_cut);
            } elseif ($lineNoDisc > 0 && $lineDisc > 0) {
                $displayPercent = round(($lineDisc / $lineNoDisc) * 100, 2);
            }
        @endphp
        <div class="item-line">
            <div class="item-name">{{ $item->name }}</div>
            <table class="item-table">
                <tr>
                    <td class="qty-price">{{ $qty }} x {{ number_format($unitOriginal, 0, ',', '.') }}</td>
                    <td class="amount">{{ number_format($lineFinal, 0, ',', '.') }}</td>
                </tr>
                @if($lineDisc > 0)
                <tr>
                    <td class="qty-price" style="font-size:7pt;font-weight:normal;padding-top:2px;">
                        @if($displayPercent !== null)
                            <span class="discount-inline">Potongan: -{{ rtrim(rtrim(number_format($displayPercent, 2, ',', '.'), '0'), ',') }}% @if($lineDisc > 0) (-{{ number_format($lineDisc, 0, ',', '.') }}) @endif</span>
                        @else
                            <span class="discount-inline">Potongan: -{{ number_format($lineDisc, 0, ',', '.') }}</span>
                        @endif
                    </td>
                    <td class="amount" style="font-size:7pt;font-weight:normal;padding-top:2px;">&nbsp;</td>
                </tr>
                @endif
            </table>
        </div>
        @endforeach
    </div>

    @php
        $subtotalItems = 0; // sum of original (no discount) line totals
        $potonganTotal = 0; // sum of discounts
        foreach ($retur->items as $it) {
            $qty = $it->quantity_returned ?? 1;
            $unitOriginal = $it->original_unit_price ?? 0;
            $unitReduced = $it->unit_price ?? $unitOriginal;
            $lineNoDisc = $unitOriginal * $qty;
            $lineFinal = isset($it->total_amount) ? $it->total_amount : ($unitReduced * $qty);
            $lineDisc = max(0, $lineNoDisc - $lineFinal);
            $subtotalItems += $lineNoDisc;
            $potonganTotal += $lineDisc;
        }
    @endphp

    <div class="totals-section" style="margin-bottom:6px;">
        <table class="totals-table">
            <tr>
                <td class="total-label">Subtotal</td>
                <td class="total-amount">{{ number_format($subtotalItems, 0, ',', '.') }}</td>
            </tr>
            @if($potonganTotal > 0)
            <tr>
                <td class="total-label">Potongan Total</td>
                <td class="total-amount">-{{ number_format($potonganTotal, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr>
                <td class="total-label"><strong>Total Retur</strong></td>
                <td class="total-amount bold">{{ number_format($retur->total_amount, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div style="border-top:1px solid #000; margin:6px 0;"></div>

    <div class="message-section">
        <div>Senyum Anda adalah Semangat</div>
        <div>kami dalam Melayani</div>
        <div class="thank-you">TERIMA KASIH</div>
    </div>

    
</body>
</html>