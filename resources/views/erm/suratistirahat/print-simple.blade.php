<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Surat Keterangan Istirahat</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 14px;
      margin: 20px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
    }
    td, th {
      border: 1px solid #000;
      padding: 6px;
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
  </style>
</head>
<body>
<table class="no-border">  <tr>
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
    <td style="width: 80%; text-align: right; font-size: 12px;">
        <strong>KLINIK UTAMA PREMIERE BELOVA</strong> <br>
        Jl. Melon Raya No.27, Karangasem, Laweyan, Surakarta<br>
        Telp. 0821-1600-0093 <br>
        www.premierebelova.id <br>
    </td>
  </tr>
</table>

<hr>

<h3 class="center"> SURAT KETERANGAN ISTIRAHAT</h3>

<table class="no-border">
  <tr>
    <td style="width: 20%; font-weight: bold;">Nama</td>
    <td style="width: 80%;">: {{ $nama }}</td>
  </tr>
  <tr>
    <td style="font-weight: bold;">Umur</td>
    <td>: {{ $umur }}</td>
  </tr>
  <tr>
    <td style="font-weight: bold;">Pekerjaan</td>
    <td>: {{ $pekerjaan }}</td>
  </tr>
  <tr>
    <td style="font-weight: bold;">Alamat</td>
    <td>: {{ $alamat }}</td>
  </tr>
</table>

<p style="text-align: justify;">
    Diberikan istirahat sakit selama <strong>{{ $jumlah_hari }} hari</strong> terhitung
    mulai tanggal <strong>{{ \Carbon\Carbon::parse($tanggal_mulai)->translatedFormat('d F Y') }} s.d {{ \Carbon\Carbon::parse($tanggal_selesai)->translatedFormat('d F Y') }}</strong>.
</p>
<p style="text-align: justify;">
    Demikian surat keterangan ini diberikan untuk diketahui dan dipergunakan seperlunya.
</p>

<table style="width: 100%; margin-top: 20px;" class="no-border">
  <tr>
    <td style="width: 50%;"></td>    <td style="width: 50%; text-align: center;">
      <p>Surakarta, {{ $tanggal_surat }} <br>
        Dokter Pemeriksa,
      </p>      @if($ttd)
        @php
          $ttdPath = public_path($ttd);
        @endphp
        <!-- Debug: TTD variable = {{ $ttd }} -->
        <!-- Debug: TTD full path = {{ $ttdPath }} -->
        <!-- Debug: TTD exists = {{ file_exists($ttdPath) ? 'Yes' : 'No' }} -->
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
