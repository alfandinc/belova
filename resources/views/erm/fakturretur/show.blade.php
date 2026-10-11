@php
    $badge = ['diajukan' => 'badge-warning', 'menunggu_retur' => 'badge-warning', 'diapprove' => 'badge-success', 'ditolak' => 'badge-danger'][$retur->status] ?? 'badge-secondary';
    $pending = in_array($retur->status, ['diajukan', 'menunggu_retur'], true);
@endphp
<div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
    <div>
        <div class="h5 mb-1">{{ $retur->no_retur }} <span class="badge {{ $badge }}">{{ $statusLabel }}</span></div>
        <div class="small text-muted">
            Tanggal retur {{ $retur->tanggal_retur ? \Carbon\Carbon::parse($retur->tanggal_retur)->format('d/m/Y') : '-' }}
            · Faktur {{ optional($retur->fakturbeli)->no_faktur ?? '-' }}
            · {{ optional($retur->pemasok)->nama ?? '-' }}
        </div>
    </div>
</div>

@if($pending)
    <div class="alert alert-warning small py-2">Menunggu persetujuan. <strong>Stok belum berkurang</strong> sampai retur disetujui.</div>
@elseif($retur->status === 'diapprove')
    <div class="alert alert-success small py-2">Disetujui{{ $approver ? ' oleh ' . $approver->name : '' }}{{ $retur->approved_at ? ' pada ' . $retur->approved_at->format('d/m/Y H:i') : '' }}. Stok sudah dikurangi.</div>
@elseif($retur->status === 'ditolak')
    <div class="alert alert-danger small py-2">Ditolak{{ $approver ? ' oleh ' . $approver->name : '' }}. Stok tidak berubah.@if($retur->rejected_reason)<br>Alasan: {{ $retur->rejected_reason }}@endif</div>
@endif

<div class="table-responsive">
    <table class="table table-bordered table-sm">
        <thead class="thead-light">
            <tr>
                <th>Obat</th>
                <th>Gudang</th>
                <th>Batch / ED</th>
                <th class="text-right">Qty Retur</th>
                <th>Alasan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($retur->items as $item)
            <tr>
                <td>{{ $item->obat->nama ?? '-' }}</td>
                <td>{{ $item->gudang->nama ?? '-' }}</td>
                <td><code>{{ $item->batch ?? '-' }}</code>{{ $item->expiration_date ? ' · ' . \Carbon\Carbon::parse($item->expiration_date)->format('d/m/Y') : '' }}</td>
                <td class="text-right font-weight-bold">{{ $item->qty }}</td>
                <td>{{ $item->alasan ?: '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@if($retur->notes)
    <p class="small text-muted mb-2"><strong>Catatan:</strong> {{ $retur->notes }}</p>
@endif

<div class="d-flex flex-wrap justify-content-end">
    @if($retur->status === 'diapprove')
        <a href="{{ route('erm.fakturbeli.create', ['replace_retur_id' => $retur->id]) }}" class="btn btn-outline-primary btn-sm mr-auto btn-faktur-pengganti" data-retur="{{ $retur->id }}"><i class="fas fa-file-medical"></i> Buat Faktur Pengganti</a>
    @endif
    @if($pending && $canDecide)
        <button class="btn btn-outline-danger btn-sm mr-2" id="btn-reject-retur" data-id="{{ $retur->id }}" data-nomor="{{ $retur->no_retur }}"><i class="fas fa-times"></i> Tolak</button>
        <button class="btn btn-success btn-sm" id="btn-approve-retur" data-id="{{ $retur->id }}" data-nomor="{{ $retur->no_retur }}"><i class="fas fa-check"></i> Setujui & kurangi stok</button>
    @endif
</div>
