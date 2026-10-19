<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Surat Keterangan Istirahat</title>
  {{-- Shared look of the A4 surat (istirahat / mondok) --}}
  @include('erm.suratistirahat.partials.surat-style')
</head>
<body>
@include('erm.riwayatkunjungan.partials.kop')

<div class="surat-title">SURAT KETERANGAN ISTIRAHAT</div>

<p>Yang bertanda tangan di bawah ini menerangkan bahwa:</p>

<table class="data-table">
  <tr><td class="lbl">Nama</td><td class="sep">:</td><td>{{ $nama }}</td></tr>
  <tr><td class="lbl">Umur</td><td class="sep">:</td><td>{{ $umur }}</td></tr>
  <tr><td class="lbl">Pekerjaan</td><td class="sep">:</td><td>{{ $pekerjaan }}</td></tr>
  <tr><td class="lbl">Alamat</td><td class="sep">:</td><td>{{ $alamat }}</td></tr>
</table>

<p class="justify">
  @php($terbilang = [1 => 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh'][(int) $jumlah_hari] ?? null)
  Perlu diberikan istirahat sakit selama <strong>{{ $jumlah_hari }}{{ $terbilang ? ' (' . $terbilang . ')' : '' }} hari</strong>,
  terhitung mulai tanggal <strong>{{ \Carbon\Carbon::parse($tanggal_mulai)->locale('id')->translatedFormat('j F Y') }}</strong>
  sampai dengan <strong>{{ \Carbon\Carbon::parse($tanggal_selesai)->locale('id')->translatedFormat('j F Y') }}</strong>.
</p>
<p class="justify">Demikian surat keterangan ini diberikan untuk diketahui dan dipergunakan seperlunya.</p>

@include('erm.suratistirahat.partials.surat-ttd')
</body>
</html>
