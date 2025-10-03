@php $employee = $slip->employee; @endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Slip Gaji - {{ $slip->employee->nama ?? '-' }}</title>
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
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #fff; color: #222; }
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
        .footer { margin-top: 30px; font-size: 0.95em; text-align: right; color: #666; }
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
                    <span style="font-size: 1.5em; font-weight: bold; color: #444;">Slip Gaji {{ $bulanTahun }}</span>
                </td>
            </tr>
        </table>
    </div>
    <table class="info-table">
        <tr>
            <td><strong>Nama</strong></td><td>: {{ $slip->employee->nama ?? '-' }}</td>
            <td><strong>No Induk</strong></td><td>: {{ $slip->employee->no_induk ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Posisi</strong></td><td>: {{ $slip->employee->position->name ?? '-' }}</td>
            <td><strong>Masa Kerja</strong></td><td>:
                @if($slip->employee->tanggal_masuk)
                    @php
                        $start = \Carbon\Carbon::parse($slip->employee->tanggal_masuk);
                        $now = now();
                        $diff = $start->diff($now);
                        $years = $diff->y;
                        $months = $diff->m;
                    @endphp
                    {{ $years }} tahun{{ $months > 0 ? ' ' . $months . ' bulan' : '' }}
                @else
                    -
                @endif
            </td>
        </tr>
        <tr>
            <td><strong>Divisi</strong></td><td>: {{ $slip->employee->division->name ?? '-' }}</td>
            <td><strong>Status</strong></td><td>: {{ ucfirst($slip->status_gaji) }}</td>
        </tr>
    </table>
    <table style="width:100%; border:none; border-collapse:collapse;">
      <tr>
        <td style="width:50%; vertical-align:top; padding-right:12px;">
            {{-- ...existing code... --}}
            <div class="section-title">Pendapatan</div>
            <table class="salary-table">
                <tr><th>Komponen</th><th class="right">Nominal (Rp)</th></tr>
                <tr><td>Gaji Pokok</td><td class="right">{{ number_format($slip->gaji_pokok, 2) }}</td></tr>
                <tr><td>Tunjangan Jabatan</td><td class="right">{{ number_format($slip->tunjangan_jabatan, 2) }}</td></tr>
                <tr><td>Tunjangan Masa Kerja</td><td class="right">{{ number_format($slip->tunjangan_masa_kerja, 2) }}</td></tr>
                <tr><td>Uang Makan</td><td class="right">{{ number_format($slip->uang_makan, 2) }}</td></tr>
                <tr><td>Jasa Medis</td><td class="right">{{ number_format($slip->jasa_medis, 2) }}</td></tr>
                <tr><td>Uang Lembur</td><td class="right">{{ number_format($slip->uang_lembur, 2) }}</td></tr>
                <tr><td>Uang KPI</td><td class="right">{{ number_format($slip->uang_kpi, 2) }}</td></tr>
                <tr class="total-row"><td>Total Pendapatan</td><td class="right"><strong>{{ number_format($slip->total_pendapatan, 2) }}</strong></td></tr>
            </table>
            <div class="section-title">Benefit</div>
            <table class="salary-table" style="margin-bottom: 18px;">
                <tr><th>Komponen</th><th class="right">Nominal (Rp)</th></tr>
                <tr><td>Benefit BPJS Kesehatan</td><td class="right">{{ number_format($slip->benefit_bpjs_kesehatan, 2) }}</td></tr>
                <tr><td>Benefit JHT</td><td class="right">{{ number_format($slip->benefit_jht, 2) }}</td></tr>
                <tr><td>Benefit JKK</td><td class="right">{{ number_format($slip->benefit_jkk, 2) }}</td></tr>
                <tr><td>Benefit JKM</td><td class="right">{{ number_format($slip->benefit_jkm, 2) }}</td></tr>
                <tr class="total-row"><td>Total Benefit</td><td class="right"><strong>{{ number_format($slip->total_benefit, 2) }}</strong></td></tr>
            </table>
        </td>
        <td style="width:50%; vertical-align:top; padding-left:12px;">
            <div class="section-title">Potongan</div>
            <table class="potongan-table">
                <tr><th>Komponen</th><th class="right">Nominal (Rp)</th></tr>
                <tr><td>Pinjaman</td><td class="right">{{ number_format($slip->potongan_pinjaman, 2) }}</td></tr>
                <tr><td>BPJS Kesehatan</td><td class="right">{{ number_format($slip->potongan_bpjs_kesehatan, 2) }}</td></tr>
                <tr><td>Jamsostek</td><td class="right">{{ number_format($slip->potongan_jamsostek, 2) }}</td></tr>
                <tr><td>Penalty</td><td class="right">{{ number_format($slip->potongan_penalty, 2) }}</td></tr>
                <tr><td>Potongan Lain</td><td class="right">{{ number_format($slip->potongan_lain, 2) }}</td></tr>
                <tr class="total-row"><td>Total Potongan</td><td class="right"><strong>{{ number_format($slip->total_potongan, 2) }}</strong></td></tr>
            </table>
            <div class="section-title">Absensi & KPI</div>
            <table class="summary-table" style="margin-bottom: 18px;">
                <tr>
                    <th>Total Hari Dijadwalkan</th>
                    <th>Total Hari Masuk</th>
                    <th>KPI Poin</th>
                    <th>Total Jam Lembur</th>
                </tr>
                <tr>
                    <td class="right">{{ $slip->total_hari_scheduled ?? '-' }}</td>
                    <td class="right">{{ $slip->total_hari_masuk ?? '-' }}</td>
                    <td class="right">{{ $slip->kpi_poin ?? '-' }}</td>
                    <td class="right">
                        @php
                            $menitlembur = $slip->total_jam_lembur ?? 0;
                            $jam = floor($menitlembur / 60);
                            $menit = $menitlembur % 60;
                        @endphp
                        @if($menitlembur)
                            {{ $jam }} jam{{ $menit > 0 ? ' ' . $menit . ' menit' : '' }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            </table>
            <div class="section-title">Rekapitulasi</div>
            <table class="summary-table">
                <tr><th>Total Gaji (Rp)</th></tr>
                <tr>
                    <td class="right"><strong>{{ number_format($slip->total_gaji, 2) }}</strong></td>
                </tr>
            </table>
            <div class="terbilang">Terbilang: {{ ucwords($terbilang($slip->total_gaji)) }} rupiah</div>
        </td>
      </tr>
    </table>
    <div class="footer">
        Dicetak pada: {{ date('d-m-Y H:i') }}<br>
        Sistem Payroll Belova Corp<br>
        <div style="margin-top:8px; font-size:0.95em; color:#c00; font-style:italic; text-align:left;">
            Catatan: Benefit adalah fasilitas dari perusahaan dan tidak termasuk dalam total gaji / take home pay.
        </div>
        @if($slip->jasmed_file)
            @php
                $filePath = storage_path('app/public/' . $slip->jasmed_file);
                $imageData = null;
                if (file_exists($filePath)) {
                    $type = mime_content_type($filePath) ?: 'image/jpeg';
                    $data = file_get_contents($filePath);
                    $base64 = base64_encode($data);
                    $imageData = 'data:' . $type . ';base64,' . $base64;
                }
            @endphp
            @if($imageData)
                <div style="margin-top:32px; text-align:left;">
                    <strong>Lampiran:</strong><br>
                    <img src="{{ $imageData }}" alt="Lampiran Jasmed File" style="max-width:320px; max-height:320px; border:1px solid #ccc;">
                </div>
            @else
                <div style="margin-top:32px; text-align:left; color:#c00;">Lampiran: (file tidak ditemukan)</div>
            @endif
        @endif
    </div>
</body>
</html>
