<div class="row">
    <div class="col-md-6">
        <table class="table table-bordered">
            <tr><th>No Induk</th><td>{{ $slip->employee->no_induk ?? '-' }}</td></tr>
            <tr><th>Nama</th><td>{{ $slip->employee->nama ?? '-' }}</td></tr>
            <tr><th>Divisi</th><td>{{ $slip->employee->division->name ?? '-' }}</td></tr>
            <tr><th>Bulan</th><td>{{ $slip->bulan }}</td></tr>
            <tr><th>Status</th><td>{{ $slip->status_gaji }}</td></tr>
        </table>
    </div>
    <div class="col-md-6">
        <table class="table table-bordered">
            <tr><th>Jumlah Hari Masuk</th><td>{{ $slip->total_hari_masuk }}</td></tr>
            <tr><th>KPI Poin</th><td>{{ $slip->kpi_poin }}</td></tr>
            <tr><th>Jumlah Pendapatan</th><td>{{ number_format($slip->total_pendapatan, 2) }}</td></tr>
            <tr><th>Jumlah Potongan</th><td>{{ number_format($slip->total_potongan, 2) }}</td></tr>
            <tr><th>Total Gaji</th><td>{{ number_format($slip->total_gaji, 2) }}</td></tr>
        </table>
    </div>
</div>
<hr>
<h5>Rincian</h5>
<table class="table table-bordered">
    <tr><th>Gaji Pokok</th><td>{{ number_format($slip->gaji_pokok, 2) }}</td></tr>
    <tr><th>Tunjangan Jabatan</th><td>{{ number_format($slip->tunjangan_jabatan, 2) }}</td></tr>
    <tr><th>Uang Makan</th><td>{{ number_format($slip->uang_makan, 2) }}</td></tr>
    <tr><th>Uang KPI</th><td>{{ number_format($slip->uang_kpi, 2) }}</td></tr>
    <tr><th>Jasa Medis</th><td>{{ number_format($slip->jasa_medis, 2) }}</td></tr>
    <tr><th>Total Jam Lembur</th><td>{{ $slip->total_jam_lembur }}</td></tr>
    <tr><th>Uang Lembur</th><td>{{ number_format($slip->uang_lembur, 2) }}</td></tr>
    <tr><th>Potongan Pinjaman</th><td>{{ number_format($slip->potongan_pinjaman, 2) }}</td></tr>
    <tr><th>Potongan BPJS Kesehatan</th><td>{{ number_format($slip->potongan_bpjs_kesehatan, 2) }}</td></tr>
    <tr><th>Potongan Jamsostek</th><td>{{ number_format($slip->potongan_jamsostek, 2) }}</td></tr>
    <tr><th>Potongan Penalty</th><td>{{ number_format($slip->potongan_penalty, 2) }}</td></tr>
    <tr><th>Potongan Lain</th><td>{{ number_format($slip->potongan_lain, 2) }}</td></tr>
    <tr><th>Benefit BPJS Kesehatan</th><td>{{ number_format($slip->benefit_bpjs_kesehatan, 2) }}</td></tr>
    <tr><th>Benefit JHT</th><td>{{ number_format($slip->benefit_jht, 2) }}</td></tr>
    <tr><th>Benefit JKK</th><td>{{ number_format($slip->benefit_jkk, 2) }}</td></tr>
    <tr><th>Benefit JKM</th><td>{{ number_format($slip->benefit_jkm, 2) }}</td></tr>
</table>
