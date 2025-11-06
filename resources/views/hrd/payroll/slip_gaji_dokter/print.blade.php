@php $dokter = $slip->dokter; @endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Slip Gaji Dokter - {{ $dokter->user->name ?? '-' }}</title>
    <style>
        .print-columns {
            position: relative;
        }
        .print-col-left {
            width: 48%;
            min-width: 320px;
            vertical-align: top;
            display: inline-block;
        }
        .print-col-right {
            position: absolute;
            top: 0;
            right: 0;
            width: 48%;
            min-width: 320px;
            vertical-align: top;
            display: inline-block;
        }
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #fff; color: #222; position: relative; min-height: 297mm; }
        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .logo {
            height: 60px;
            margin: 0;
            display: block;
        }
        .slip-title { font-size: 1.5em; font-weight: bold; color: #444; }
        .bulan-label { font-size: 1em; color: #444; margin-top: 2px; }
        .info-table, .salary-table, .potongan-table, .summary-table {
            width: 100%; max-width: 420px; border-collapse: collapse; margin-bottom: 18px; table-layout: fixed;
        }
        .print-columns {
            display: flex;
            flex-direction: row;
            gap: 24px;
            width: 100%;
            justify-content: space-between;
            align-items: flex-start;
        }
        .print-col {
            width: 48%;
            min-width: 320px;
            vertical-align: top;
            display: inline-block;
        }
        .info-table td { padding: 3px 6px; font-size: 0.95em; }
        .salary-table th, .salary-table td,
        .potongan-table th, .potongan-table td,
        .summary-table th, .summary-table td {
            border: 1px solid #bbb; padding: 4px 6px; font-size: 0.92em; height: 22px;
        }
        .salary-table th, .potongan-table th, .summary-table th { background: #eaf6fa; color: #2a7ae2; }
        .section-title { font-size: 1.1em; font-weight: bold; margin: 18px 0 8px; color: #2a7ae2; }
        .right { text-align: right; }
        .total-row { font-weight: bold; background: #eaf6ea; color: #1a7a1a; }
    /* Fixed footer at bottom-right for print and PDF */
    .footer { position: absolute; right: 30px; bottom: 20px; font-size: 0.95em; text-align: right; color: #666; }
        .signature-section { margin-top: 40px; display: flex; justify-content: space-between; }
        .signature-box { width: 40%; text-align: center; }
        .signature-label { margin-bottom: 60px; }
        .terbilang { font-size: 0.95em; font-style: italic; color: #444; margin-top: 4px; }
    </style>
</head>
<body>
    <div style="width: 100%; margin-bottom: 10px;">
        <table style="width: 100%; border: none; border-collapse: collapse;">
            <tr>
                <td style="width: 1px; vertical-align: middle;">
                    <img src="{{ public_path('img/header-belovacorp.png') }}" alt="Company Logo" style="height: 60px; display: block;">
                </td>
                <td style="text-align: right; vertical-align: middle;">
                    @php
                        // Assume $slip->bulan is in 'Y-m' format (e.g., '2025-01')
                        $bulanTahun = '-';
                        if (!empty($slip->bulan)) {
                            $bulanTahun = \Carbon\Carbon::createFromFormat('Y-m', $slip->bulan)->translatedFormat('F Y');
                        }
                    @endphp
                    <span style="font-size: 1.5em; font-weight: bold; color: #444;">Slip Gaji Dokter</span>
                    <div class="bulan-label">{{ $bulanTahun }}</div>
                </td>
            </tr>
        </table>
    </div>
    <table class="info-table">
        <tr>
            <td><strong>Nama Dokter</strong></td><td>: {{ $dokter->user->name ?? '-' }}</td>
            <td><strong>Status</strong></td><td>: {{ ucfirst($slip->status_gaji ?? '-') }}</td>
        </tr>
        <tr>
            <td><strong>Bulan</strong></td><td>: {{ $bulanTahun }}</td>
            <td></td><td></td>
        </tr>
    </table>
    <table style="width:100%; border:none; border-collapse:collapse;">
      <tr>
        <td style="width:50%; vertical-align:top; padding-right:12px;">
            <div class="section-title">Pendapatan</div>
            <table class="salary-table">
                <tr><th>Komponen</th><th class="right">Nominal (Rp)</th></tr>
                    <tr><td>Jasa Konsultasi</td><td class="right">{{ number_format($slip->jasa_konsultasi ?? 0, 2) }}</td></tr>
                    <tr><td>Jasa Tindakan</td><td class="right">{{ number_format($slip->jasa_tindakan ?? 0, 2) }}</td></tr>
                    <tr><td>Uang Duduk</td><td class="right">{{ number_format($slip->uang_duduk ?? 0, 2) }}</td></tr>
                    <tr><td>Tunjangan Jabatan</td><td class="right">{{ number_format($slip->tunjangan_jabatan ?? 0, 2) }}</td></tr>
                    <tr><td>Overtime</td><td class="right">{{ number_format($slip->overtime ?? 0, 2) }}</td></tr>
                    <tr><td>Peresepan Obat</td><td class="right">{{ number_format($slip->peresepan_obat ?? 0, 2) }}</td></tr>
                    <tr><td>Rujuk Lab</td><td class="right">{{ number_format($slip->rujuk_lab ?? 0, 2) }}</td></tr>
                    <tr><td>Pembuatan Konten</td><td class="right">{{ number_format($slip->pembuatan_konten ?? 0, 2) }}</td></tr>
                @if(isset($slip->pendapatan_tambahan) && is_array($slip->pendapatan_tambahan) && count($slip->pendapatan_tambahan))
                    @foreach($slip->pendapatan_tambahan as $item)
                        <tr><td>{{ $item['label'] ?? '-' }}</td><td class="right">{{ number_format($item['amount'] ?? 0, 2) }}</td></tr>
                    @endforeach
                @else
                    <tr><td><em>Tidak ada pendapatan tambahan</em></td><td class="right">{{ number_format(0, 2) }}</td></tr>
                @endif
                <!-- Bagi Hasil moved to Potongan column (deduction) -->
                <tr class="total-row"><td>Total Pendapatan</td><td class="right"><strong>{{ number_format($slip->total_pendapatan, 2) }}</strong></td></tr>
            </table>
        </td>
        <td style="width:50%; vertical-align:top; padding-left:12px;">
            <div class="section-title">Potongan</div>
            <table class="potongan-table">
                <tr><th>Komponen</th><th class="right">Nominal (Rp)</th></tr>
                    <tr><td>Potongan Pajak</td><td class="right">{{ number_format($slip->pot_pajak ?? 0, 2) }}</td></tr>
                    <tr><td>Bagi Hasil</td><td class="right">{{ number_format($slip->bagi_hasil ?? 0, 2) }}</td></tr>
                    <tr><td>Potongan Lain</td><td class="right">{{ number_format($slip->potongan_lain ?? 0, 2) }}</td></tr>
                <tr class="total-row"><td>Total Potongan</td><td class="right"><strong>{{ number_format($slip->total_potongan ?? 0, 2) }}</strong></td></tr>
            </table>

            <div class="section-title">Rekapitulasi</div>
            <table class="summary-table">
                <tr><th>Total Gaji (Rp)</th></tr>
                <tr>
                    <td class="right"><strong>{{ number_format($slip->total_gaji ?? 0, 2) }}</strong></td>
                </tr>
            </table>
            <div class="terbilang">Terbilang: {{ isset($terbilang) ? (ucwords($terbilang($slip->total_gaji ?? 0)) . ' rupiah') : '' }}</div>
        </td>
      </tr>
    </table>
    <div class="footer">
        <div style="text-align:right;">Dicetak pada: {{ date('d-m-Y H:i') }}<br> Sistem Payroll Belova Corp</div>
    </div>
    @if(isset($slip->jasmed_file) && $slip->jasmed_file)
        @php
            $filePath = storage_path('app/public/' . $slip->jasmed_file);
            $inlineImageData = null;
            $attachmentLabel = null;
            if (file_exists($filePath)) {
                $type = mime_content_type($filePath) ?: 'application/octet-stream';
                // Only prepare a data URI if the attachment is an image
                if (strpos($type, 'image/') === 0) {
                    $data = file_get_contents($filePath);
                    $base64 = base64_encode($data);
                    $inlineImageData = 'data:' . $type . ';base64,' . $base64;
                } else {
                    // For non-image attachments (PDFs etc.) show a small label. The controller
                    // already merges PDF attachments as separate pages when possible, so we
                    // avoid embedding PDF as data URI (which produced the large random text).
                    $attachmentLabel = basename($filePath);
                }
            }
        @endphp
        @if($inlineImageData)
            <div style="margin-top:32px; text-align:left;">
                <strong>Lampiran:</strong><br>
                <img src="{{ $inlineImageData }}" alt="Lampiran Jasmed File" style="max-width:320px; max-height:320px; border:1px solid #ccc;">
            </div>
        @endif
    @endif
</body>
</html>
