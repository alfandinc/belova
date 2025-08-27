<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Medical Diagnosis Certificate</title>
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
        <strong>PREMIERE BELOVA MAIN CLINIC</strong> <br>
        Jl. Melon Raya No.27, Karangasem, Laweyan, Surakarta<br>
        Telp. 0821-1600-0093 <br>
        www.premierebelova.id <br>
    </td>
  </tr>
</table>

<hr>

<h3 class="center">MEDICAL DIAGNOSIS CERTIFICATE</h3>

<p>The undersigned, physician of Klinik Utama Premiere Belova, hereby certifies that:</p>

<table class="identity-table no-border">
  <tr>   
    <td style="width: 30%; line-height: 1.2;"><strong>Medical Record Number</strong></td> 
    <td style="width: 70%; line-height: 1.2;"><strong>:</strong> {{ $pasien->id }}</td>
  </tr>
  <tr>   
    <td style="width: 30%; line-height: 1.2;"><strong>Name</strong></td> 
    <td style="width: 70%; line-height: 1.2;"><strong>:</strong> {{ $pasien->nama }}</td>
  </tr>
  <tr>   
    <td style="width: 30%; line-height: 1.2;"><strong>Gender</strong></td> 
    <td style="width: 70%; line-height: 1.2;"><strong>:</strong> {{ $pasien->jenis_kelamin == 'L' ? 'Male' : 'Female' }}</td>
  </tr>
  <tr>   
    <td style="width: 30%; line-height: 1.2;"><strong>Age</strong></td> 
    <td style="width: 70%; line-height: 1.2;"><strong>:</strong> {{ $umur }} years</td>
  </tr>
  <tr>   
    <td style="width: 30%; line-height: 1.2;"><strong>Address</strong></td> 
    <td style="width: 70%; line-height: 1.2;"><strong>:</strong> {{ $pasien->alamat }}</td>
  </tr>
</table>

<p>Has undergone medical examination on:</p>

<table class="identity-table no-border">
  <tr>   
    <td style="width: 30%; line-height: 1.2;"><strong>Date</strong></td> 
    <td style="width: 70%; line-height: 1.2;"><strong>:</strong> {{ \Carbon\Carbon::parse($tanggal_visit)->translatedFormat('F d, Y') }}</td>
  </tr>
  <tr>
    <td style="width: 30%; line-height: 1.2;"><strong>With the following diagnoses</strong></td>
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
    <td style="width: 30%; line-height: 1.2;"><strong>Remarks</strong></td>
    <td style="width: 70%; line-height: 1.2;"><strong>:</strong> {{ $keterangan }}</td>
  </tr>
</table>

<p style="margin-top: 20px;">This certificate is hereby issued for reference as necessary.</p>

<table style="width: 100%; margin-top: 20px;" class="no-border">
  <tr>
    <td style="width: 50%;"></td>
    <td style="width: 50%; text-align: center;">
      <p>Surakarta, {{ \Carbon\Carbon::parse($tanggal_visit)->translatedFormat('F d, Y') }}</p>
      
      @if(!empty($ttd_image_path))
        <img src="{{ $ttd_image_path }}" alt="Signature" width="100">
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
