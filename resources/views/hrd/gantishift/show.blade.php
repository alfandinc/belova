<div class="table-responsive">
    <table class="table table-bordered mb-0">
        <tr>
            <th style="width: 30%">Nama Karyawan</th>
            <td>{{ $pengajuan->employee->nama ?? '-' }}</td>
        </tr>
        <tr>
            <th>Jenis</th>
            <td>
                @if($pengajuan->is_tukar_shift)
                    <span class="badge badge-info">Tukar Shift</span>
                    dengan <strong>{{ $pengajuan->targetEmployee->nama ?? '-' }}</strong>
                @else
                    <span class="badge badge-secondary">Ganti Shift</span>
                @endif
            </td>
        </tr>
        <tr>
            <th>Tanggal</th>
            <td>{{ $pengajuan->tanggal_shift->locale('id')->translatedFormat('l, j F Y') }}</td>
        </tr>
        <tr>
            <th>Perubahan Shift</th>
            <td>
                <span class="text-muted">{{ $shiftLama }}</span>
                <i class="fas fa-arrow-right mx-1 text-primary"></i>
                <strong>{{ $shiftBaru }}</strong>
                @if($pengajuan->is_tukar_shift)
                    <div class="small text-muted mt-1">{{ $pengajuan->targetEmployee->nama ?? 'Rekan' }} akan mendapat {{ $shiftLama }}.</div>
                @endif
            </td>
        </tr>
        <tr>
            <th>Alasan</th>
            <td>{!! nl2br(e($pengajuan->alasan)) !!}</td>
        </tr>
        @if($pengajuan->is_tukar_shift)
        <tr>
            <th>Persetujuan Rekan</th>
            <td>
                @php $ts = $pengajuan->target_employee_approval_status; @endphp
                <span class="badge {{ $ts === 'disetujui' ? 'badge-success' : ($ts === 'ditolak' ? 'badge-danger' : 'badge-warning') }}">{{ ucfirst($ts ?? 'menunggu') }}</span>
                @if($pengajuan->target_employee_approval_date)
                    <span class="text-muted small ml-1">{{ $pengajuan->target_employee_approval_date->locale('id')->translatedFormat('j M Y H:i') }}</span>
                @endif
                @if($pengajuan->target_employee_notes)
                    <div class="mt-1"><i class="fas fa-comment-alt text-muted mr-1"></i>{{ $pengajuan->target_employee_notes }}</div>
                @endif
            </td>
        </tr>
        @endif
        @include('hrd.pengajuan._approval_progress', ['p' => $pengajuan])
        <tr>
            <th>Jadwal Saat Ini</th>
            <td>
                @forelse($currentShifts as $label)
                    <div>{{ $label }}</div>
                @empty
                    <span class="text-muted">Tidak ada shift</span>
                @endforelse
                @if($pengajuan->status_hrd === 'disetujui')
                    <div class="small text-success mt-1"><i class="fas fa-check mr-1"></i>Pengajuan sudah diterapkan ke jadwal.</div>
                @endif
            </td>
        </tr>
    </table>
</div>
