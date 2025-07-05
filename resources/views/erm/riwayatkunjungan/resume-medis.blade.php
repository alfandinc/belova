<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Ringkasan Pasien Pulang</title>
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
    <td style="width: 30%;" class="header-logo">
      <img src="{{ public_path('img/logo-premiere.png') }}" alt="Logo" width="180">
    </td>
    <td style="width: 70%; text-align: right;">
        <strong>KLINIK UTAMA PREMIERE BELOVA</strong> <br>
        Jl. Melon Raya No.27, Karangasem, Laweyan, Surakarta<br>
        Telp. 0821-1600-0093 <br>
        www.premierebelova.id <br>
    </td>
  </tr>
</table>

<hr>

<table class="identity-table no-border">
  <tr>   
    <td style="width: 12%; line-height: 0.8;"><strong>Nama</strong> </td> <td style="width: 48%; line-height: 0.8;" ><strong>:</strong> {{ $pasien->nama }}</td>
    <td style="width: 12%; line-height: 0.8;"><strong>No RM</strong> </td> <td style="width: 28%; line-height: 0.8;" ><strong>:</strong> {{ $pasien->id }}</td>
  </tr>
  <tr>   
    <td style="width: 12%; line-height: 0.8;"><strong>NIK</strong> </td> <td style="width: 48%%; line-height: 0.8;" ><strong>:</strong> {{ $pasien->nik }}</td>
    <td style="width: 12%; line-height: 0.8;"><strong>Tgl Lahir</strong> </td> <td style="width: 28%; line-height: 0.8;" ><strong>:</strong> {{ \Carbon\Carbon::parse($pasien->tanggal_lahir)->translatedFormat('d F Y') }}</td>
  </tr>
  <tr>   
    <td style="width: 12%; line-height: 0.8;"><strong>Alamat</strong> </td> <td style="width: 48%%; line-height: 0.8;" ><strong>:</strong> {{ $pasien->alamat }}</td>
    <td style="width: 12%; line-height: 0.8;"><strong>No Telp.</strong> </td> <td style="width: 28%; line-height: 0.8;" ><strong>:</strong> {{ $pasien->no_hp }}</td>
  </tr>
</table>

<hr>

<h3 class="center">RINGKASAN PASIEN PULANG (RAWAT JALAN)</h3>

<table>
  <tr>
    <td><strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($tanggal_visit)->translatedFormat('d F Y') }}</td>
    <td><strong>Klinik:</strong> Klinik Utama Premiere Belova</td>
  </tr>
</table>

<table>
  <tr>
    <td class="bold">Anamnesis :</td>
  </tr>
  <tr>
    <td>{{ $keluhan_utama }}</td>
  </tr>



  <tr>
    <td class="bold">Pemeriksaan Fisik :</td>
  </tr>
  <tr>
    <td>Keadaan Umum : {{ $keadaan_umum }}, TD : {{ $td }}mmHg, N : {{ $n }} x/Menit, S : {{ $s }}°C, R : {{ $r }} x/Menit</td>
  </tr>



  <tr>
    <td class="bold">Penunjang :</td>
  </tr>
  <tr>
    <td>-</td>
  </tr>



  <tr>
    <td class="bold">Diagnosis :</td>
  </tr>
  <tr>
    <td>
      {{ $diagnosis }}
    </td>
  </tr>



  <tr>
    <td class="bold">Terapi :</td>
  </tr>
  <tr>
    <td>
      {{ $nama_obat }}
    </td>
  </tr>



  <tr>
    <td class="bold">Instruksi Tindak Lanjut :</td>
  </tr>
  <tr>
    <td>{{ $tindak_lanjut }}</td>
  </tr>
</table>

<table style="width: 100%; margin-top: 20px;" class="no-border">
  <tr>
    <td style="width: 50%;"></td>
    <td style="width: 50%; text-align: center;">
      <p>Surakarta, {{ \Carbon\Carbon::parse($tanggal_visit)->translatedFormat('d F Y') }}</p>
      
      @if(isset($ttd_exists) && $ttd_exists && isset($ttd_image_data))
        <img src="data:image/png;base64,{{ $ttd_image_data }}" alt="Tanda Tangan" width="100">
      @elseif($ttd && file_exists(public_path($ttd)))
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path($ttd))) }}" alt="Tanda Tangan" width="100">
      @else
        <div style="height: 60px;"></div>
      @endif
      <p style="margin-top: 10px;"><strong>{{ $nama_dokter }}</strong></p>
    </td>
  </tr>
</table>

</body>


</html>
