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
            <td>:
                @php
                    $labels = [
                        'pending' => '<span class="badge bg-warning">Pending</span>',
                        'approved' => '<span class="badge bg-success">Disetujui</span>',
                        'rejected' => '<span class="badge bg-danger">Ditolak</span>'
                    ];
                @endphp
                {!! $labels[$mutasi->status] ?? $mutasi->status !!}
            </td>
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
        <thead>
            <tr>
                <th>Nama Obat</th>
                <th>Jumlah</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @if($mutasi->items && $mutasi->items->count() > 0)
                @foreach($mutasi->items as $item)
                    <tr>
                        <td>{{ $item->obat ? $item->obat->nama : ('Obat ID ' . $item->obat_id) }}</td>
                        <td>{{ $item->jumlah }}</td>
                        <td>{{ $item->keterangan ?: '-' }}</td>
                    </tr>
                @endforeach
            @else
                {{-- Fallback to legacy single-obat fields --}}
                <tr>
                    <td>{{ $mutasi->obat ? $mutasi->obat->nama : '-' }}</td>
                    <td>{{ $mutasi->jumlah ?? '-' }}</td>
                    <td>{{ $mutasi->keterangan ?: '-' }}</td>
                </tr>
            @endif
        </tbody>
    </table>
    <table class="table table-borderless mt-2">
        <tr>
            <th width="150">Dari Gudang</th>
            <td>: {{ $mutasi->gudangAsal->nama }}</td>
        </tr>
        <tr>
            <th>Ke Gudang</th>
            <td>: {{ $mutasi->gudangTujuan->nama }}</td>
        </tr>
    </table>
</div>
