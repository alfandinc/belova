<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>IC Pendaftaran</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 16px; text-align: center; margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 6px 4px; vertical-align: top; }
        .label { width: 180px; font-weight: bold; }
        .section-title { font-weight: bold; margin: 12px 0 6px; }
        .signature-box { margin-top: 12px; }
        .signature-box img { border: 1px solid #ccc; width: 100%; max-width: 500px; height: auto; }
    </style>
    </head>
<body>
    <h1>Persetujuan & Informasi Pasien</h1>

    <table>
        <tr>
            <td class="label">NAMA PASIEN</td>
            <td>{{ $pasien->nama ?? '-' }}</td>
            <td class="label">TANGGAL LAHIR</td>
            <td>
                @php
                    function formatLongId($s) {
                        if (!$s) return '';
                        try {
                            $dateStr = is_string($s) ? $s : (string)$s;
                            $dateStr = substr($dateStr, 0, 10);
                            $parts = explode('-', $dateStr);
                            if (count($parts) === 3) {
                                $bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                                $y = intval($parts[0]); $m = intval($parts[1]); $d = intval($parts[2]);
                                return $d . ' ' . $bulan[$m-1] . ' ' . $y;
                            }
                        } catch (Exception $e) {}
                        return $s;
                    }
                @endphp
                {{ formatLongId($pasien->tanggal_lahir ?? '') }}
            </td>
        </tr>
        <tr>
            <td class="label">ALAMAT</td>
            <td>{{ $pasien->alamat ?? '-' }}</td>
            <td class="label">NIK</td>
            <td>{{ $pasien->nik ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">NO. RM</td>
            <td>{{ $pasien->id ?? '-' }}</td>
            <td class="label">NO. HP</td>
            <td>{{ $pasien->no_hp ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">A. HAK DAN KEWAJIBAN KLINIK</div>
    <ol>
        <li>Seluruh pasien yang datang akan memperoleh informasi tentang hak dan kewajiban yang berlaku di Klinik.</li>
        <li>Klinik memberikan pelayanan tanpa membedakan kelas, jenis kelamin dan agama secara manusiawi, adil, jujur, dan tanpa diskriminasi.</li>
        <li>Klinik memberikan pelayanan kesehatan yang profesional, bermutu seusai dengan standar profesi dan standar prosedur operasional.</li>
        <li>Klinik melindungi privasi dan kerahasian penyakit yang diderita termasuk data-data medisnya.</li>
        <li>Klinik memberikan pilihan kepada pasien atas persetujuan atau penolakkan tindakan atau pengobatan yang akan dilakukan oleh tenaga kesehatan terhadap penyakit yang dideritanya.</li>
        <li>Klinik memfasilitasi keluarga untuk mendampingi pasien yang membutuhkan bantuan.</li>
        <li>Klinik memonitoring dan mengevaluasi secara periodik pelaksanaan edukasi hak dan kewajiban pasien.</li>
    </ol>

    <div class="section-title">B. HAK PASIEN</div>
    <ol>
        <li>Memperoleh informasi mengenai tata tertib dan peraturan yang berlaku di Klinik Utama Premiere Belova.</li>
        <li>Memperoleh informasi mengenai hak dan kewajiban pasien.</li>
        <li>Memperoleh layanan yang manusiawi, adil, jujur, dan tanpa diskriminasi.</li>
        <li>Memperoleh pelayanan kesehatan bermutu sesuai dengan standar profesi.</li>
        <li>Memperoleh layanan yang efektif dan effisien sehingga pasien terhindar dari kerugian fisik dan materi.</li>
        <li>Mengajukan pengaduan atas kualitas pelayanan yang didapatkan melalui kotak saran.</li>
        <li>Mendapatkan privasi dan kerahasian penyakit yang diderita termasuk data medinya.</li>
        <li>Memberikan persetujuan atau menolak atas tindakan yang akan dilakukan oleh tenaga kesehatan terhadap penyakit yang dideritanya.</li>
        <li>Mendapatkan informasi yang meliputi diagnosis dan tata cara tindakan medis, tujuan tindakan medis, alternatif tindakan, resiko dan komplikasi yang mungkin terjadi, dan prognosis terhadap tindakan yang dilakukan serta perkiraan biaya pengobatan.</li>
        <li>Memperoleh keamanan dan keselamatan dirinya selama dalam perawatan di Klinik Utama Premiere Belova.</li>
        <li>Menyampaikan usul dan saran dalam rangka perbaikan atas perlakuan Klinik terhadap dirinya.</li>
    </ol>

    <div class="section-title">C. KEWAJIBAN PASIEN</div>
    <ol>
        <li>Pasien dan keluarga berkewajiban menaati segala peraturan dan tata tertib di Klinik.</li>
        <li>Pasien wajib untuk menginformasikan secara jujur tentang segala sesuatu mengenai penyakit yang dideritanya.</li>
        <li>Pasien wajib untuk mentaati segala instruksi dokter dalam rangka pengobatannya.</li>
        <li>Pasien dan pengantar berkewajiban untuk memenuhi segala ketentuan administrasi.</li>
    </ol>

    <div class="signature-box">
        <div class="section-title">Tanda Tangan Pasien</div>
        @if($signatureDataUri)
            <img src="{{ $signatureDataUri }}" alt="Tanda Tangan">
        @else
            <p>(Tanda tangan tidak tersedia)</p>
        @endif
        <p>Ditandatangani pada: {{ optional($record->signed_at)->format('d-m-Y H:i') }}</p>
    </div>
</body>
</html>
