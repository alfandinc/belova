<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Inform Consent Pasien</title>
    <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 14px;
      color: #222;
    }
    .header-table {
      width: 100%;
      margin-bottom: 10px;
    }
    .header-title {
      font-size: 22px;
      font-weight: bold;
      letter-spacing: 1px;
      text-align: left;
      line-height: 1.2;
      padding-top: 10px;
      padding-bottom: 10px;
    }
    .header-logo-cell {
      text-align: right;
      vertical-align: top;
      width: 260px;
    }
    .header-logo {
      max-width: 220px;
      max-height: 80px;
      margin-top: 0;
      display: inline-block;
    }
    .logo-divider {
      border: none;
      border-top: 2px solid #bdbdbd;
      margin: 10px 0 18px 0;
    }
    .identity-table {
      width: 100%;
      margin-top: 10px;
      background: #f8f8f8;
      border-radius: 8px;
      border: 1px solid #e0e0e0;
      margin-bottom: 18px;
    }
    .identity-table td {
      padding: 7px 8px;
      vertical-align: top;
      border: none;
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
    .section {
      margin-bottom: 18px;
    }
    .signature-table {
      width: 100%;
      margin-top: 30px;
    }
    .signature-table td {
      text-align: center;
      vertical-align: top;
      border: none;
      padding: 0 10px;
    }
    .signature-container {
      margin: 20px 0 10px 0;
      min-height: 60px;
    }
    .signature-container img {
      max-height: 100px;
    }
    .sign-label {
      font-weight: bold;
      margin-bottom: 8px;
      display: block;
    }
    </style>
</head>
<body>
{{-- Header Table: Title left, Logo right --}}
<table class="header-table">
  <tr>
    <td class="header-title">
      INFORMED CONSENT<br>FACIAL LIGHT
    </td>
    <td class="header-logo-cell">
      @if(isset($klinik_id) && $klinik_id == 1)
        <img src="{{ public_path('img/header-premiere.png') }}" alt="Logo Premiere" class="header-logo">
      @elseif(isset($klinik_id) && $klinik_id == 2)
        <img src="{{ public_path('img/header-belova.png') }}" alt="Logo Belova" class="header-logo">
      @endif
    </td>
  </tr>
</table>
<hr class="logo-divider">

<table class="identity-table">
  <tr>
    <td style="width: 12%;"><strong>Nama</strong></td>
    <td style="width: 48%;"><strong>:</strong> {{ $pasien->nama }}</td>
    <td style="width: 12%;"><strong>No RM</strong></td>
    <td style="width: 28%;"><strong>:</strong> {{ $pasien->id }}</td>
  </tr>
  <tr>
    <td><strong>NIK</strong></td>
    <td><strong>:</strong> {{ $pasien->nik }}</td>
    <td><strong>Tgl Lahir</strong></td>
    <td><strong>:</strong> {{ \Carbon\Carbon::parse($pasien->tanggal_lahir)->translatedFormat('d F Y') }}</td>
  </tr>
  <tr>
    <td><strong>Alamat</strong></td>
    <td><strong>:</strong> {{ $pasien->alamat }}</td>
    <td><strong>No Telp.</strong></td>
    <td><strong>:</strong> {{ $pasien->no_hp }}</td>
  </tr>
</table>

<div class="section">
  <p>Saya yang bertanda tangan dibawah ini menyetujui untuk dilakukan tindakan Facial Light dengan ketentuan:</p>
  <ol style="margin-left: 18px;">
    <li>Saya telah mendapat penjelasan mengenai prosedur tindakan</li>
    <li>Saya mengerti tentang manfaat dan risiko yang mungkin timbul</li>
    <li>Saya setuju untuk dilakukan tindakan yang diperlukan</li>
  </ol>
  @if(isset($data['notes']) && !empty($data['notes']))
    <div style="margin-top: 10px;">
      <strong>Catatan Tambahan:</strong>
      <div>{{ $data['notes'] }}</div>
    </div>
  @endif
</div>

<table class="signature-table">
  <tr>
    <td style="width: 50%;">
      <span class="sign-label">Pasien/Wali</span>
      <div class="signature-container">
        @if(!empty($data['signature']))
          <img src="{{ $data['signature'] }}" alt="Tanda tangan pasien">
        @endif
      </div>
      <div style="margin-top: 10px;">({{ $data['nama_pasien'] }})</div>
    </td>
    <td style="width: 50%;">
      <span class="sign-label">Saksi</span>
      <div class="signature-container">
        @if(!empty($data['witness_signature']))
          <img src="{{ $data['witness_signature'] }}" alt="Tanda tangan saksi">
        @endif
      </div>
      <div style="margin-top: 10px;">({{ $data['nama_saksi'] }})</div>
    </td>
  </tr>
</table>
</body>
</html>
