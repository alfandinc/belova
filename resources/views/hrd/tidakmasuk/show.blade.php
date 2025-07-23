<div class="container">
    <h4>Detail Pengajuan Tidak Masuk</h4>
    <table class="table table-bordered">
        <tr>
            <th>Jenis</th>
            <td>{{ ucfirst($pengajuan->jenis) }}</td>
        </tr>
        <tr>
            <th>Tanggal Mulai</th>
            <td>{{ $pengajuan->tanggal_mulai->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <th>Tanggal Selesai</th>
            <td>{{ $pengajuan->tanggal_selesai->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <th>Total Hari</th>
            <td>{{ $pengajuan->total_hari }}</td>
        </tr>
        <tr>
            <th>Alasan</th>
            <td>{{ $pengajuan->alasan }}</td>
        </tr>
        <tr>
            <th>Status Manager</th>
            <td>{{ $pengajuan->status_manager ?? '-' }}</td>
        </tr>
        <tr>
            <th>Status HRD</th>
            <td>{{ $pengajuan->status_hrd ?? '-' }}</td>
        </tr>
    </table>
</div>
