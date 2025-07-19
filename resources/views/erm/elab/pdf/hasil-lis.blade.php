<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Laboratorium</title>
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
            /* background-image dihapus, gunakan header/footer mPDF saja */
        }
        .content-wrapper {
            position: relative;
            z-index: 10;
            padding: 5px 10px;
            min-height: 90%;
            margin-top: 10px;
            width: 100%;
            margin-left: 0;
            margin-right: 0;
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
            margin-bottom: 10px;
            padding-bottom: 5px;
            /* padding-top dihapus agar tidak ada blank space dari header image mPDF */
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
            padding: 6px;
            border: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
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
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .signature {
            text-align: center;
            font-size: 10px;
            margin-left: auto;
            margin-right: 0;
            width: 220px;
        }
        .signature-date {
            text-align: left;
            font-size: 10px;
        }
        .signature {
            text-align: center;
            font-size: 10px;
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
    </style>
</head>
<body>
    
    <div class="content-wrapper">
        <div class="header">
            <h2>HASIL PEMERIKSAAN LABORATORIUM</h2>
        </div>
    
    <table class="patient-info">
        <tr>
            <!-- LEFT SIDE -->
            <td style="white-space:nowrap;">Nama Pasien</td>
            <td style="width:10px; text-align:center;">:</td>
            <td style="white-space:nowrap;">{{ $visitation->pasien->nama }}</td>
            <!-- RIGHT SIDE -->
            <td style="white-space:nowrap;">No. Rekam Medis</td>
            <td style="width:10px; text-align:center;">:</td>
            <td style="white-space:nowrap;">{{ $visitation->pasien->id }}</td>
        </tr>
        <tr>
            <!-- LEFT SIDE -->
            <td style="white-space:nowrap;">Jenis Kelamin</td>
            <td style="width:10px; text-align:center;">:</td>
            <td style="white-space:nowrap;">{{ $visitation->pasien->gender }}</td>
            <!-- RIGHT SIDE -->
            <td style="white-space:nowrap;">Tanggal Pemeriksaan</td>
            <td style="width:10px; text-align:center;">:</td>
            <td style="white-space:nowrap;">
                {{ $tanggalVisitation }}
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
            <td style="word-break:break-word; white-space:normal; max-width: 220px;">{{ $visitation->dokter->user->name ?? 'Tidak ada dokter' }}</td>
        </tr>
        <tr>
            <!-- LEFT SIDE -->
            <td style="white-space:nowrap;">Alamat Pasien</td>
            <td style="width:10px; text-align:center;">:</td>
            <td style="white-space:normal;">{{ $visitation->pasien->alamat }}</td>
            <!-- RIGHT SIDE -->
            <td style="white-space:nowrap;">Diagnosa</td>
            <td style="width:10px; text-align:center;">:</td>
            <td style="white-space:normal;">{{ isset($diagnosaKerja) && count($diagnosaKerja) ? implode('; ', $diagnosaKerja) : '-' }}</td>
        </tr>
    </table>
    
    @if(count($hasilLis) > 0)
        <table class="lab-results">
            <thead>
                <tr>
                    <th width="35%">Pemeriksaan</th>
                    <th width="10%">Hasil</th>
                    <th width="5%">Flag</th>
                    <th width="25%">Nilai Rujukan</th>
                    <th width="10%">Satuan</th>
                    <th width="15%">Metode</th>
                </tr>
            </thead>
            <tbody>
                
                @foreach($groupedData as $header => $subHeaders)
                    <tr>
                        <td colspan="6" class="lab-header">{{ $header }}</td>
                    </tr>
                    
                    @foreach($subHeaders as $subHeader => $items)
                        @if($subHeader)
                            <tr>
                                <td colspan="6" class="lab-subheader">{{ $subHeader }}</td>
                            </tr>
                        @endif
                        
                        @foreach($items as $item)
                            <tr>
                                <td>{{ $item->nama_test }}</td>
                                <td class="result-center">{{ $item->hasil }}</td>
                                <td class="flag-{{ $item->flag }}">{{ $item->flag }}</td>
                                <td>{{ $item->nilai_rujukan }}</td>
                                <td class="result-center">{{ $item->satuan }}</td>
                                <td>{{ $item->metode }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                @endforeach
                
                @if(count($groupedData) == 0)
                    <tr>
                        <td colspan="6" style="text-align: center;">Tidak ada data hasil laboratorium</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @else
        <p style="text-align: center;">Tidak ada data hasil laboratorium untuk kunjungan ini.</p>
    @endif
    
    <div class="signature-container">
        <div class="signature-date">
            <p>* Dokumen ini dicetak pada {{ now()->format('d-m-Y H:i:s') }} dan merupakan hasil resmi laboratorium.</p>
            <p>* Hasil yang telah diberikan tidak dapat diminta kembali (bila hilang) tanpa pemeriksaan ulang.</p>
            <p>* Untuk riwayat pemeriksaan, lihat lembar monitoring laboratorium.</p>
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
    
    {{-- <div class="footer-notes">
    </div> --}}
    </div><!-- End content-wrapper -->
</body>
</html>
