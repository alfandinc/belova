@php $dokter = $slip->dokter; @endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Slip Gaji Dokter - {{ $dokter->user->name ?? '-' }} - {{ $slip->bulan }}</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #fff; color: #222; }
        .header-flex { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px }
        .logo { height:60px }
        .slip-title { font-size:1.4em; font-weight:bold; color:#444 }
        .bulan-label { font-size:1em; color:#444 }
        .print-columns { display:flex; gap:20px; width:100%; align-items:flex-start }
        .print-col { width:48%; min-width:260px }
        table { width:100%; border-collapse:collapse }
        .info-table td { padding:4px 6px; font-size:0.95em }
        .salary-table th, .salary-table td, .potongan-table th, .potongan-table td, .summary-table th, .summary-table td { border:1px solid #bbb; padding:6px; font-size:0.92em }
        .salary-table th, .potongan-table th, .summary-table th { background:#eaf6fa; color:#2a7ae2 }
        .section-title { font-size:1.05em; font-weight:bold; margin:12px 0 6px; color:#2a7ae2 }
        .right { text-align:right }
        .total-row { font-weight:bold; background:#eaf6ea; color:#1a7a1a }
        .terbilang { font-style:italic; margin-top:6px }
        .footer { margin-top:18px; font-size:0.95em; color:#666 }
    </style>
</head>
<body>
    <div style="width:100%; margin-bottom:10px;">
        <table style="width:100%; border:none; border-collapse:collapse;">
            <tr>
                <td style="vertical-align:middle;">
                    <img src="{{ public_path('img/header-belovacorp.png') }}" alt="Company Logo" class="logo">
                </td>
                <td style="text-align:right; vertical-align:middle;">
                    @php
                        $bulanTahun = '-';
                        if (!empty($slip->bulan)) {
                            try {
                                $bulanTahun = \Carbon\Carbon::createFromFormat('Y-m', $slip->bulan)->translatedFormat('F Y');
                            } catch (Exception $e) {
                                $bulanTahun = $slip->bulan;
                            }
                        }
                    @endphp
                    <div class="slip-title">Slip Gaji Dokter</div>
                    <div class="bulan-label">{{ $bulanTahun }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="info-table" style="margin-bottom:10px;">
        <tr>
            <td><strong>Nama Dokter</strong></td><td>: {{ $dokter->user->name ?? '-' }}</td>
            <td><strong>Bulan</strong></td><td>: {{ $bulanTahun }}</td>
        </tr>
        <tr>
            <td><strong>ID Dokter</strong></td><td>: {{ $dokter->id ?? '-' }}</td>
            <td><strong>Status</strong></td><td>: {{ ucfirst($slip->status_gaji ?? '-') }}</td>
        </tr>
    </table>

    <div class="print-columns">
        <div class="print-col">
            <div class="section-title">Pendapatan</div>
            <table class="salary-table">
                <tr><th>Komponen</th><th class="right">Nominal (Rp)</th></tr>
                <tr><td>Jasa Konsultasi</td><td class="right">{{ number_format($slip->jasa_konsultasi, 2) }}</td></tr>
                <tr><td>Jasa Tindakan</td><td class="right">{{ number_format($slip->jasa_tindakan, 2) }}</td></tr>
                <tr><td>Uang Duduk</td><td class="right">{{ number_format($slip->uang_duduk, 2) }}</td></tr>
                <tr><td>Bagi Hasil</td><td class="right">{{ number_format($slip->bagi_hasil, 2) }}</td></tr>
                <tr class="total-row"><td>Total Pendapatan</td><td class="right"><strong>{{ number_format($slip->total_pendapatan, 2) }}</strong></td></tr>
            </table>
        </div>
        <div class="print-col">
            <div class="section-title">Potongan</div>
            <table class="potongan-table">
                <tr><th>Komponen</th><th class="right">Nominal (Rp)</th></tr>
                <tr><td>Potongan Pajak</td><td class="right">{{ number_format($slip->pot_pajak, 2) }}</td></tr>
                <tr><td>Potongan Lain</td><td class="right">{{ number_format($slip->total_potongan - ($slip->pot_pajak ?? 0), 2) }}</td></tr>
                <tr class="total-row"><td>Total Potongan</td><td class="right"><strong>{{ number_format($slip->total_potongan, 2) }}</strong></td></tr>
            </table>

            <div class="section-title">Rekapitulasi</div>
            <table class="summary-table">
                <tr><th>Total Gaji (Rp)</th></tr>
                <tr><td class="right"><strong>{{ number_format($slip->total_gaji, 2) }}</strong></td></tr>
            </table>
            <div class="terbilang">Terbilang: {{ function_exists('terbilang') ? ucwords(terbilang($slip->total_gaji)) . ' rupiah' : '' }}</div>
        </div>
    </div>

    <div class="footer">
        Dicetak pada: {{ date('d-m-Y H:i') }}<br>
        Sistem Payroll Belova Corp
    </div>

</body>
</html>
