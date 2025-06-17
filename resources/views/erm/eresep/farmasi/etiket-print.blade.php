<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiket Obat</title>
    <style>
        @page {
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            /* padding: 5px; */
            font-size: 10pt;
            width: 100%;
            /* height: 100%; */
            
        }
        .page-break {
            page-break-after: always;
        }
        .etiket {
            width: 100%;
            height: 99%;
            border: 1px solid #000;
        }
        .header {
            text-align: center;
            font-weight: bold;
            padding: 5px;
            font-size: 10pt; /* Reduced from 12pt */
            border-bottom: 1px solid #000;
        }
        .info-container {
            padding: 5px;
            border-bottom: 1px dotted #000;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6pt;
        }
        .info-table td {
            padding: 2px;
            vertical-align: top;
            text-align: left; /* Ensure all text is left-aligned */
        }
        .info-left {
            width: 50%;
        }
        .info-right {
            width: 50%;
        }
        .label {
            font-weight: normal;
        }
        .obat-detail {
            padding: 8px 5px;
            font-weight: bold;
            font-size: 10pt; /* Reduced from 14pt */
            text-align: center;
            border-bottom: 1px dotted #000;
        }
        .aturan-pakai {
            margin: 8px 5px;
            text-align: center;
            font-weight: bold;
            font-size: 12pt; /* Reduced from 16pt */
            border: 1px solid #000;
            padding: 6px 3px;
        }
        .footer {
            margin: 8px 5px;
            text-align: center;
            font-style: italic;
            font-size: 8pt;
        }
    </style>
</head>
<body>
    @foreach($reseps as $index => $resep)
    <div class="etiket">
        <div class="header">
            Klinik Utama Premiere Belova
        </div>
        
        <div class="info-container">
            <table class="info-table">
                <tr>
                    <td class="info-left">
                        <span class="label">No. RM: </span>{{ $visitation->pasien->id ?? '-' }}
                    </td>
                    <td class="info-right">
                        <span class="label">Nama: </span>{{ $visitation->pasien->nama ?? '-' }}
                    </td>
                </tr>
                <tr>
                    <td class="info-left">
                        <span class="label">Tgl Lahir: </span>{{ \Carbon\Carbon::parse($visitation->pasien->tanggal_lahir)->isoFormat('D MMMM Y') ?? '-' }}
                    </td>
                    <td class="info-right">
                        <span class="label">Dokter: </span>{{ $visitation->dokter->user->name ?? '-' }}
                    </td>
                </tr>
                <tr>
                    <td class="info-left" colspan="2">
                        <span class="label">Tanggal Kunjungan: </span>{{ \Carbon\Carbon::parse($visitation->tanggal_visitation)->isoFormat('D MMMM Y') ?? date('d F Y') }}
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="obat-detail">
            {{ $resep->obat->nama ?? '-' }} ({{ $resep->jumlah ?? '-' }})
        </div>
        
        <div class="aturan-pakai">
            {{ $resep->aturan_pakai ?? 'Sesuai petunjuk dokter' }}
        </div>
        
        {{-- <div class="footer">
            Simpan di tempat kering dan jauh dari jangkauan anak-anak
        </div> --}}
    </div>
    
    {{-- Remove or comment out the page break below if you want labels to appear consecutively --}}
    {{-- @if(!$loop->last)
    <div class="page-break"></div>
    @endif --}}
    @endforeach
</body>
</html>