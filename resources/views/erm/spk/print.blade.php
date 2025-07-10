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
    </style>
</head>
<body>
    <div class="header">
        <h2>STANDAR PROSEDUR KERJA & CUCI TANGAN</h2>
        <h3>{{ $riwayat->tindakan ? $riwayat->tindakan->nama : 'Tindakan' }}</h3>
    </div>
    
    <div class="info-box">
        <table style="border: none; width: 100%;">
            <tr>
                <td style="border: none; width: 25%;"><strong>Nama Pasien</strong></td>
                <td style="border: none; width: 25%;">: {{ $pasienNama }}</td>
                <td style="border: none; width: 25%;"><strong>No. RM</strong></td>
                <td style="border: none; width: 25%;">: {{ $pasienId }}</td>
            </tr>
            <tr>
                <td style="border: none;"><strong>Tanggal Tindakan</strong></td>
                <td style="border: none;">: {{ $tanggalTindakan }}</td>
                <td style="border: none;"><strong>Dokter</strong></td>
                <td style="border: none;">: {{ $dokterNama }}</td>
            </tr>
            <tr>
                <td style="border: none;"><strong>Nama Tindakan</strong></td>
                <td style="border: none;">: {{ $tindakanNama }}</td>
                <td style="border: none;"><strong>Harga</strong></td>
                <td style="border: none;">: Rp {{ number_format($harga, 0, ',', '.') }}</td>
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

    <div class="footer">
        <div class="signature-box">
            <p>Dokter</p>
            <div class="signature-line">{{ $dokterNama }}</div>
        </div>
        <div class="signature-box">
            <p>Beautician</p>
            <div class="signature-line"></div>
        </div>
    </div>
    
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
