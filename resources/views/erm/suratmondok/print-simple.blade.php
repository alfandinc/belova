<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Surat Keterangan Mondok</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 12px;
      margin: 20px;
      line-height: 1.2;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 8px;
    }
    td, th {
      border: 1px solid #000;
      padding: 4px;
      vertical-align: top;
    }
    .no-border td {
      border: none;
    }
    .bold {
      font-weight: bold;
    }
    .center {
      text-align: center;
    }
    .header-section {
      margin-bottom: 15px;
    }
    .letter-content {
      text-align: justify;
      margin-bottom: 10px;
      line-height: 1.1;
    }
    .patient-info td {
      padding: 2px 6px;
    }
    p {
      margin: 5px 0;
      line-height: 1.1;
    }
  </style>
</head>
<body>
<table class="no-border header-section">
  <tr>
    <td style="width: 20%;" class="header-logo">
      @php
        $logoPath = public_path('img/logo-premiere.png');
      @endphp
      @if(file_exists($logoPath))
        @php
          $logoData = base64_encode(file_get_contents($logoPath));
          $logoMimeType = mime_content_type($logoPath);
        @endphp
        <img src="data:{{ $logoMimeType }};base64,{{ $logoData }}" alt="Logo" width="130" style="max-height: 60px;">
      @else
        <div style="width: 130px; height: 60px; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center; font-size: 10px;">Logo Not Found</div>
      @endif
    </td>
    <td style="width: 80%; text-align: right; font-size: 11px;">
        <strong>KLINIK UTAMA PREMIERE BELOVA</strong> <br>
        Jl. Melon Raya No.27, Karangasem, Laweyan, Surakarta<br>
        Telp. 0821-1600-0093 <br>
        www.premierebelova.id <br>
    </td>
  </tr>
</table>

<hr>

<div style="margin-bottom: 12px;">
<p><strong>Kepada Yth</strong></p>
<p><strong>{{ $tujuan_igd }}</strong></p>
<p><strong>Dengan Hormat,</strong></p>
<p>Bersama ini kami mohon diberikan rawat inap untuk pasien saya.</p>
</div>

<table class="no-border patient-info">
  <tr>
    <td style="width: 15%; font-weight: bold;">Nama</td>
    <td style="width: 5%;">:</td>
    <td style="width: 80%;">{{ strtoupper($nama) }}</td>
  </tr>
  <tr>
    <td style="font-weight: bold;">Umur</td>
    <td>:</td>
    <td>{{ $umur }}</td>
  </tr>
  <tr>
    <td style="font-weight: bold;">Alamat</td>
    <td>:</td>
    <td>{{ strtoupper($alamat) }}</td>
  </tr>
  <tr>
    <td style="font-weight: bold; vertical-align: top;">Diagnosa</td>
    <td style="vertical-align: top;">:</td>
    <td>{{ $diagnosa }}</td>
  </tr>
</table>

<div class="letter-content">
<p><strong>Atas Perhatian dan kerjasamanya kami ucapkan terima kasih.</strong></p>
</div>

<div class="letter-content">
<p><strong>Instruksi Terapi:</strong></p>
<p>{!! nl2br(e($instruksi_terapi)) !!}</p>
</div>

<table style="width: 100%; margin-top: 20px;" class="no-border">
  <tr>
    <td style="width: 50%;"></td>
    <td style="width: 50%; text-align: center;">
      <p>Surakarta, {{ $tanggal_surat }} <br>
        Dokter Pemeriksa,
      </p>
      @if($ttd)
        @php
          $ttdPath = public_path($ttd);
        @endphp
        @if(file_exists($ttdPath))
          @php
            $ttdData = base64_encode(file_get_contents($ttdPath));
            $ttdMimeType = mime_content_type($ttdPath);
          @endphp
          <img src="data:{{ $ttdMimeType }};base64,{{ $ttdData }}" alt="Tanda Tangan" width="80" style="margin: 10px 0; max-height: 60px;">
        @else
          <div style="height: 60px; margin: 10px 0; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center; font-size: 10px;">TTD Not Found: {{ $ttd ?? 'No TTD provided' }}</div>
        @endif
      @else
        <div style="height: 60px; margin: 10px 0; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center; font-size: 10px;">No TTD in data</div>
      @endif
      <p style="margin-top: 10px; font-size: 12px;"><strong>{{ $nama_dokter }}</strong></p>
    </td>
  </tr>
</table>
</body>
</html>
