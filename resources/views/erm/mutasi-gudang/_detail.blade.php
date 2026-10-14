@php
    $fmt = function ($n) { return rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ','); };
@endphp
<div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
    <div class="mb-2">
        <div class="h5 mb-1">{{ $mutasi->nomor_mutasi }} {!! $statusBadge !!}</div>
        <div class="text-muted small">Dibuat {{ $mutasi->created_at->format('d/m/Y H:i') }} oleh {{ optional($mutasi->requestedBy)->name ?? '-' }}</div>
    </div>
    <div class="text-md-right mb-2">
        <div class="small text-muted">Rute</div>
        <div><i class="fas fa-arrow-circle-left text-danger"></i> {{ optional($mutasi->gudangAsal)->nama ?? '-' }}
            <span class="mx-1">&rarr;</span>
            <i class="fas fa-arrow-circle-right text-success"></i> {{ optional($mutasi->gudangTujuan)->nama ?? '-' }}</div>
    </div>
</div>

@if($mutasi->status === 'pending')
    @if($kurang > 0)
        <div class="alert alert-danger small py-2 mb-3"><i class="fas fa-exclamation-triangle"></i> Stok di gudang asal <strong>tidak cukup untuk {{ $kurang }} obat</strong> (ditandai merah). Transfer ini tidak bisa disetujui sebelum stoknya cukup; tolak bila perlu.</div>
    @else
        <div class="alert alert-warning small py-2 mb-3"><i class="fas fa-hourglass-half"></i> Menunggu approval. <strong>Stok belum berpindah</strong> sampai transfer disetujui. Stok diambil dari batch layak pakai dengan ED terdekat (FEFO).</div>
    @endif
    @if($jumlahEd > 0)
        <div class="alert alert-danger small py-2 mb-3"><i class="fas fa-calendar-times"></i> <strong>{{ $jumlahEd }} obat ikut memindahkan stok yang sudah ED</strong> (ditandai <span class="badge badge-danger">ED</span>). Batch ED pindah beserta tanggal ED-nya; pastikan memang disengaja.</div>
    @endif
@elseif($mutasi->status === 'approved')
    <div class="alert alert-success small py-2 mb-3"><i class="fas fa-check-circle"></i> Disetujui oleh <strong>{{ optional($mutasi->approvedBy)->name ?? '-' }}</strong>@if($mutasi->approved_at) pada {{ $mutasi->approved_at->format('d/m/Y H:i') }}@endif. Stok sudah berpindah.</div>
@elseif($mutasi->status === 'rejected')
    <div class="alert alert-danger small py-2 mb-3"><i class="fas fa-times-circle"></i> Ditolak oleh <strong>{{ optional($mutasi->rejectedBy)->name ?? '-' }}</strong>@if($mutasi->rejected_at) pada {{ $mutasi->rejected_at->format('d/m/Y H:i') }}@endif. Stok tidak berubah.@if($alasanTolak)<br>Alasan: {{ $alasanTolak }}@endif</div>
@endif

<div class="table-responsive">
    <table class="table table-bordered table-sm mb-2">
        <thead class="thead-light">
            <tr>
                <th style="width:40px">#</th>
                <th>Nama Obat</th>
                <th class="text-right" style="width:130px">Jumlah</th>
                @if($stokTersedia !== null)
                    <th class="text-right" style="width:150px">Stok di asal</th>
                @endif
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $i => $item)
                @php
                    $satuan = $item->obat->satuan_stok_label ?? '';
                    $tersedia = $stokTersedia !== null ? (float) ($stokTersedia[$item->obat_id] ?? 0) : null;
                    $tidakCukup = $tersedia !== null && round($tersedia, 4) < round((float) $item->jumlah, 4);
                @endphp
                <tr class="{{ $tidakCukup ? 'table-danger' : '' }}">
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->obat->nama ?? ('Obat ID ' . $item->obat_id) }}
                        @if((float) ($edTerpakai[$item->obat_id] ?? 0) > 0)
                            <span class="badge badge-danger" title="Bagian jumlah ini diambil dari batch yang sudah ED">ED {{ $fmt($edTerpakai[$item->obat_id]) }} {{ $satuan }}</span>
                        @endif
                    </td>
                    <td class="text-right font-weight-bold">{{ $fmt($item->jumlah) }} {{ $satuan }}</td>
                    @if($stokTersedia !== null)
                        <td class="text-right {{ $tidakCukup ? 'text-danger font-weight-bold' : 'text-muted' }}">{{ $fmt($tersedia) }} {{ $satuan }}</td>
                    @endif
                    <td>{{ $item->keterangan ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if($mutasi->keterangan)
    <div class="small"><strong>Catatan:</strong> {{ $mutasi->keterangan }}</div>
@endif
