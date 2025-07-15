<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lembar Monitoring Laboratorium</title>
    <style>
        .patient-info tr {
            height: auto;
        }
        .patient-info td {
            padding: 4px 8px 4px 0;
            vertical-align: top;
        }
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
            background-image: url('{{ $backgroundData }}');
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
        }
        .content-wrapper {
            position: relative;
            z-index: 10;
            padding: 10px 40px;
            min-height: 90%;
            margin-top: 20px;
            width: 85%;
            margin-left: auto;
            margin-right: auto;
        }
        table {
            background-color: transparent;
        }
        .patient-info {
            margin-top: 20px;
            border: none;
            font-size: 11px;
        }
        .patient-info td {
            border: none;
            padding: 3px 4px 3px 2px;
            /* Reduce left padding for less gap */
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            padding-top: 120px; /* Make space for the letterhead in the background image */
        }
        .header h2 {
            margin: 0;
            font-size: 16px;
            color: #003366;
            font-weight: bold;
        }
        .header p {
            margin: 2px 0;
            font-size: 11px;
        }
        .patient-info {
            width: 100%;
            table-layout: auto;
            border-collapse: collapse;
            margin-bottom: 5px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 5px;
        }
        .patient-info td {
            padding: 5px;
            vertical-align: top;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .lab-results {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: rgba(255, 255, 255, 0.9);
        }
        .lab-results th {
            background-color: white;
            color: black;
            padding: 8px 6px;
            border: 1px solid #ddd;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            vertical-align: middle;
        }
        .lab-results td {
            padding: 4px 6px;
            border: 1px solid #ddd;
            font-size: 10px;
            vertical-align: middle;
        }
        .lab-header {
            background-color: white;
            color: black;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            /* text-align: center; */
            padding: 5px !important;
        }
        .lab-subheader {
            background-color: white;
            /* font-style: italic; */
            padding: 5px !important;
            font-weight: bold;
            font-size: 10px;
        }
        .flag-H {
            color: red;
            font-weight: bold;
            text-align: center;
        }
        .flag-L {
            color: blue;
            font-weight: bold;
            text-align: center;
        }
        .flag-N {
            color: green;
            text-align: center;
        }
        td.result-center {
            text-align: center;
        }
        .footer {
            margin-top: 30px;
            border-top: 1px solid #0056a4;
            padding-top: 10px;
            font-size: 10px;
            background-color: rgba(255, 255, 255, 0.9);
            padding: 10px;
            border-radius: 5px;
        }
        .signature-container {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        .signature-date {
            text-align: left;
            font-size: 10px;
        }
        .signature {
            text-align: center;
            font-size: 10px;
            margin-left: auto;
            margin-right: 0;
            width: 220px;
        }
        .signature-line {
            margin-top: 40px;
            border-top: 1px solid #000;
            width: 150px;
            display: inline-block;
        }
        .page-break {
            page-break-after: always;
        }
        .footer-notes {
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 15px;
            font-size: 10px;
        }
        /* Special styles for monitoring table */
        .monitoring-results th {
            background-color: #f0f5fa;
            font-size: 9px;
            padding: 4px;
        }
        .monitoring-results td {
            font-size: 9px;
            padding: 4px;
        }
        .notes-section {
            margin-top: 15px;
            font-size: 10px;
            padding: 8px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 5px;
            font-style: italic;
        }
    </style>
</head>
<body>
    
    <div class="content-wrapper">
        <div class="header">
            <h2>LEMBAR MONITORING LABORATORIUM</h2>
        </div>
    
    <table class="patient-info">
        <tr>
            <!-- LEFT SIDE -->
            <td style="white-space:nowrap;">Nama Pasien</td>
            <td style="width:10px; text-align:center;">:</td>
            <td style="white-space:nowrap;">{{ $pasien->nama }}</td>
            <!-- RIGHT SIDE -->
            <td style="white-space:nowrap;">No. Rekam Medis</td>
            <td style="width:10px; text-align:center;">:</td>
            <td style="white-space:nowrap;">{{ $pasien->id }}</td>
        </tr>
        <tr>
            <!-- LEFT SIDE -->
            <td style="white-space:nowrap;">Jenis Kelamin</td>
            <td style="width:10px; text-align:center;">:</td>
            <td style="white-space:nowrap;">{{ $pasien->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
            <!-- RIGHT SIDE -->
            <td style="white-space:nowrap;">Tanggal Cetak</td>
            <td style="width:10px; text-align:center;">:</td>
            <td style="white-space:nowrap;">
                {{ $tanggalSekarang }}
            </td>
        </tr>
        <tr>
            <!-- LEFT SIDE -->
            <td style="white-space:nowrap;">Tanggal Lahir</td>
            <td style="width:10px; text-align:center;">:</td>
            <td style="white-space:nowrap;">
                {{ $tanggalLahir }} ({{ $umurPasien }})
            </td>
            <!-- RIGHT SIDE -->
            <td style="white-space:nowrap;">Dokter</td>
            <td style="width:10px; text-align:center;">:</td>
            <td style="word-break:break-word; white-space:normal; max-width: 220px;">{{ $latestVisit && $latestVisit->dokter ? $latestVisit->dokter->user->name : '-' }}</td>
        </tr>
        <tr>
            <!-- LEFT SIDE -->
            <td style="white-space:nowrap;">Alamat Pasien</td>
            <td style="width:10px; text-align:center;">:</td>
            <td style="white-space:normal;">{{ $pasien->alamat }}</td>
            <!-- RIGHT SIDE -->
            <td style="white-space:nowrap;">Diagnosa</td>
            <td style="width:10px; text-align:center;">:</td>
            <td style="white-space:normal;">{{ isset($diagnosaKerja) && count($diagnosaKerja) ? implode('; ', $diagnosaKerja) : '-' }}</td>
        </tr>
    </table>
    
    @if(count($monitoringData) > 0)
        <table class="lab-results monitoring-results">
            <thead>
                <tr>
                    <th width="40%">Pemeriksaan</th>
                    @foreach($visitDates as $index => $visitDate)
                        <th>{{ !empty($visitDate) ? $visitDate : '-' }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($monitoringData as $header => $subHeaders)
                    <tr>
                        <td colspan="{{ 1 + count($visitDates) }}" class="lab-header">{{ $header }}</td>
                    </tr>
                    
                    @foreach($subHeaders as $subHeader => $items)
                        @if($subHeader)
                            <tr>
                                <td colspan="{{ 1 + count($visitDates) }}" class="lab-subheader">{{ $subHeader }}</td>
                            </tr>
                        @endif
                        
                        @foreach($items as $testName => $results)
                            <tr>
                                <td>{{ $testName }}</td>
                                @foreach($visitDates as $visitDate)
                                    <td class="result-center">
                                        @if(isset($results[$visitDate]))
                                            {{ $results[$visitDate]['hasil'] }}
                                            @if(isset($results[$visitDate]['flag']) && $results[$visitDate]['flag'])
                                                <span class="flag-{{ $results[$visitDate]['flag'] }}">{{ $results[$visitDate]['flag'] }}</span>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                @endforeach
            </tbody>
        </table>
        
        <div class="notes-section">
            <p><strong>Catatan:</strong> Laporan ini menampilkan riwayat maksimal 5 pemeriksaan laboratorium terakhir yang memiliki hasil.</p>
        </div>
    @else
        <p style="text-align: center;">Tidak ada data monitoring laboratorium untuk pasien ini.</p>
    @endif
    
    <div class="signature-container">
        <div class="signature-date">
            <p>* Dokumen ini dicetak pada {{ now()->format('d-m-Y H:i:s') }} dan merupakan hasil monitoring laboratorium.</p>
            <p>* Kolom tanggal menunjukkan hasil pemeriksaan pada tanggal tersebut (maksimal 5 kali pemeriksaan terakhir).</p>
        </div>
        <div class="signature">
            <p>Surakarta, {{ $tanggalSekarang }}</p>
            <p>Dokter Penanggung Jawab</p>
            @if($dokterLab)
                @if($qrCodeData)
                    <img src="{{ $qrCodeData }}" alt="QR TTD" style="height:80px; margin-bottom:4px;" />
                @else
                    <div style="height:80px; margin-bottom:4px;"></div>
                @endif
                <p style="margin-top: 8px; font-weight: bold;">{{ $dokterLab['name'] }}</p>
            @else
                <div style="height:80px; margin-bottom:4px;"></div>
                <p style="margin-top: 8px;">(..................................)</p>
            @endif
        </div>
    </div>
    </div><!-- End content-wrapper -->
</body>
</html>
