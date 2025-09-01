@php $employee = $slip->employee; @endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Slip Gaji - {{ $slip->employee->nama ?? '-' }}</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #fff; color: #222; }
        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .logo {
            height: 60px;
            margin: 0;
            display: block;
        }
        .slip-title { font-size: 1.5em; font-weight: bold; color: #444; }
        .bulan-label { font-size: 1em; color: #444; margin-top: 2px; }
        .info-table, .salary-table, .potongan-table, .summary-table {
            width: 100%; border-collapse: collapse; margin-bottom: 18px;
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
    <div style="width: 100%; margin-bottom: 30px;">
        <table style="width: 100%; border: none; border-collapse: collapse;">
            <tr>
                <td style="width: 1px; vertical-align: middle;">
                    <img src="{{ public_path('img/header-belovacorp.png') }}" alt="Company Logo" style="height: 60px; display: block;">
                </td>
                <td style="text-align: right; vertical-align: middle;">
                    <span style="font-size: 1.5em; font-weight: bold; color: #444;">Slip Gaji Januari 2025</span>
                </td>
            </tr>
        </table>
    </div>
    <table style="width: 100%; margin-bottom: 10px;">
        <tr>
            <td style="font-weight: bold; color: #444;">Total Hari Dijadwalkan</td>
            <td>: {{ $slip->total_hari_dijadwalkan ?? '-' }}</td>
            <td style="font-weight: bold; color: #444;">Total Hari Masuk</td>
            <td>: {{ $slip->total_hari_masuk ?? '-' }}</td>
        </tr>
    </table>
    <table class="info-table">
        <tr>
            <td><strong>Nama</strong></td><td>: {{ $slip->employee->nama ?? '-' }}</td>
            <td><strong>No Induk</strong></td><td>: {{ $slip->employee->no_induk ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Divisi</strong></td><td>: {{ $slip->employee->division->name ?? '-' }}</td>
            <td><strong>Status</strong></td><td>: {{ ucfirst($slip->status_gaji) }}</td>
        </tr>
    </table>
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
        <tr class="total-row"><td>Total Pendapatan</td><td class="right">{{ number_format($slip->total_pendapatan, 2) }}</td></tr>
    </table>
    <div class="section-title">Potongan</div>
    <table class="potongan-table">
        <tr><th>Komponen</th><th class="right">Nominal (Rp)</th></tr>
        <tr><td>Pinjaman</td><td class="right">{{ number_format($slip->potongan_pinjaman, 2) }}</td></tr>
        <tr><td>BPJS Kesehatan</td><td class="right">{{ number_format($slip->potongan_bpjs_kesehatan, 2) }}</td></tr>
        <tr><td>Jamsostek</td><td class="right">{{ number_format($slip->potongan_jamsostek, 2) }}</td></tr>
        <tr><td>Penalty</td><td class="right">{{ number_format($slip->potongan_penalty, 2) }}</td></tr>
        <tr><td>Potongan Lain</td><td class="right">{{ number_format($slip->potongan_lain, 2) }}</td></tr>
        <tr class="total-row"><td>Total Potongan</td><td class="right">{{ number_format($slip->total_potongan, 2) }}</td></tr>
    </table>
    <div class="section-title">Rekapitulasi</div>
    <table class="summary-table">
        <tr><th>KPI Poin</th><th>Total Jam Lembur</th><th>Total Gaji (Rp)</th></tr>
        <tr>
            <td class="right">{{ $slip->kpi_poin }}</td>
            <td class="right">{{ $slip->total_jam_lembur }}</td>
            <td class="right">{{ number_format($slip->total_gaji, 2) }}</td>
        </tr>
    </table>
    <div class="terbilang">Terbilang: {{ ucwords($terbilang($slip->total_gaji)) }} rupiah</div>
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-label">Diterima Oleh,</div>
            <div style="height:60px;"></div>
            <div><strong>{{ $slip->employee->nama ?? '-' }}</strong></div>
        </div>
        <div class="signature-box">
            <div class="signature-label">Diketahui Oleh,</div>
            <div style="height:60px;"></div>
            <div><strong>HRD</strong></div>
        </div>
    </div>
    <div class="footer">
        Dicetak pada: {{ date('d-m-Y H:i') }}<br>
        Sistem Payroll PT. Nama Perusahaan Anda
    </div>
</body>
</html>
