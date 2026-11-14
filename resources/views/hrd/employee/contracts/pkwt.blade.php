{{-- Draft Perjanjian Kerja Waktu Tertentu (dompdf). Isi pasal umum; HRD memeriksa / melengkapi sebelum ditandatangani. --}}
@php
    $fmt = fn($d) => $d ? $d->locale('id')->translatedFormat('j F Y') : '........................';
    $perusahaan = $klinik->nama ?? ($employee->perusahaan ?: '........................');
    $jabatan = optional($employee->position)->name ?? '........................';
    $divisi = optional($employee->division)->name;
    $blank = '........................';
    // 1–99 in words (durasi kontrak is at most 60 bulan)
    $sebut = function (int $n) {
        $s = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
        if ($n < 12) return $s[$n];
        if ($n < 20) return $s[$n - 10] . ' belas';
        return trim($s[intdiv($n, 10)] . ' puluh ' . $s[$n % 10]);
    };
    $jk = strtoupper(substr((string) $employee->jenis_kelamin, 0, 1));
@endphp
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>PKWT {{ $employee->nama }}</title>
<style>
    @page { margin: 1.6cm 2cm; }
    body { font-family: 'DejaVu Serif', serif; font-size: 10pt; line-height: 1.35; color: #000; }
    .penutup { page-break-inside: avoid; }
    h1 { font-size: 13pt; text-align: center; margin: 0; text-decoration: underline; }
    .nomor { text-align: center; margin: 2px 0 18px; }
    .kop { text-align: center; border-bottom: 2px solid #000; padding-bottom: 6px; margin-bottom: 16px; }
    .kop b { font-size: 13pt; }
    .kop small { display: block; }
    table.data { margin: 4px 0 10px 18px; border-collapse: collapse; }
    table.data td { padding: 1px 6px 1px 0; vertical-align: top; }
    h2 { font-size: 11pt; text-align: center; margin: 14px 0 4px; }
    p { margin: 0 0 6px; text-align: justify; }
    ol { margin: 0 0 6px; padding-left: 20px; }
    .ttd { width: 100%; margin-top: 18px; }
    .ttd td { width: 50%; text-align: center; vertical-align: top; }
    .draft { position: fixed; top: -1.4cm; right: 0; font-size: 8pt; color: #888; }
</style>
</head>
<body>
<div class="draft">Draf dibuat {{ now()->format('d/m/Y H:i') }} · periksa sebelum ditandatangani</div>

<div class="kop">
    <b>{{ strtoupper($perusahaan) }}</b>
    @if($klinik && $klinik->alamat)<small>{{ $klinik->alamat }}{{ $klinik->telp ? ' · Telp. ' . $klinik->telp : '' }}</small>@endif
</div>

<h1>PERJANJIAN KERJA WAKTU TERTENTU</h1>
<div class="nomor">Nomor: {{ $blank }}{{ $ke > 1 ? ' (Perpanjangan / kontrak ke-' . $ke . ')' : '' }}</div>

<p>Pada hari ini, tanggal {{ $blank }}, yang bertanda tangan di bawah ini:</p>
<table class="data">
    <tr><td>1.</td><td>Nama</td><td>: {{ $blank }}</td></tr>
    <tr><td></td><td>Jabatan</td><td>: {{ $blank }}</td></tr>
    <tr><td></td><td colspan="2">dalam hal ini bertindak untuk dan atas nama <b>{{ $perusahaan }}</b>, selanjutnya disebut <b>PIHAK PERTAMA</b>.</td></tr>
</table>
<table class="data">
    <tr><td>2.</td><td>Nama</td><td>: <b>{{ $employee->nama }}</b></td></tr>
    <tr><td></td><td>NIK</td><td>: {{ $employee->nik ?: $blank }}</td></tr>
    <tr><td></td><td>Tempat, tgl. lahir</td><td>: {{ $employee->tempat_lahir ?: $blank }}, {{ $fmt($employee->tanggal_lahir) }}</td></tr>
    <tr><td></td><td>Jenis kelamin</td><td>: {{ ['L' => 'Laki-laki', 'P' => 'Perempuan'][$jk] ?? $blank }}</td></tr>
    <tr><td></td><td>Alamat</td><td>: {{ $employee->alamat ?: $blank }}</td></tr>
    <tr><td></td><td colspan="2">dalam hal ini bertindak untuk dan atas nama diri sendiri, selanjutnya disebut <b>PIHAK KEDUA</b>.</td></tr>
</table>
<p>Kedua belah pihak sepakat mengadakan Perjanjian Kerja Waktu Tertentu (PKWT) dengan ketentuan sebagai berikut:</p>

<h2>Pasal 1<br>Jangka Waktu</h2>
<p>Perjanjian ini berlaku selama <b>{{ $contract->duration_months }} ({{ $sebut((int) $contract->duration_months) }}) bulan</b>, terhitung mulai
    <b>{{ $fmt($contract->start_date) }}</b> sampai dengan <b>{{ $fmt($contract->end_date) }}</b>.</p>

<h2>Pasal 2<br>Jabatan dan Tugas</h2>
<p>PIHAK PERTAMA mempekerjakan PIHAK KEDUA sebagai <b>{{ $jabatan }}</b>{{ $divisi ? ' pada divisi ' . $divisi : '' }}.
    PIHAK KEDUA wajib melaksanakan tugas sesuai uraian jabatan serta arahan atasan dengan penuh tanggung jawab.</p>

<h2>Pasal 3<br>Waktu Kerja</h2>
<p>Waktu kerja, istirahat, dan cuti mengikuti jadwal kerja dan peraturan yang berlaku di perusahaan.</p>

<h2>Pasal 4<br>Upah dan Hak Lain</h2>
<ol>
    <li>PIHAK KEDUA menerima upah sebesar Rp {{ $blank }} per bulan, dibayarkan setiap bulan.</li>
    <li>PIHAK KEDUA diikutsertakan dalam program BPJS Kesehatan dan BPJS Ketenagakerjaan sesuai ketentuan yang berlaku.</li>
    <li>Pada saat perjanjian ini berakhir, PIHAK KEDUA berhak atas uang kompensasi sesuai peraturan perundang-undangan.</li>
</ol>

<h2>Pasal 5<br>Kewajiban dan Tata Tertib</h2>
<p>PIHAK KEDUA wajib mematuhi peraturan perusahaan, menjaga kerahasiaan data pasien dan perusahaan, serta menjaga nama baik perusahaan
    selama maupun setelah masa perjanjian.</p>

<h2>Pasal 6<br>Berakhirnya Perjanjian</h2>
<ol>
    <li>Perjanjian ini berakhir dengan sendirinya pada tanggal yang disebut dalam Pasal 1, kecuali diperpanjang dengan perjanjian baru.</li>
    <li>Perjanjian dapat diakhiri lebih awal apabila PIHAK KEDUA mengundurkan diri atau melakukan pelanggaran berat, sesuai peraturan perusahaan dan perundang-undangan.</li>
    <li>PIHAK KEDUA yang mengundurkan diri wajib mengajukan permohonan tertulis paling lambat 30 (tiga puluh) hari sebelumnya.</li>
</ol>

<div class="penutup">
<h2>Pasal 7<br>Penutup</h2>
<p>Hal-hal yang belum diatur dalam perjanjian ini mengikuti peraturan perusahaan dan peraturan perundang-undangan yang berlaku.
    Perjanjian ini dibuat rangkap 2 (dua), masing-masing mempunyai kekuatan hukum yang sama.</p>

<table class="ttd">
    <tr>
        <td>PIHAK PERTAMA<br><br><br><br><br>( {{ $blank }} )</td>
        <td>PIHAK KEDUA<br><small>materai</small><br><br><br><br>( <b>{{ $employee->nama }}</b> )</td>
    </tr>
</table>
</div>
</body>
</html>
