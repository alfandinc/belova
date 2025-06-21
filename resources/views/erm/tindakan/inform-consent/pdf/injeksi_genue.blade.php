<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Inform Consent Injeksi Genue</title>
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

<h3 class="center">INFORMED CONSENT INJEKSI GENUE</h3>

<table>
  <tr>
    <th width="5%">No.</th>
    <th width="30%">JENIS INFORMASI</th>
    <th>ISI INFORMASI</th>
  </tr>
  <tr>
    <td>1</td>
    <td>Diagnosis (diagnosis kerja & diagnosis banding)</td>
    <td>Abses genue dengan OA</td>
  </tr>
  <tr>
    <td>2</td>
    <td>Dasar diagnosis</td>
    <td>
      1. Anamnesis<br>
      2. Pemeriksaan fisik<br>
      3. Pemeriksaan penunjang
    </td>
  </tr>
  <tr>
    <td>3</td>
    <td>Tindakan Kedokteran</td>
    <td>Pungsi evakuasi dan injeksi intraartikuler</td>
  </tr>
  <tr>
    <td>4</td>
    <td>Indikasi Tindakan</td>
    <td>
      1. Nyeri sendi genue berlebihan<br>
      2. Kambuh berkepanjangan
    </td>
  </tr>
  <tr>
    <td>5</td>
    <td>Tata Cara</td>
    <td>
      1. Anestesi Dengan LA<br>
      2. Pungsi evakuasi cairan Abses<br>
      3. Injeksi kortikosteroid dan AHA
    </td>
  </tr>
  <tr>
    <td>6</td>
    <td>Tujuan</td>
    <td>
      1. Mengurangi / menghilangkan nyeri<br>
      2. Menekan peradangan<br>
      3. Memperbaiki gerakan / cairan sendi
    </td>
  </tr>
  <tr>
    <td>7</td>
    <td>Risiko</td>
    <td>Nyeri</td>
  </tr>
  <tr>
    <td>8</td>
    <td>Komplikasi</td>
    <td>
      1. Peradangan<br>
      2. Infeksi
    </td>
  </tr>
  <tr>
    <td>9</td>
    <td>Prognosis</td>
    <td>Dubia</td>
  </tr>
  <tr>
    <td>10</td>
    <td>Alternatif</td>
    <td>Fisioterapi</td>
  </tr>
  <tr>
    <td>11</td>
    <td>Lain - lain</td>
    <td>-</td>
  </tr>
</table>

<table class="no-border">
  <tr>
    <td colspan="2">
      <p>Saya yang bertanda tangan dibawah ini menyetujui untuk dilakukan tindakan Injeksi Genue dengan ketentuan:</p>
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