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
        <tr>
            <th>Bukti</th>
            <td>
                @if($pengajuan->bukti)
                    @php
                        $ext = strtolower(pathinfo($pengajuan->bukti, PATHINFO_EXTENSION));
                        $url = asset('storage/' . $pengajuan->bukti);
                    @endphp
                    @if(in_array($ext, ['jpg','jpeg','png']))
                        <a href="{{ $url }}" target="_blank">
                            <img src="{{ $url }}" alt="Bukti" style="max-width:200px;max-height:200px;" class="img-thumbnail">
                        </a>
                    @elseif($ext === 'pdf')
                        <a href="{{ $url }}" target="_blank" class="btn btn-sm btn-info">Lihat PDF</a>
                    @else
                        <a href="{{ $url }}" target="_blank">Download Bukti</a>
                    @endif
                @else
                    <span class="text-muted">Tidak ada bukti</span>
                @endif
            </td>
        </tr>
    </table>
</div>
