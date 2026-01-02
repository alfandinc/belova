<!doctype html>
<html>
<head>
    <meta charset="utf-8" />
    <title>IC Pendaftaran</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { text-align: left; }
        .title { font-weight: bold; font-size: 16px; margin-bottom: 8px; }
        .section { margin-top: 12px; }
        .signature { margin-top: 40px; }
        .small { font-size: 11px; color: #444; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">KLINIK UTAMA PREMIERE BELOVA</div>
        <div class="small">Dokumentasi - Hak dan Kewajiban Pasien</div>
    </div>

    <div class="section">
        <strong>Nama Pasien:</strong> {{ $pasien->nama ?? '-' }}<br/>
        <strong>Tempat/Tanggal Lahir:</strong> {{ $pasien->tanggal_lahir ?? '-' }}<br/>
        <strong>Alamat:</strong> {{ $pasien->alamat ?? '-' }}<br/>
        <strong>NIK:</strong> {{ $pasien->nik ?? '-' }}<br/>
        <strong>No. HP:</strong> {{ $pasien->no_hp ?? '-' }}
    </div>

    <div class="section">
        <h4>A. Hak dan Kewajiban Klinik</h4>
        <ol>
            <li>Seluruh pasien yang datang akan memperoleh informasi tentang hak dan kewajiban yang berlaku di Klinik.</li>
            <li>Klinik memberikan pelayanan tanpa membedakan kelas, jenis kelamin dan agama secara manusiawi, adil, jujur, dan tanpa diskriminasi.</li>
            <li>Klinik memberikan pelayanan kesehatan yang profesional, bermutu sesuai dengan standar profesi dan standar prosedur operasional.</li>
            <li>Klinik melindungi privasi dan kerahasiaan penyakit yang diderita termasuk data-data medisnya.</li>
        </ol>
    </div>

    <div class="section">
        <h4>B. Hak Pasien</h4>
        <ol>
            <li>Memperoleh informasi mengenai tata tertib dan peraturan yang berlaku di Klinik Utama Premiere Belova.</li>
            <li>Memperoleh layanan yang manusiawi, adil, jujur, dan tanpa diskriminasi.</li>
            <li>Memperoleh pelayanan kesehatan bermutu sesuai dengan standar profesi.</li>
        </ol>
    </div>

    <div class="section">
        <h4>C. Kewajiban Pasien</h4>
        <ol>
            <li>Pasien dan keluarga berkewajiban menaati segala peraturan dan tata tertib di Klinik.</li>
            <li>Pasien wajib menginformasikan secara jujur tentang segala sesuatu mengenai penyakit yang dideritanya.</li>
        </ol>
    </div>

    <div class="signature">
        <div><strong>Tanda Tangan Pasien</strong></div>
        @if(!empty($signature_data_url))
            <div style="margin-top:8px;"><img src="{{ $signature_data_url }}" style="max-width:300px; height:auto; border:1px solid #ccc;"/></div>
        @endif

        <div style="margin-top:12px;">
            <small>Ditandatangani pada: {{ $signed_at->format('Y-m-d H:i') }}</small>
        </div>
    </div>

</body>
</html>
