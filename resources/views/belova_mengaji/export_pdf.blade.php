@php
    // Simple printable table for PDF export
    function formatVal($v) {
        if ($v === null || $v === '') return '';
        if (is_numeric($v) && floor($v) == $v) return (string) $v;
        return number_format($v, 2, '.', ',');
    }
@endphp
<div style="font-family: DejaVu Sans, sans-serif; font-size:12px;">
    <h3>Belova Mengaji - Absensi / Nilai per tanggal: {{ $date }}</h3>
    <table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width:100%; font-size:11px;">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Tanggal</th>
                <th>Makhroj</th>
                <th>Tajwid</th>
                <th>Panjang/Pendek</th>
                <th>Kelancaran</th>
                <th>Total</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ optional($r->employee)->nama }}</td>
                    <td>{{ $r->date? $r->date->format('Y-m-d') : '' }}</td>
                    <td style="text-align:center">{{ $r->nilai_makhroj }}</td>
                    <td style="text-align:center">{{ $r->nilai_tajwid }}</td>
                    <td style="text-align:center">{{ $r->nilai_panjang_pendek }}</td>
                    <td style="text-align:center">{{ $r->nilai_kelancaran }}</td>
                    <td style="text-align:center">{{ $r->total_nilai }}</td>
                    <td>{{ $r->catatan }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center">Tidak ada data untuk tanggal ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
