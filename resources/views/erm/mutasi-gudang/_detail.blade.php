<div class="table-responsive">
    <table class="table table-sm table-borderless">
        <tr>
            <td width="150">No. Mutasi</td>
            <td>: {{ $mutasi->nomor_mutasi }}</td>
        </tr>
        <tr>
            <td>Tanggal</td>
            <td>: {{ $mutasi->created_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td>Status</td>
            <td>: {!! $mutasi->status_label !!}</td>
        </tr>
        <tr>
            <td>Diminta Oleh</td>
            <td>: {{ $mutasi->requestedBy->name }}</td>
        </tr>
        @if($mutasi->approved_by)
        <tr>
            <td>Disetujui Oleh</td>
            <td>: {{ $mutasi->approvedBy->name }}</td>
        </tr>
        <tr>
            <td>Tanggal Disetujui</td>
            <td>:
                @if($mutasi->approved_at instanceof \Carbon\Carbon)
                    {{ $mutasi->approved_at->format('d/m/Y H:i') }}
                @elseif(is_string($mutasi->approved_at) && strtotime($mutasi->approved_at))
                    {{ date('d/m/Y H:i', strtotime($mutasi->approved_at)) }}
                @else
                    -
                @endif
            </td>
        </tr>
        @endif
    </table>

    <h5 class="mt-4">Detail Obat</h5>
    <table class="table table-bordered">
        <tr>
            <th>Nama Obat</th>
            <td>{{ $mutasi->obat->nama }}</td>
        </tr>
        <tr>
            <th>Jumlah</th>
            <td>{{ $mutasi->jumlah }}</td>
        </tr>
        <tr>
            <th>Dari Gudang</th>
            <td>{{ $mutasi->gudangAsal->nama }}</td>
        </tr>
        <tr>
            <th>Ke Gudang</th>
            <td>{{ $mutasi->gudangTujuan->nama }}</td>
        </tr>
        <tr>
            <th>Keterangan</th>
            <td>{{ $mutasi->keterangan ?: '-' }}</td>
        </tr>
    </table>
</div>
