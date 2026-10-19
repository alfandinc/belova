<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Inform Consent Pasien</title>
    <style>
    @page { margin: 32px 40px 36px 40px; }
    body {
      font-family: Arial, sans-serif;
      font-size: 11.5px;
      color: #222;
      line-height: 1.45;
    }
    /* Header: title left, clinic logo right */
    .header-table { width: 100%; border-collapse: collapse; }
    .header-table td { vertical-align: middle; padding: 0; }
    .header-title {
      font-size: 18px;
      font-weight: bold;
      letter-spacing: 0.6px;
      line-height: 1.2;
    }
    .header-subtitle {
      display: block;
      font-size: 12.5px;
      font-weight: bold;
      letter-spacing: 0;
      margin-top: 4px;
      color: #444;
    }
    .header-logo-cell { text-align: right; width: 240px; }
    .header-logo { max-width: 210px; max-height: 70px; }
    .logo-divider { border: none; border-top: 1.5px solid #8f8f8f; margin: 10px 0 14px 0; }

    .section { margin-bottom: 14px; }
    .section-heading {
      font-size: 10.5px;
      font-weight: bold;
      letter-spacing: 0.8px;
      text-transform: uppercase;
      border-bottom: 1px solid #cfcfcf;
      padding-bottom: 5px;
      margin-bottom: 8px;
    }

    /* Patient identity: two label / value pairs per row, fixed columns so long values wrap in their own column */
    .identity-table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 11px; }
    .identity-table td { padding: 2px 0; vertical-align: top; }
    .identity-table .lbl { width: 13%; font-weight: bold; }
    .identity-table .sep { width: 2%; font-weight: bold; }
    .identity-table .val { width: 35%; padding-right: 12px; word-wrap: break-word; }

    /* Consent text (from master Tindakan / Kategori or the generic text; only formatting tags are kept) */
    .consent p { margin: 0 0 7px 0; text-align: justify; }
    .consent li { margin-bottom: 2px; text-align: justify; }
    .consent ol, .consent ul { margin: 3px 0 8px 0; padding-left: 22px; }
    .consent h4, .consent h5, .consent h6 { font-size: 12px; margin: 10px 0 4px 0; }
    .consent blockquote { margin: 4px 0 8px 14px; }
    .consent table { width: 100%; border-collapse: collapse; margin: 4px 0 10px; }
    .consent th, .consent td { border: 1px solid #999; padding: 4px 6px; vertical-align: top; font-size: 10.5px; }
    .consent th { background: #f2f2f2; }

    .notes { white-space: pre-line; margin: 0; }

    /* Signatures: patient & witness above, doctor & nurse below; kept together (and with its heading) on one page */
    .sign-wrapper { page-break-inside: avoid; margin-top: 4px; }
    .sign-date { text-align: right; margin-bottom: 6px; }
    .signature-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .signature-table td { width: 50%; text-align: center; vertical-align: top; padding: 0 30px 12px 30px; }
    .sign-label { font-weight: bold; font-size: 11px; }
    .signature-container { height: 84px; margin: 4px 0; text-align: center; }
    .signature-container img { max-height: 82px; max-width: 100%; }
    .signature-container img.qr { height: 82px; width: 82px; }
    .sign-name {
      border-top: 1px solid #8f8f8f;
      padding-top: 3px;
      margin: 0 20px;
      font-size: 10.5px;
      word-wrap: break-word;
      min-height: 14px;
    }
    </style>
</head>
<body>
<table class="header-table">
  <tr>
    <td class="header-title">
      INFORMED CONSENT
      <span class="header-subtitle">{{ $tindakan->nama ?? '' }}</span>
    </td>
    <td class="header-logo-cell">
      {{-- As a small JPEG made once (App\Support\PdfLetterhead): DomPDF needs ~1 s for these PNGs with transparency --}}
      @if(isset($klinik_id) && $klinik_id == 1)
        <img src="{{ \App\Support\PdfLetterhead::logo('img/header-premiere.png', 800) }}" alt="Logo Premiere" class="header-logo">
      @elseif(isset($klinik_id) && $klinik_id == 2)
        <img src="{{ \App\Support\PdfLetterhead::logo('img/header-belova.png', 800) }}" alt="Logo Belova" class="header-logo">
      @endif
    </td>
  </tr>
</table>
<hr class="logo-divider">

<div class="section">
  <div class="section-heading">Identitas Pasien</div>
  <table class="identity-table">
    <tr>
      <td class="lbl">Nama</td><td class="sep">:</td><td class="val">{{ $pasien->nama }}</td>
      <td class="lbl">No. RM</td><td class="sep">:</td><td class="val">{{ $pasien->id }}</td>
    </tr>
    <tr>
      <td class="lbl">{{ $pasien->identity_label ?? 'Identitas' }}</td><td class="sep">:</td><td class="val">{{ $pasien->identity_number ?? $pasien->nik ?? '-' }}</td>
      <td class="lbl">Tgl. Lahir</td><td class="sep">:</td><td class="val">{{ $pasien->tanggal_lahir ? \Carbon\Carbon::parse($pasien->tanggal_lahir)->locale('id')->translatedFormat('j F Y') : '-' }}</td>
    </tr>
    <tr>
      <td class="lbl">Alamat</td><td class="sep">:</td><td class="val">{{ $pasien->alamat ?: '-' }}</td>
      <td class="lbl">No. Telp.</td><td class="sep">:</td><td class="val">{{ $pasien->no_hp ?: '-' }}</td>
    </tr>
  </table>
</div>

<div class="section consent">
  <div class="section-heading">Pernyataan Persetujuan</div>
  {!! $content !!}
</div>

@if(trim((string) ($data['notes'] ?? '')) !== '')
<div class="section">
  <div class="section-heading">Catatan Tambahan</div>
  <p class="notes">{{ trim($data['notes']) }}</p>
</div>
@endif

<div class="sign-wrapper">
  <div class="section-heading">Tanda Tangan</div>
  <div class="sign-date">{{ \Carbon\Carbon::parse($visitation->tanggal_visitation)->locale('id')->translatedFormat('j F Y') }}</div>
  <table class="signature-table">
    <tr>
      <td>
        <div class="sign-label">Pasien/Wali</div>
        <div class="signature-container">@if(!empty($data['signature']))<img src="{{ $data['signature'] }}" alt="Tanda tangan pasien">@endif</div>
        <div class="sign-name">{{ $data['nama_pasien'] ?? '' }}</div>
      </td>
      <td>
        <div class="sign-label">Saksi</div>
        <div class="signature-container">@if(!empty($data['witness_signature']))<img src="{{ $data['witness_signature'] }}" alt="Tanda tangan saksi">@endif</div>
        <div class="sign-name">{{ $data['nama_saksi'] ?? '' }}</div>
      </td>
    </tr>
    <tr>
      <td>
        <div class="sign-label">Dokter</div>
        <div class="signature-container">@if(!empty($dokter_qr))<img src="{{ $dokter_qr }}" alt="QR Dokter" class="qr">@endif</div>
        <div class="sign-name">{{ $dokter_name ?? '' }}</div>
      </td>
      <td>
        <div class="sign-label">Perawat</div>
        <div class="signature-container">@if(!empty($perawat_qr))<img src="{{ $perawat_qr }}" alt="QR Perawat" class="qr">@endif</div>
        <div class="sign-name">{{ $perawat_name ?? '' }}</div>
      </td>
    </tr>
  </table>
</div>
</body>
</html>
