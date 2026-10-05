@php
    $mulai = substr((string) $pengajuan->jam_mulai, 0, 5);
    $selesai = substr((string) $pengajuan->jam_selesai, 0, 5);
@endphp
<div class="table-responsive">
    <table class="table table-bordered mb-0">
        <tr>
            <th style="width: 30%">Nama Karyawan</th>
            <td>{{ $pengajuan->employee->nama ?? '-' }}</td>
        </tr>
        <tr>
            <th>Tanggal</th>
            <td>{{ $pengajuan->tanggal->locale('id')->translatedFormat('l, j F Y') }}</td>
        </tr>
        <tr>
            <th>Jam</th>
            <td>
                {{ $mulai }} &ndash; {{ $selesai }}
                @if($selesai <= $mulai)
                    <span class="badge badge-light border ml-1">selesai keesokan hari</span>
                @endif
            </td>
        </tr>
        <tr>
            <th>Total Jam</th>
            <td>{{ $pengajuan->total_jam_formatted }}</td>
        </tr>
        @if($pengajuan->jam_mulai_diajukan !== null)
        <tr class="table-warning">
            <th>Diajukan Awalnya</th>
            <td>
                {{ substr($pengajuan->jam_mulai_diajukan, 0, 5) }} &ndash; {{ substr($pengajuan->jam_selesai_diajukan, 0, 5) }}
                ({{ $pengajuan->total_jam_diajukan_formatted }})
                <div class="small text-muted">Disetujui sebagian. Jam di atas adalah jam yang disetujui.</div>
            </td>
        </tr>
        @endif
        <tr>
            <th>Alasan</th>
            <td>{!! nl2br(e($pengajuan->alasan)) !!}</td>
        </tr>
        @include('hrd.pengajuan._approval_progress', ['p' => $pengajuan])
    </table>
</div>
