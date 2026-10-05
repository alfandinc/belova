<div class="table-responsive">
    <table class="table table-bordered mb-0">
        <tr>
            <th style="width: 30%">Nama Karyawan</th>
            <td>{{ $pengajuanLibur->employee->nama ?? '-' }}</td>
        </tr>
        <tr>
            <th>Jenis Libur</th>
            <td>
                @if($pengajuanLibur->jenis_libur == 'cuti_tahunan')
                    <span class="badge badge-info">Cuti Tahunan</span>
                @else
                    <span class="badge badge-secondary">Ganti Libur</span>
                @endif
            </td>
        </tr>
        <tr>
            <th>Tanggal</th>
            <td>
                {{ $pengajuanLibur->tanggal_mulai->locale('id')->translatedFormat('l, j F Y') }}
                @unless($pengajuanLibur->tanggal_mulai->isSameDay($pengajuanLibur->tanggal_selesai))
                    &ndash; {{ $pengajuanLibur->tanggal_selesai->locale('id')->translatedFormat('l, j F Y') }}
                @endunless
            </td>
        </tr>
        <tr>
            <th>Jumlah Hari</th>
            <td>{{ $pengajuanLibur->total_hari }} hari</td>
        </tr>
        @include('hrd.pengajuan._diajukan_row', ['p' => $pengajuanLibur])
        @if($pengajuanLibur->jenis_libur === 'ganti_libur')
        <tr>
            <th>Pengganti (Masuk Minggu / Libur Nasional)</th>
            <td>
                @forelse(collect($pengajuanLibur->tanggal_masuk_pengganti ?? [])->sort() as $tgl)
                    <div><i class="fas fa-calendar-check text-success mr-1"></i>{{ \Carbon\Carbon::parse($tgl)->locale('id')->translatedFormat('l, j F Y') }}</div>
                @empty
                    <span class="text-muted">Tidak dicatat (pengajuan lama)</span>
                @endforelse
            </td>
        </tr>
        @endif
        <tr>
            <th>Alasan</th>
            <td>{!! nl2br(e($pengajuanLibur->alasan)) !!}</td>
        </tr>
        @include('hrd.pengajuan._approval_progress', ['p' => $pengajuanLibur])
    </table>
</div>
