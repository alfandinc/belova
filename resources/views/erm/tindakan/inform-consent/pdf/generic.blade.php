<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Inform Consent Pasien</title>
    <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 12px;
      color: #222;
      line-height: 1.45;
    }
    .header-table {
      width: 100%;
      margin-bottom: 8px;
    }
    .header-title {
      font-size: 18px;
      font-weight: bold;
      letter-spacing: 0.6px;
      text-align: left;
      line-height: 1.2;
      padding-top: 6px;
      padding-bottom: 6px;
      text-transform: uppercase;
    }
    .header-subtitle {
      display: block;
      font-size: 13px;
      font-weight: bold;
      letter-spacing: 0;
      text-transform: none;
      margin-top: 4px;
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
      border-top: 1.5px solid #8f8f8f;
      margin: 8px 0 14px 0;
    }
    .identity-wrapper {
      width: 100%;
      margin-bottom: 18px;
    }
    .identity-heading {
      padding: 0 0 7px 0;
      font-size: 11px;
      font-weight: bold;
      letter-spacing: 0.8px;
      text-transform: uppercase;
      border-bottom: 1px solid #cfcfcf;
      margin-bottom: 10px;
    }
    .section-heading {
      font-size: 11px;
      font-weight: bold;
      letter-spacing: 0.8px;
      text-transform: uppercase;
      border-bottom: 1px solid #cfcfcf;
      padding-bottom: 6px;
      margin-bottom: 10px;
    }
    .identity-table {
      border-collapse: collapse;
      font-size: 11px;
    }
    .identity-table td {
      padding: 3px 0;
      vertical-align: top;
    }
    .identity-split-table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }
    .identity-split-table td {
      width: 50%;
      vertical-align: top;
    }
    .identity-split-left {
      padding-right: 10px;
    }
    .identity-split-right {
      padding-left: 10px;
    }
    .identity-label-cell {
      font-weight: bold;
      white-space: nowrap;
      vertical-align: top;
      text-align: left;
      padding-right: 6px;
    }
    .identity-separator-cell {
      text-align: center;
      font-weight: bold;
      vertical-align: top;
      padding: 3px 4px;
    }
    .identity-value-cell {
      word-break: break-word;
      vertical-align: top;
      padding-left: 6px;
    }
    .section {
      margin-bottom: 18px;
      font-size: 12px;
    }
    .section p,
    .section li {
      font-size: 12px;
      text-align: justify;
    }
    .section ol,
    .section ul {
      margin-top: 6px;
    }
    .sign-wrapper {
      margin-top: 20px;
    }
    .sign-date {
      text-align: right;
      font-size: 12px;
      margin-bottom: 14px;
    }
    .signature-table {
      width: 100%;
    }
    .signature-table td {
      text-align: center;
      vertical-align: top;
      border: none;
      padding: 0 10px 18px 10px;
    }
    .signature-container {
      margin: 14px 0 6px 0;
      min-height: 70px;
    }
    .signature-container img {
      max-height: 100px;
    }
    .sign-label {
      font-weight: bold;
      margin-bottom: 4px;
      display: block;
      font-size: 12px;
    }
    .sign-name {
      display: inline-block;
      min-width: 150px;
      border-top: 1px solid #8f8f8f;
      padding-top: 4px;
      font-size: 12px;
    }
    </style>
</head>
<body>
{{-- Header Table: Title left, Logo right --}}
<table class="header-table">
  <tr>
    <td class="header-title">
      INFORMED CONSENT
      <span class="header-subtitle">{{ $tindakan->nama ?? '' }}</span>
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

<div class="identity-wrapper">
  <div class="identity-heading">Identitas Pasien</div>
  <table class="identity-split-table">
    <tr>
      <td class="identity-split-left">
        <table class="identity-table">
          <tr>
            <td class="identity-label-cell">Nama</td>
            <td class="identity-separator-cell">:</td>
            <td class="identity-value-cell">&nbsp;&nbsp;{{ $pasien->nama }}</td>
          </tr>
          <tr>
            <td class="identity-label-cell">{{ $pasien->identity_label ?? 'Identitas' }}</td>
            <td class="identity-separator-cell">:</td>
            <td class="identity-value-cell">&nbsp;&nbsp;{{ $pasien->identity_number ?? $pasien->nik ?? '-' }}</td>
          </tr>
          <tr>
            <td class="identity-label-cell">Alamat</td>
            <td class="identity-separator-cell">:</td>
            <td class="identity-value-cell">&nbsp;&nbsp;{{ $pasien->alamat }}</td>
          </tr>
        </table>
      </td>
      <td class="identity-split-right">
        <table class="identity-table">
          <tr>
            <td class="identity-label-cell">No. RM</td>
            <td class="identity-separator-cell">:</td>
            <td class="identity-value-cell">&nbsp;&nbsp;{{ $pasien->id }}</td>
          </tr>
          <tr>
            <td class="identity-label-cell">Tgl. Lahir</td>
            <td class="identity-separator-cell">:</td>
            <td class="identity-value-cell">&nbsp;&nbsp;{{ \Carbon\Carbon::parse($pasien->tanggal_lahir)->translatedFormat('d F Y') }}</td>
          </tr>
          <tr>
            <td class="identity-label-cell">No. Telp.</td>
            <td class="identity-separator-cell">:</td>
            <td class="identity-value-cell">&nbsp;&nbsp;{{ $pasien->no_hp }}</td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</div>

<div class="section">
  <div class="section-heading">Pernyataan Persetujuan</div>
  {!! $content !!}
</div>

<div class="sign-wrapper">
  <div class="section-heading">Tanda Tangan</div>
  <div class="sign-date">{{ \Carbon\Carbon::parse($visitation->tanggal_visitation)->locale('id')->translatedFormat('j F Y') }}</div>
  <table class="signature-table">
    <tr>
      <td style="width: 50%;">
        <span class="sign-label">Pasien/Wali</span>
        <div class="signature-container">
          @if(!empty($data['signature']))
            <img src="{{ $data['signature'] }}" alt="Tanda tangan pasien">
          @endif
        </div>
        <span class="sign-name">{{ $data['nama_pasien'] ?? '' }}</span>
      </td>
      <td style="width: 50%;">
        <span class="sign-label">Saksi</span>
        <div class="signature-container">
          @if(!empty($data['witness_signature']))
            <img src="{{ $data['witness_signature'] }}" alt="Tanda tangan saksi">
          @endif
        </div>
        <span class="sign-name">{{ $data['nama_saksi'] ?? '' }}</span>
      </td>
    </tr>
    <tr>
      <td style="width:50%;">
        <span class="sign-label">Dokter</span>
        <div class="signature-container">
          @if(!empty($dokter_qr))
            <img src="{{ $dokter_qr }}" alt="QR Dokter">
          @endif
        </div>
        <span class="sign-name">{{ $dokter_name ?? '' }}</span>
      </td>
      <td style="width:50%;">
        <span class="sign-label">Perawat</span>
        <div class="signature-container">
          @if(!empty($perawat_qr))
            <img src="{{ $perawat_qr }}" alt="QR Perawat">
          @endif
        </div>
        <span class="sign-name">{{ $perawat_name ?? '' }}</span>
      </td>
    </tr>
  </table>
</div>
</body>
</html>
