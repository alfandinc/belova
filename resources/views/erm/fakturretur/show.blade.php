<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <p><strong>No Retur:</strong> {{ $retur->no_retur }}</p>
            <p><strong>Tanggal Retur:</strong> {{ $retur->tanggal_retur }}</p>
            <p><strong>Status:</strong> <span id="retur-status">{{ $retur->status }}</span></p>
            @if($retur->status == 'diapprove')
            <div class="mb-3">
                <a href="{{ route('erm.fakturbeli.create', ['replace_retur_id' => $retur->id]) }}" class="btn btn-primary">
                    Buat Faktur Pengganti
                </a>
            </div>
            @endif
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Obat</th>
                        <th>Gudang</th>
                        <th>Batch</th>
                        <th>Qty Retur</th>
                        <th>Alasan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($retur->items as $item)
                    <tr>
                        <td>{{ $item->obat->nama ?? '-' }}</td>
                        <td>{{ $item->gudang->nama ?? '-' }}</td>
                        <td>{{ $item->batch ?? '-' }}</td>
                        <td>{{ $item->qty }}</td>
                        <td>{{ $item->alasan }}</td>
                        <td>{{ $item->status }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @if($retur->status == 'diajukan')
            <button class="btn btn-success" id="btn-approve-retur" data-id="{{ $retur->id }}">Approve Retur</button>
            @endif
        </div>
    </div>
</div>
