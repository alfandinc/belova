<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Surat Keterangan Istirahat</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 14px;
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
    .header-logo {
      width: 120px;
    }
    .bold {
      font-weight: bold;
    }
    .center {
      text-align: center;
    }
    .identity-table {
      width: 100%;
      margin-top: 10px;
    }
    .identity-table td {
      padding: 6px;
      vertical-align: top;
    }
    .identity-table .label {
      text-align: right;
      width: 30%;
      padding-right: 10px;
    }
    .identity-table .value {
      text-align: left;
      width: 70%;
    }
  </style>
</head>
<body>
<table class="no-border">
  <tr>
    <td style="width: 20%;" class="header-logo">
      <img src="{{ public_path('img/logo-premiere.png') }}" alt="Logo" width="130">
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
    <td style="width: 50%;"></td>
    <td style="width: 50%; text-align: center; ">
      <p>Surakarta, {{ $tanggal_surat }} <br>
        Dokter Pemeriksa,
    </p>
      <img src="{{ public_path($ttd) }}" alt="QR Code" width="80">
      <p style="margin-top: 10px; font-size: 12px;"><strong>{{ $nama_dokter }}</strong></p>
    </td>
  </tr>
</table>
</body>