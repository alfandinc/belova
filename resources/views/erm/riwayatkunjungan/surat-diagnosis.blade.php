<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Surat Keterangan Diagnosis</title>
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

<h3 class="center">SURAT KETERANGAN DIAGNOSIS</h3>

<p>Yang bertanda tangan dibawah ini dokter Klinik Utama Premiere Belova:</p>

<table class="identity-table no-border">
  <tr>   
    <td style="width: 30%; line-height: 1.2;"><strong>No RM</strong></td> 
    <td style="width: 70%; line-height: 1.2;"><strong>:</strong> {{ $pasien->id }}</td>
  </tr>
  <tr>   
    <td style="width: 30%; line-height: 1.2;"><strong>Nama</strong></td> 
    <td style="width: 70%; line-height: 1.2;"><strong>:</strong> {{ $pasien->nama }}</td>
  </tr>
  <tr>   
    <td style="width: 30%; line-height: 1.2;"><strong>Jenis Kelamin</strong></td> 
    <td style="width: 70%; line-height: 1.2;"><strong>:</strong> {{ $pasien->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
  </tr>
  <tr>   
    <td style="width: 30%; line-height: 1.2;"><strong>Umur</strong></td> 
    <td style="width: 70%; line-height: 1.2;"><strong>:</strong> {{ $umur }} tahun</td>
  </tr>
  <tr>   
    <td style="width: 30%; line-height: 1.2;"><strong>Alamat</strong></td> 
    <td style="width: 70%; line-height: 1.2;"><strong>:</strong> {{ $pasien->alamat }}</td>
  </tr>
</table>

<p>Telah dilakukan pemeriksaan pada:</p>

<table class="identity-table no-border">
  <tr>   
    <td style="width: 30%; line-height: 1.2;"><strong>Tanggal</strong></td> 
    <td style="width: 70%; line-height: 1.2;"><strong>:</strong> {{ \Carbon\Carbon::parse($tanggal_visit)->translatedFormat('d F Y') }}</td>
  </tr>
  <tr>
    <td style="width: 30%; line-height: 1.2;"><strong>Dengan diagnosis</strong></td>
    <td style="width: 70%; line-height: 1.2;">
      <strong>:</strong>
    </td>
  </tr>
  @foreach($diagnosis_list as $index => $diagnosis)
  <tr>
    <td style="width: 30%; line-height: 1.2;"></td>
    <td style="width: 70%; line-height: 1.2;">
      {{ $index + 1 }}. {{ $diagnosis }}
    </td>
  </tr>
  @endforeach
  <tr>
    <td style="width: 30%; line-height: 1.2;"><strong>Keterangan</strong></td>
    <td style="width: 70%; line-height: 1.2;"><strong>:</strong> {{ $keterangan }}</td>
  </tr>
</table>

<p style="margin-top: 20px;">Demikian harap dijadikan periksa.</p>

<table style="width: 100%; margin-top: 20px;" class="no-border">
  <tr>
    <td style="width: 50%;"></td>
    <td style="width: 50%; text-align: center;">
      <p>Surakarta, {{ \Carbon\Carbon::parse($tanggal_visit)->translatedFormat('d F Y') }}</p>
      
      @if(!empty($ttd_image_path))
        <img src="{{ $ttd_image_path }}" alt="Tanda Tangan" width="100">
      @else
        <div style="height: 60px;"></div>
      @endif
      <p style="margin-top: 10px;">
        <strong>{{ $nama_dokter }}</strong><br>
        {{ $spesialisasi }}
      </p>
    </td>
  </tr>
</table>

</body>
</html>
