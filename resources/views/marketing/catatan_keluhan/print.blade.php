@php $pasien = $catatan->pasien; @endphp
<style>
    body { font-family: 'Times New Roman', Times, serif; font-size: 13px; background: #f8f9fa; color: #000; }
    .main-content {
        /* Padding is handled by controller */
    }
    .section-title {
        font-size: 15px;
        font-weight: bold;
        color: #000;
        margin-top: 25px;
        margin-bottom: 8px;
        border-bottom: 1px solid #000;
        padding-bottom: 2px;
    }
    .info-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
        background: #fff;
        box-shadow: 0 2px 8px #0001;
    }
    .info-table th, .info-table td {
        border: 1px solid #000;
        padding: 8px 12px;
        vertical-align: top;
    }
    .info-table th {
        background: #f1f3f6;
        width: 180px;
        text-align: left;
    }
    .label {
        font-weight: bold;
        color: #333;
    }
    .bukti-img {
        margin-top: 8px;
        max-width: 300px;
        max-height: 300px;
        border: 1px solid #ccc;
        border-radius: 6px;
        box-shadow: 0 1px 4px #0002;
    }
    .footer {
        margin-top: 40px;
        text-align: right;
        color: #666;
        font-size: 12px;
    }
</style>
<div class="main-content" style="padding-top:4mm;">
    <div style="height:0; border-bottom:2px solid #000; margin-bottom:10px;"></div>
    <div style="text-align:center; font-size:26px; font-weight:bold; margin-bottom:10px; letter-spacing:2px;">LAPORAN KELUHAN PELANGGAN</div>
    <div class="section-title">Identitas Pasien & Kunjungan</div>
    <table class="info-table" style="width:100%;">
        <tr>
            <td style="width:50%; vertical-align:top; border:none; padding:0;">
                <table style="width:100%; border-collapse:collapse;">
                    <tr><th style="width:120px;">Nama Pasien</th><td>{{ $pasien ? $pasien->nama : '-' }}</td></tr>
                    <tr><th>No RM</th><td>{{ $pasien ? $pasien->id : '-' }}</td></tr>
                    <tr><th>No HP</th><td>{{ $pasien ? $pasien->no_hp : '-' }}</td></tr>
                </table>
            </td>
            <td style="width:50%; vertical-align:top; border:none; padding:0;">
                <table style="width:100%; border-collapse:collapse;">
                    <tr><th style="width:120px;">Tanggal Kunjungan</th><td>{{ $catatan->visit_date }}</td></tr>
                    <tr><th>Unit</th><td>{{ $catatan->unit }}</td></tr>
                    <tr><th>Kategori</th><td>{{ $catatan->kategori }}</td></tr>
                </table>
            </td>
        </tr>
    </table>
    <div class="section-title">Keluhan, Penyelesaian & Bukti</div>
    <table class="info-table">
        <tr><th>Keluhan</th><td>{{ $catatan->keluhan }}</td></tr>
        <tr><th>Penyelesaian</th><td>{{ $catatan->penyelesaian }}</td></tr>
        @if($catatan->bukti)
        <tr>
            <th>Bukti</th>
            <td>
                @php
                    $ext = strtolower(pathinfo($catatan->bukti, PATHINFO_EXTENSION));
                @endphp
                @if(in_array($ext, ['jpg','jpeg','png','gif','bmp','webp']))
                    <img src="{{ public_path() . $catatan->bukti }}" class="bukti-img" alt="Bukti Keluhan">
                @elseif($ext === 'pdf')
                    <a href="{{ public_path() . $catatan->bukti }}">Lihat Bukti (PDF)</a>
                @else
                    <a href="{{ public_path() . $catatan->bukti }}">Download Bukti</a>
                @endif
            </td>
        </tr>
        @endif
    </table>

    <div class="section-title">Rencana & Deadline Perbaikan</div>
    <table class="info-table">
        <tr><th>Rencana Perbaikan</th><td>{{ $catatan->rencana_perbaikan }}</td></tr>
        <tr><th>Batas Waktu Perbaikan</th><td>{{ $catatan->deadline_perbaikan }}</td></tr>
        <tr><th>Status</th><td>{{ $catatan->status }}</td></tr>
    </table>

    <div class="footer">
        Tanggal Cetak: {{ date('d-m-Y') }}
    </div>
</div>
