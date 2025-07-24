<div class="container">
    <h4>Detail Pengajuan Lembur</h4>
    <table class="table table-bordered">
        <tr>
            <th>Tanggal</th>
            <td>{{ $pengajuan->tanggal->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <th>Jam Mulai</th>
            <td>{{ $pengajuan->jam_mulai }}</td>
        </tr>
        <tr>
            <th>Jam Selesai</th>
            <td>{{ $pengajuan->jam_selesai }}</td>
        </tr>
        <tr>
            <th>Total Jam</th>
            <td>{{ $pengajuan->total_jam_formatted }}</td>
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
