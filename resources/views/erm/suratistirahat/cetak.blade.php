<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Keterangan Istirahat</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12pt;
            margin: 2cm;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            border-bottom: 2px solid black;
            padding-bottom: 0.5rem;
        }
        header .left img {
            max-height: 80px;
        }
        header .right {
            text-align: right;
            font-size: 10pt;
            line-height: 1.2;
        }
        h1.title {
            text-align: center;
            font-weight: bold;
            margin: 1.5rem 0;
            font-size: 16pt;
            text-transform: uppercase;
        }
        .patient-info {
            margin-bottom: 1rem;
        }
        .patient-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .patient-info td {
            padding: 4px 8px;
            vertical-align: top;
        }
        .patient-info td.label {
            width: 120px;
            font-weight: bold;
        }
        .content {
            margin-bottom: 3rem;
        }
        .signature {
            position: relative;
            width: 200px;
            float: right;
            text-align: center;
            font-size: 11pt;
        }
        .signature .date-place {
            margin-bottom: 2.5rem;
        }
        .signature img.qr {
            width: 100px;
            height: 100px;
            margin-bottom: 0.3rem;
        }
        .clear {
            clear: both;
        }
    </style>
</head>
<body>

<table width="100%" style="border-bottom: 2px solid black; padding-bottom: 0.5rem; margin-bottom: 1rem;">
    <tr>
        <td style="width: 80px; vertical-align: top;">
            <img src="{{ public_path('img/logo-premiere.png') }}" style="max-height: 80px;" alt="Logo">
        </td>
        <td style="text-align: right; font-size: 10pt; line-height: 1.4;">
            Jl. Melon Raya I, RT 003 RW 007, Karangasem,<br>
            Laweyan, Surakarta, Jawa Tengah<br>
            082 116 000 093<br>
            Premierebelova@gmail.com
        </td>
    </tr>
</table>

<h1 class="title">Surat Keterangan Istirahat</h1>

<div class="patient-info">
    <table>
        <tr>
            <td class="label">Nama</td>
            <td>:</td>
            <td>{{ $surat->pasien->nama ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Umur</td>
            <td>:</td>
            <td>
                {{-- Example: calculate umur tahun, bulan, hari --}}
                @php
                    $tgl_lahir = \Carbon\Carbon::parse($surat->pasien->tanggal_lahir);
                    $tgl_surat = \Carbon\Carbon::parse($surat->tanggal_mulai);
                    $diff = $tgl_lahir->diff($tgl_surat);
                @endphp
                {{ $diff->y }} tahun {{ $diff->m }} bulan {{ $diff->d }} hari
            </td>
        </tr>
        <tr>
            <td class="label">Pekerjaan</td>
            <td>:</td>
            <td>{{ $surat->pasien->pekerjaan ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Alamat</td>
            <td>:</td>
            <td>{{ $surat->pasien->alamat ?? '-' }}</td>
        </tr>
    </table>
</div>

<div class="content">
    Diberikan istirahat sakit selama {{ $surat->jumlah_hari }} hari terhitung<br>
    Mulai tanggal {{ \Carbon\Carbon::parse($surat->tanggal_mulai)->translatedFormat('j F Y') }} s.d {{ \Carbon\Carbon::parse($surat->tanggal_selesai)->translatedFormat('j F Y') }}<br><br>

    Demikian surat keterangan ini diberikan untuk diketahui dan dipergunakan seperlunya.
</div>

<div class="signature">
    <div class="date-place">
        Surakarta, {{ \Carbon\Carbon::parse($surat->tanggal_mulai)->translatedFormat('j F Y') }}
    </div>
    <div>Dokter Pemeriksa</div>
   
    @php
    $qrText = urlencode($surat->dokter->user->name ?? '-');
    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?data={$qrText}&size=100x100";
@endphp

<img src="{{ $qrUrl }}" alt="QR Code" class="qr">

    <div><strong>{{ $surat->dokter->user->name ?? '-' }}</strong></div>
</div>

<div class="clear"></div>

</body>
</html>
