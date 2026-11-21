<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Contoh Kop - {{ $judul }}</title>
<style>
  body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #222; }
  table { width: 100%; border-collapse: collapse; }
  table.no-border td { border: none; vertical-align: top; }
  h3 { text-align: center; margin: 14px 0 4px; font-size: 14px; text-decoration: underline; }
  .nomor { text-align: center; margin-bottom: 14px; }
  .dummy { color: #555; line-height: 1.6; }
  .note { margin-top: 18px; padding: 8px 10px; border: 1px dashed #999; color: #555; font-size: 10px; }
  .section { margin-top: 22px; }
  .section-title { font-weight: bold; font-size: 11px; color: #1761fd; margin-bottom: 6px; }
  .box { border: 1px solid #ddd; padding: 8px; }
</style>
</head>
<body>
@include('erm.riwayatkunjungan.partials.kop')

<h3>CONTOH SURAT</h3>
<div class="nomor">Nomor: 001/CONTOH/{{ date('m/Y') }}</div>

<div class="dummy">
  <p>Yang bertanda tangan di bawah ini menerangkan bahwa:</p>
  <table class="no-border" style="width: 70%; margin-left: 20px;">
    <tr><td style="width: 35%;">Nama</td><td>: Pasien Contoh</td></tr>
    <tr><td>Tanggal lahir</td><td>: 1 Januari 1990</td></tr>
    <tr><td>Alamat</td><td>: Jl. Contoh No. 1, Surakarta</td></tr>
  </table>
  <p>Ini adalah halaman contoh untuk memeriksa posisi dan ukuran kop. Isi surat asli akan berada di area ini,
     di bawah kop atas dan di atas kop bawah. Pastikan tidak ada teks yang tertutup atau terpotong.</p>
  <p>Surakarta, {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('j F Y') }}</p>
</div>

@if($lingkup === 'klinik')
  <div class="section">
    <div class="section-title">Logo (invoice, nota kasir, inform consent, retur pembelian)</div>
    <div class="box">
      @if(!empty($kop['logo']))
        <img src="{{ $kop['logo'] }}" alt="Logo" style="height: 60px;">
      @else
        <span style="color:#999;">Belum ada logo.</span>
      @endif
    </div>
  </div>
  <div class="section">
    <div class="section-title">Kop kecil (resep farmasi & edukasi obat)</div>
    <div class="box">@include('erm.eresep.farmasi.partials.kop-kecil')</div>
  </div>
@elseif($lingkup === 'perusahaan')
  <div class="section">
    <div class="section-title">Logo (pengajuan dana, slip gaji dokter)</div>
    <div class="box">
      @if(!empty($kop['logo']))
        <img src="{{ $kop['logo'] }}" alt="Logo" style="height: 60px;">
      @endif
    </div>
  </div>
@endif

<div class="note">Contoh ini memakai kop yang sedang tersimpan. Gambar yang belum di-upload memakai gambar bawaan.</div>
</body>
</html>
