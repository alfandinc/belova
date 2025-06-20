<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Inform Consent Pasien</title>
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
{{-- <table class="no-border">
  <tr>
    @if($visitation->klinik_id == 1)
      <td style="width: 30%;" class="header-logo">
        <img src="{{ public_path('img/logo-premiere.png') }}" alt="Logo" width="180">
      </td>
      <td style="width: 70%; text-align: right;">
          <strong>KLINIK UTAMA PREMIERE BELOVA</strong> <br>
          Jl. Melon Raya No.27, Karangasem, Laweyan, Surakarta<br>
          Telp. 0821-1600-0093 <br>
          www.premierebelova.id <br>
      </td>
    @elseif($visitation->klinik_id == 2)
      <td style="width: 30%;" class="header-logo">
        <img src="{{ public_path('img/logo-belovaskin.png') }}" alt="Logo" width="180">
      </td>
      <td style="width: 70%; text-align: right;">
          <strong>KLINIK PRATAMA BELOVA SKIN & BEAUTY CENTER</strong> <br>
          Jl. Melon Raya No.29, Karangasem, Laweyan, Surakarta<br>
          Telp. 0851-0099-0139 <br>
          www.belovaskin.id <br>
      </td>
    @endif
  </tr>
</table>

<hr> --}}

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

<h3 class="center">INFORMED CONSENT ADVANCE REJURAN HB</h3>
<table class="no-border">
  <tr>
    <td colspan="2">
      <p>Saya yang bertanda tangan dibawah ini menyetujui untuk dilakukan tindakan Advance Rejuran HB dengan ketentuan:</p>
      <ol>
        <li>Saya telah mendapat penjelasan mengenai prosedur tindakan</li>
        <li>Saya mengerti tentang manfaat dan risiko yang mungkin timbul</li>
        <li>Saya setuju untuk dilakukan tindakan yang diperlukan</li>
      </ol>
    </td>
  </tr>
  @if(isset($data['notes']) && !empty($data['notes']))
  <tr>
    <td colspan="2">
      <p><strong>Catatan Tambahan:</strong></p>
      <p>{{ $data['notes'] }}</p>
    </td>
  </tr>
  @endif
</table>

<table class="no-border" style="margin-top: 20px;">
  <tr>
    <td style="width: 50%; text-align: center; vertical-align: top;">
      <p><strong>Pasien/Wali</strong></p>
      <div class="signature-container" style="margin: 20px 0;">
        <img src="{{ $data['signature'] }}" alt="Tanda tangan pasien" style="max-height: 100px;">
      </div>
      <p style="margin-top: 10px;">({{ $data['nama_pasien'] }})</p>
    </td>
    <td style="width: 50%; text-align: center; vertical-align: top;">
      <p><strong>Saksi</strong></p>
      <div class="signature-container" style="margin: 20px 0;">
        <img src="{{ $data['witness_signature'] }}" alt="Tanda tangan saksi" style="max-height: 100px;">
      </div>
      <p style="margin-top: 10px;">({{ $data['nama_saksi'] }})</p>
    </td>
  </tr>
</table>
</body>
</html>
