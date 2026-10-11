@extends('layouts.erm.app')

@section('title', 'Farmasi | Obat Hibah')

@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection

@section('content')
<div class="container-fluid">
    <style>
        .hb-tabs .nav-link { padding: .35rem .8rem; font-size: .875rem; }
        #hibah-table td { vertical-align: top; }
        #hibah-table tr.hb-pending td:first-child { box-shadow: inset 3px 0 0 #ffc107; }
    </style>

    <div class="d-flex flex-wrap align-items-center justify-content-between mt-3 mb-3">
        <div>
            <h4 class="page-title mb-0">Obat Hibah</h4>
            <small class="text-muted">Obat yang diterima tanpa pembelian. Stok masuk setelah disetujui Admin / Farmasi.</small>
        </div>
        <button type="button" class="btn btn-primary mt-2 mt-md-0" id="btnInputHibah"><i class="fa fa-plus"></i> Input Obat Hibah</button>
    </div>

    @php
        $tabs = ['' => 'Semua', 'diterima' => 'Menunggu approval', 'diapprove' => 'Disetujui', 'ditolak' => 'Ditolak'];
        $tabCount = function ($key) use ($counts) { return $key === '' ? (int) $counts->sum() : (int) ($counts[$key] ?? 0); };
        $badge = ['diterima' => 'badge-warning', 'diapprove' => 'badge-success', 'ditolak' => 'badge-danger'];
        $label = ['diterima' => 'Menunggu approval', 'diapprove' => 'Disetujui', 'ditolak' => 'Ditolak'];
    @endphp

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start">
                <ul class="nav nav-pills hb-tabs mb-3">
                    @foreach($tabs as $key => $text)
                        <li class="nav-item">
                            <a class="nav-link {{ (string) $status === (string) $key ? 'active' : '' }}" href="{{ route('erm.obat-hibah.index', array_filter(['status' => $key, 'q' => $q])) }}">
                                {{ $text }} <span class="badge {{ $key === 'diterima' ? 'badge-warning' : 'badge-light' }} ml-1">{{ $tabCount($key) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <form method="GET" action="{{ route('erm.obat-hibah.index') }}" class="mb-3" style="min-width:280px">
                    @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
                    <div class="input-group input-group-sm">
                        <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Cari nomor, sumber, atau nama obat">
                        <div class="input-group-append"><button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button></div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0" id="hibah-table">
                    <thead class="thead-light">
                        <tr>
                            <th>Nomor / Tanggal</th>
                            <th>Sumber</th>
                            <th>Obat</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($hibahs as $hibah)
                            <tr class="{{ $hibah->status === 'diterima' ? 'hb-pending' : '' }}">
                                <td>
                                    <div class="font-weight-bold">{{ $hibah->nomor_hibah }}</div>
                                    <div class="small text-muted">{{ \Carbon\Carbon::parse($hibah->received_date)->format('d/m/Y') }} · {{ $hibah->creator?->name ?? '-' }}</div>
                                    @if($hibah->bukti)
                                        <div class="small"><a href="{{ asset('storage/' . $hibah->bukti) }}" target="_blank"><i class="fas fa-paperclip"></i> bukti</a></div>
                                    @endif
                                </td>
                                <td>
                                    {{ $hibah->sumber ?: '-' }}
                                    @if($hibah->notes)<div class="small text-muted font-italic">{{ $hibah->notes }}</div>@endif
                                </td>
                                <td>
                                    @foreach($hibah->items as $item)
                                        <div class="small">
                                            <strong>{{ $item->obat?->nama ?? '-' }}</strong>
                                            · {{ rtrim(rtrim(number_format((float) $item->qty, 4, ',', '.'), '0'), ',') }}
                                            <span class="text-muted">({{ $item->gudang?->nama ?? '-' }}, batch {{ $item->batch ?: '-' }}{{ $item->expiration_date ? ', ED ' . \Carbon\Carbon::parse($item->expiration_date)->format('d/m/Y') : '' }})</span>
                                        </div>
                                    @endforeach
                                </td>
                                <td>
                                    <span class="badge {{ $badge[$hibah->status] ?? 'badge-secondary' }}">{{ $label[$hibah->status] ?? $hibah->status }}</span>
                                    @if($hibah->status === 'diapprove' && $hibah->approver)
                                        <div class="small text-muted">oleh {{ $hibah->approver->name }}{{ $hibah->approved_at ? ' · ' . \Carbon\Carbon::parse($hibah->approved_at)->format('d/m/Y H:i') : '' }}</div>
                                    @elseif($hibah->status === 'ditolak')
                                        <div class="small text-muted">{{ $hibah->rejecter ? 'oleh ' . $hibah->rejecter->name : '' }}</div>
                                        @if($hibah->rejected_reason)<div class="small text-danger">{{ $hibah->rejected_reason }}</div>@endif
                                    @endif
                                </td>
                                <td class="text-right text-nowrap">
                                    @if($hibah->status === 'diterima' && $canApprove)
                                        <button type="button" class="btn btn-success btn-sm btn-approve-hibah" data-url="{{ route('erm.obat-hibah.approve', $hibah->id) }}" data-nomor="{{ $hibah->nomor_hibah }}" data-items="{{ $hibah->items->count() }}"><i class="fas fa-check"></i> Setujui</button>
                                        <button type="button" class="btn btn-outline-danger btn-sm btn-reject-hibah" data-url="{{ route('erm.obat-hibah.reject', $hibah->id) }}" data-nomor="{{ $hibah->nomor_hibah }}"><i class="fas fa-times"></i></button>
                                    @elseif($hibah->status === 'diterima')
                                        <span class="small text-muted">menunggu Admin / Farmasi</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">{{ $q || $status ? 'Tidak ada obat hibah yang cocok.' : 'Belum ada data obat hibah.' }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $hibahs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('erm.obat-hibah._form-modal')
<script>
$(function () {
    var CSRF = '{{ csrf_token() }}';

    @if(session('success'))
        Swal.fire({ icon: 'success', title: 'Berhasil', text: @json(session('success')), timer: 2500, showConfirmButton: false });
    @endif
    @if(session('error'))
        Swal.fire({ icon: 'error', title: 'Gagal', text: @json(session('error')) });
    @endif

    function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }

    // Input form (modal); the list is server-rendered, so a save reloads the page
    $('#btnInputHibah').on('click', function () { HibahForm.open(); });
    HibahForm.onSaved(function (res) {
        Swal.fire({ icon: 'success', title: 'Tersimpan', text: res.message, timer: 1800, showConfirmButton: false })
            .then(function () { window.location = '{{ route('erm.obat-hibah.index', ['status' => 'diterima']) }}'; });
    });
    // The old /create link lands here with the modal to open
    if (new URLSearchParams(window.location.search).get('baru')) {
        HibahForm.open();
        window.history.replaceState({}, '', window.location.pathname);
    }
    function message(xhr, fallback) {
        var json = xhr && xhr.responseJSON;
        if (json && json.errors) return Object.values(json.errors).map(function (m) { return m[0]; }).join('\n');
        return (json && json.message) || fallback;
    }

    $(document).on('click', '.btn-approve-hibah', function () {
        var $b = $(this);
        Swal.fire({
            icon: 'question',
            title: 'Setujui ' + esc($b.data('nomor')) + '?',
            text: $b.data('items') + ' obat langsung masuk ke stok gudang (per batch) dan tercatat di kartu stok.',
            showCancelButton: true,
            confirmButtonText: 'Ya, setujui',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#28a745',
            reverseButtons: true
        }).then(function (result) {
            if (!result.value) return;
            $b.prop('disabled', true);
            $.ajax({ url: $b.data('url'), type: 'POST', data: { _token: CSRF }, headers: { Accept: 'application/json' } })
                .done(function (res) {
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 1800, showConfirmButton: false })
                        .then(function () { window.location.reload(); });
                })
                .fail(function (xhr) {
                    $b.prop('disabled', false);
                    Swal.fire({ icon: 'error', title: 'Gagal', text: message(xhr, 'Gagal menyetujui obat hibah.') });
                });
        });
    });

    $(document).on('click', '.btn-reject-hibah', function () {
        var $b = $(this);
        Swal.fire({
            icon: 'warning',
            title: 'Tolak ' + esc($b.data('nomor')) + '?',
            text: 'Stok tidak berubah.',
            input: 'textarea',
            inputPlaceholder: 'Alasan penolakan...',
            inputAttributes: { maxlength: 500 },
            inputValidator: function (value) { if (!value || !value.trim()) return 'Alasan penolakan wajib diisi.'; },
            showCancelButton: true,
            confirmButtonText: 'Tolak',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d33',
            reverseButtons: true
        }).then(function (result) {
            if (!result.value) return;
            $.ajax({ url: $b.data('url'), type: 'POST', data: { _token: CSRF, reason: result.value.trim() }, headers: { Accept: 'application/json' } })
                .done(function (res) {
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 1800, showConfirmButton: false })
                        .then(function () { window.location.reload(); });
                })
                .fail(function (xhr) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: message(xhr, 'Gagal menolak obat hibah.') });
                });
        });
    });
});
</script>
@endpush
