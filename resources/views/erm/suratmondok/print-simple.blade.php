<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Surat Keterangan Mondok</title>
  {{-- Shared look of the A4 surat (istirahat / mondok) --}}
  @include('erm.suratistirahat.partials.surat-style')
</head>
<body>
@include('erm.riwayatkunjungan.partials.kop')

<div class="surat-title">SURAT PENGANTAR RAWAT INAP</div>

<p>
  Kepada Yth.<br>
  <strong>{{ $tujuan_igd }}</strong><br>
  di tempat
</p>
<p>Dengan hormat,<br>Bersama ini kami mohon diberikan perawatan rawat inap untuk pasien:</p>

<table class="data-table">
  <tr><td class="lbl">Nama</td><td class="sep">:</td><td>{{ mb_strtoupper($nama) }}</td></tr>
  <tr><td class="lbl">Umur</td><td class="sep">:</td><td>{{ $umur }}</td></tr>
  <tr><td class="lbl">Alamat</td><td class="sep">:</td><td>{{ $alamat }}</td></tr>
  <tr><td class="lbl">Diagnosa</td><td class="sep">:</td><td>{!! nl2br(e($diagnosa)) !!}</td></tr>
</table>

<p style="margin-bottom: 2px;"><strong>Instruksi Terapi:</strong></p>
<p class="justify">{!! nl2br(e($instruksi_terapi)) !!}</p>

<p>Atas perhatian dan kerja samanya kami ucapkan terima kasih.</p>

@include('erm.suratistirahat.partials.surat-ttd')
</body>
</html>
