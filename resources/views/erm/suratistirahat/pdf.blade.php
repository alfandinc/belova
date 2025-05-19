<!-- resources/views/erm/surat_istirahat/pdf.blade.php -->
<html>
<head>
    <style>
        body { font-family: sans-serif; }
    </style>
</head>
<body>
    <h2>Surat Keterangan Istirahat</h2>
    <p>Nama: {{ $surat->pasien->nama }}</p>
    <p>Diminta untuk istirahat selama {{ $surat->jumlah_hari }} hari, dari {{ $surat->tanggal_mulai }} sampai {{ $surat->tanggal_selesai }}.</p>
    <br><br>
    <p>Dokter,</p>
    <br><br>
    <p>_____________________</p>
</body>
</html>
