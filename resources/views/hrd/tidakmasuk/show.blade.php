<div class="table-responsive">
    <table class="table table-bordered mb-0">
        <tr>
            <th style="width: 30%">Nama Karyawan</th>
            <td>{{ $pengajuan->employee->nama ?? '-' }}</td>
        </tr>
        <tr>
            <th>Jenis</th>
            <td><span class="badge {{ $pengajuan->jenis === 'sakit' ? 'badge-danger' : 'badge-info' }}">{{ ucfirst($pengajuan->jenis) }}</span></td>
        </tr>
        <tr>
            <th>Tanggal</th>
            <td>
                {{ $pengajuan->tanggal_mulai->locale('id')->translatedFormat('l, j F Y') }}
                @unless($pengajuan->tanggal_mulai->isSameDay($pengajuan->tanggal_selesai))
                    &ndash; {{ $pengajuan->tanggal_selesai->locale('id')->translatedFormat('l, j F Y') }}
                @endunless
            </td>
        </tr>
        <tr>
            <th>Jumlah Hari</th>
            <td>{{ $pengajuan->total_hari ?? 1 }} hari</td>
        </tr>
        @include('hrd.pengajuan._diajukan_row', ['p' => $pengajuan])
        <tr>
            <th>Alasan</th>
            <td>{!! nl2br(e($pengajuan->alasan)) !!}</td>
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
                        <a href="{{ $url }}" target="_blank" rel="noopener">
                            <img src="{{ $url }}" alt="Bukti" style="max-width:200px;max-height:200px;" class="img-thumbnail">
                        </a>
                    @elseif($ext === 'pdf')
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-sm btn-info"><i class="fas fa-file-pdf mr-1"></i>Lihat PDF</a>
                    @else
                        <a href="{{ $url }}" target="_blank" rel="noopener">Download Bukti</a>
                    @endif
                @else
                    <span class="text-muted">Tidak ada bukti</span>
                @endif
            </td>
        </tr>
        @include('hrd.pengajuan._approval_progress', ['p' => $pengajuan])
        @if($pengajuan->status_hrd === 'disetujui')
        <tr>
            <th>Potong Cuti Tahunan</th>
            <td>
                @if(!empty($potongCuti))
                    <span class="badge badge-warning">Ya</span>
                    <span class="ml-1">{{ $potongCuti->total_hari }} hari dicatat sebagai cuti tahunan dan saldo cuti dikurangi.</span>
                @else
                    <span class="text-muted">Tidak</span>
                @endif
            </td>
        </tr>
        @endif
    </table>
</div>
