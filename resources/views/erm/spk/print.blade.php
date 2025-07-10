<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPK - {{ $pasienNama }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h2 {
            margin: 5px 0;
        }
        .info-box {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .footer {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 45%;
            text-align: center;
        }
        .signature-line {
            margin-top: 60px;
            border-top: 1px solid #000;
            padding-top: 5px;
        }
        .checkbox {
            width: 15px;
            height: 15px;
            border: 1px solid #000;
            display: inline-block;
            margin-right: 5px;
            text-align: center;
        }
        .checkbox.checked:after {
            content: "✓";
            font-weight: bold;
        }
        .identity-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media print {
            .identity-grid { grid-template-columns: 1fr 1fr !important; }
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ asset('img/logo-belovaskin.png') }}" alt="Belovaskin Logo" style="height: 60px; margin-bottom: 10px; display: block; margin-left: auto; margin-right: auto;">
        <h2>STANDAR PROSEDUR KERJA & CUCI TANGAN</h2>
        <h3>{{ $riwayat->tindakan ? $riwayat->tindakan->nama : 'Tindakan' }}</h3>
    </div>
    
    <div class="info-box" style="padding: 10px 16px; margin-bottom: 10px; border-radius: 5px;">
        <table style="width:100%; border:none; border-collapse:collapse;">
            <tr>
                <td style="vertical-align:top; width:50%; border:none; padding:0 8px 0 0;">
                    <div><strong>Nama Pasien:</strong> {{ $pasienNama }}</div>
                    <div><strong>Tanggal Tindakan:</strong> {{ $tanggalTindakan }}</div>
                    <div><strong>Nama Tindakan:</strong> {{ $tindakanNama }}</div>
                </td>
                <td style="vertical-align:top; width:50%; border:none; padding:0 0 0 8px;">
                    <div><strong>No. RM:</strong> {{ $pasienId }}</div>
                    <div><strong>Dokter:</strong> {{ $dokterNama }}</div>
                    <div><strong>Harga:</strong> Rp {{ number_format($harga, 0, ',', '.') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama SOP</th>
                <th>Penanggung Jawab</th>
                <th>SBK</th>
                <th>SBA</th>
                <th>SDC</th>
                <th>SDK</th>
                <th>SDL</th>
                <th>Waktu Mulai</th>
                <th>Waktu Selesai</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sopList as $index => $sop)
                @php
                    $existingDetail = $spk && $spk->details ? $spk->details->firstWhere('sop_id', $sop->id) : null;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $sop->nama_sop }}</td>
                    <td>{{ $existingDetail ? $existingDetail->penanggung_jawab : '' }}</td>
                    <td style="text-align:center">
                        <div class="checkbox {{ $existingDetail && $existingDetail->sbk ? 'checked' : '' }}"></div>
                    </td>
                    <td style="text-align:center">
                        <div class="checkbox {{ $existingDetail && $existingDetail->sba ? 'checked' : '' }}"></div>
                    </td>
                    <td style="text-align:center">
                        <div class="checkbox {{ $existingDetail && $existingDetail->sdc ? 'checked' : '' }}"></div>
                    </td>
                    <td style="text-align:center">
                        <div class="checkbox {{ $existingDetail && $existingDetail->sdk ? 'checked' : '' }}"></div>
                    </td>
                    <td style="text-align:center">
                        <div class="checkbox {{ $existingDetail && $existingDetail->sdl ? 'checked' : '' }}"></div>
                    </td>
                    <td>{{ $existingDetail && $existingDetail->waktu_mulai ? \Carbon\Carbon::parse($existingDetail->waktu_mulai)->format('H:i') : '' }}</td>
                    <td>{{ $existingDetail && $existingDetail->waktu_selesai ? \Carbon\Carbon::parse($existingDetail->waktu_selesai)->format('H:i') : '' }}</td>
                    <td>{{ $existingDetail ? $existingDetail->notes : '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 30px;">
        <p><strong>Keterangan:</strong></p>
        <p>SBK : Sebelum Kontak dengan pasien</p>
        <p>SBA : Sebelum melakukan tindakan aseptik</p>
        <p>SDC : Setelah terkena cairan tubuh</p>
        <p>SDK : Setelah kontak dengan pasien</p>
        <p>SDL : Setelah kontak dengan lingkungan pasien</p>
    </div>
</body>
</html>
