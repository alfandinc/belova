<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <title>Surat Permintaan Konsultasi - {{ optional($rujuk->pasien)->nama ?? '-' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color:#111; }
        .header { display:flex; align-items:center; justify-content:space-between; }
        .clinic { text-align:left; }
        .clinic h2 { margin:0; }
        .meta { margin-top:10px; margin-bottom:8px; }
        .box { border:1px solid #000; padding:12px; }
        table { width:100%; border-collapse:collapse; }
        td.label { width:20%; vertical-align:top; font-weight:700; }
        td.value { width:80%; }
        .signature { margin-top:40px; text-align:center; }
        .small { font-size:0.85rem; color:#444; }
        @media print {
            .no-print { display:none; }
        }
    </style>
</head>
<body onload="setTimeout(()=>{ window.print(); },300)">
    <div class="header">
        <div class="clinic">
            <h2>KLINIK UTAMA PREMIERE BELOVA</h2>
            <div class="small">Jl. Melon Raya No.27, Karangasem, Laweyan, Surakarta</div>
            <div class="small">Telp. 0821-1600-0093</div>
        </div>
        <div style="text-align:right">
            <img src="{{ asset('public/img/logo.png') }}" alt="logo" style="height:60px;" onerror="this.style.display='none'" />
        </div>
    </div>

    <h3 style="text-align:center; margin-top:18px; text-decoration:underline;">SURAT PERMINTAAN KONSULTASI / RUJUK</h3>

    <div class="box">
        <table>
            <tr>
                <td class="label">Tanggal</td>
                <td class="value">: {{ optional($rujuk->created_at)->translatedFormat('j F Y') ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Klinik</td>
                <td class="value">: {{ optional($rujuk->visitation->klinik)->nama ?? 'Klinik Utama Premiere Belova' }}</td>
            </tr>
            <tr>
                <td class="label">Nama</td>
                <td class="value">: {{ optional($rujuk->pasien)->nama ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">No RM</td>
                <td class="value">: {{ optional($rujuk->pasien)->id ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Umur</td>
                <td class="value">: {{ $age ? $age.' Tahun' : '-' }}</td>
            </tr>
            <tr>
                <td class="label">Jenis Kelamin</td>
                <td class="value">: {{ optional($rujuk->pasien)->gender ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Diagnosa</td>
                <td class="value">: {{ $rujuk->keterangan ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Konsul Ke</td>
                <td class="value">: {{ optional($rujuk->dokterTujuan->user)->name ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="signature">
        <div style="height:90px;"></div>
        <div style="font-weight:700">{{ optional($rujuk->dokterPengirim->user)->name ?? (Auth::user()->name ?? '-') }}</div>
        <div class="small">dr. (Pengirim)</div>
    </div>

    <div style="margin-top:10px; font-size:0.75rem; color:#666;" class="no-print">Halaman ini akan terbuka di tab baru; gunakan Print -> Save as PDF untuk menyimpan sebagai PDF.</div>
</body>
</html>
