<div class="table-responsive">
    <table class="table table-bordered">
        <tr>
            <th style="width: 30%">Nama Karyawan</th>
            <td>{{ $pengajuanLibur->employee->nama }}</td>
        </tr>
        <tr>
            <th>Divisi</th>
            <td>{{ $pengajuanLibur->employee->division->name ?? '-' }}</td>
        </tr>
        <tr>
            <th>Jenis Libur</th>
            <td>
                @if($pengajuanLibur->jenis_libur == 'cuti_tahunan')
                    Cuti Tahunan
                @else
                    Ganti Libur
                @endif
            </td>
        </tr>
        <tr>
            <th>Tanggal Mulai</th>
            <td>{{ $pengajuanLibur->tanggal_mulai->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <th>Tanggal Selesai</th>
            <td>{{ $pengajuanLibur->tanggal_selesai->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <th>Jumlah Hari</th>
            <td>{{ $pengajuanLibur->total_hari }} hari</td>
        </tr>
        <tr>
            <th>Alasan</th>
            <td>{{ $pengajuanLibur->alasan }}</td>
        </tr>
        <tr>
            <th>Status Persetujuan Manager</th>
            <td>
                @if($pengajuanLibur->status_manager == 'menunggu')
                    <span class="badge badge-warning">Menunggu</span>
                @elseif($pengajuanLibur->status_manager == 'disetujui')
                    <span class="badge badge-success">Disetujui</span>
                @else
                    <span class="badge badge-danger">Ditolak</span>
                @endif
            </td>
        </tr>
        @if($pengajuanLibur->komentar_manager)
        <tr>
            <th>Catatan Manager</th>
            <td>{{ $pengajuanLibur->komentar_manager }}</td>
        </tr>
        @endif
        @if($pengajuanLibur->tanggal_persetujuan_manager)
        <tr>
            <th>Tanggal Persetujuan Manager</th>
            <td>{{ $pengajuanLibur->tanggal_persetujuan_manager->format('d/m/Y H:i') }}</td>
        </tr>
        @endif
        <tr>
            <th>Status Persetujuan HRD</th>
            <td>
                @if($pengajuanLibur->status_hrd == 'menunggu')
                    <span class="badge badge-warning">Menunggu</span>
                @elseif($pengajuanLibur->status_hrd == 'disetujui')
                    <span class="badge badge-success">Disetujui</span>
                @else
                    <span class="badge badge-danger">Ditolak</span>
                @endif
            </td>
        </tr>
        @if($pengajuanLibur->komentar_hrd)
        <tr>
            <th>Catatan HRD</th>
            <td>{{ $pengajuanLibur->komentar_hrd }}</td>
        </tr>
        @endif
        @if($pengajuanLibur->tanggal_persetujuan_hrd)
        <tr>
            <th>Tanggal Persetujuan HRD</th>
            <td>{{ $pengajuanLibur->tanggal_persetujuan_hrd->format('d/m/Y H:i') }}</td>
        </tr>
        @endif
    </table>
</div>