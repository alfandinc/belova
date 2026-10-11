<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Berita Acara Pemusnahan - {{ $nomor }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        .header { text-align: center; margin-bottom: 14px; border-bottom: 2px solid #111; padding-bottom: 8px; }
        .header h2 { margin: 0; font-size: 15px; letter-spacing: .5px; }
        .header .sub { font-size: 11px; margin-top: 2px; }
        .meta { width: 100%; margin-bottom: 10px; border-collapse: collapse; }
        .meta td { padding: 2px 4px; vertical-align: top; border: none; }
        .items { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .items th, .items td { border: 1px solid #333; padding: 5px; }
        .items th { background: #eee; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        p { margin: 6px 0; line-height: 1.45; }
        .sign { width: 100%; margin-top: 28px; border-collapse: collapse; }
        .sign td { width: 33%; text-align: center; vertical-align: top; border: none; padding: 0 6px; }
        .sign .space { height: 60px; }
        .footer { margin-top: 18px; font-size: 9px; color: #555; }
    </style>
</head>
<body>
    @php
        $tanggal = $header->tanggal ?? $header->created_at;
        $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][$tanggal->dayOfWeek];
        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$tanggal->month];
        $petugas = optional($header->creator)->name ?? '-';
    @endphp

    <div class="header">
        <h2>BERITA ACARA PEMUSNAHAN OBAT KADALUARSA</h2>
        <div class="sub">Instalasi Farmasi Belova</div>
        <div class="sub">Nomor: <strong>{{ $nomor }}</strong></div>
    </div>

    <p>
        Pada hari ini <strong>{{ $hari }}</strong>, tanggal <strong>{{ $tanggal->day }} {{ $bulan }} {{ $tanggal->year }}</strong>,
        telah dilakukan pemusnahan obat / bahan medis habis pakai yang telah melewati tanggal kadaluarsa,
        dengan rincian sebagai berikut:
    </p>

    <table class="items">
        <thead>
            <tr>
                <th style="width:4%">No</th>
                <th>Nama Obat</th>
                <th style="width:16%">Batch</th>
                <th style="width:12%">Tgl. Kadaluarsa</th>
                <th style="width:13%">Jumlah</th>
                <th style="width:16%">Gudang</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $i => $item)
                @php $obat = $item->obat; @endphp
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $obat->nama ?? ('Obat #' . $item->obat_id) }}<br><span style="color:#555">{{ $obat->kode_obat ?? '' }}</span></td>
                    <td>{{ optional($item->stokGudang)->batch ?? '-' }}</td>
                    <td class="text-center">{{ optional($item->expiration_date)->format('d-m-Y') ?? '-' }}</td>
                    <td class="text-right">{{ rtrim(rtrim(number_format((float) $item->jumlah, 2, ',', '.'), '0'), ',') }} {{ $obat ? strtolower($obat->satuan_stok ?: $obat->satuan) : '' }}</td>
                    <td>{{ optional(optional($item->stokGudang)->gudang)->nama ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="meta" style="margin-top:10px;">
        <tr><td style="width:24%">Metode pemusnahan</td><td>: {{ $header->metode ?? '-' }}</td></tr>
        <tr><td>Jumlah item</td><td>: {{ $items->count() }} batch</td></tr>
        @if ($header->notes)
            <tr><td>Keterangan</td><td>: {{ $header->notes }}</td></tr>
        @endif
    </table>

    <p>
        Pemusnahan dilakukan sesuai ketentuan yang berlaku dan disaksikan oleh pihak-pihak yang bertanda tangan di bawah ini.
        Berita acara ini dibuat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.
    </p>

    <table class="sign">
        <tr>
            <td>Petugas Farmasi</td>
            <td>Saksi 1</td>
            <td>Saksi 2</td>
        </tr>
        <tr>
            <td class="space"></td>
            <td class="space"></td>
            <td class="space"></td>
        </tr>
        <tr>
            <td>( {{ $petugas }} )</td>
            <td>( {{ $header->saksi_1 ?? '....................' }} )</td>
            <td>( {{ $header->saksi_2 ?? '....................' }} )</td>
        </tr>
    </table>

    <div class="footer">
        Dicetak dari sistem pada {{ now()->format('d-m-Y H:i') }}. Stok telah dikeluarkan dari gudang expired dan tercatat di kartu stok dengan referensi {{ $nomor }}.
    </div>
</body>
</html>
