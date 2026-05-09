@extends('layouts.erm.app')

@section('title', 'ERM | Obat Hibah')

@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mt-3 align-items-center mb-2">
        <div class="col-md-6">
            <h2 class="mb-0">Obat Hibah</h2>
        </div>
        <div class="col-md-6 text-md-right mt-2 mt-md-0">
            <a href="{{ route('erm.obat-hibah.create') }}" class="btn btn-primary">
                <i class="fa fa-plus"></i> Input Obat Hibah
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box py-1 mb-3">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Farmasi</li>
                            <li class="breadcrumb-item active">Obat Hibah</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nomor Hibah</th>
                            <th>Tanggal Terima</th>
                            <th>Sumber</th>
                            <th>Bukti</th>
                            <th>Status</th>
                            <th>Item</th>
                            <th>Input Oleh</th>
                            <th>Catatan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($hibahs as $hibah)
                            <tr data-hibah-id="{{ $hibah->id }}">
                                <td>{{ $hibahs->firstItem() + $loop->index }}</td>
                                <td>{{ $hibah->nomor_hibah }}</td>
                                <td>{{ \Carbon\Carbon::parse($hibah->received_date)->format('d/m/Y') }}</td>
                                <td>{{ $hibah->sumber ?: '-' }}</td>
                                <td>
                                    @if($hibah->bukti)
                                        <a href="{{ asset('storage/' . $hibah->bukti) }}" target="_blank">Lihat Bukti</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="hibah-status-cell">
                                    @php
                                        $badgeClass = $hibah->status === 'diapprove' ? 'badge-success' : 'badge-warning text-dark';
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ ucfirst($hibah->status) }}</span>
                                    @if($hibah->approver)
                                        <div class="small text-muted mt-1">Approve: {{ $hibah->approver->name }}</div>
                                    @endif
                                </td>
                                <td>
                                    @foreach($hibah->items as $item)
                                        <div>
                                            {{ $item->obat?->nama ?? '-' }} - {{ rtrim(rtrim(number_format((float) $item->qty, 4, '.', ''), '0'), '.') }}
                                            @if($item->gudang)
                                                ({{ $item->gudang->nama }})
                                            @endif
                                            @if($item->batch)
                                                | Batch: {{ $item->batch }}
                                            @endif
                                        </div>
                                    @endforeach
                                </td>
                                <td>{{ $hibah->creator?->name ?? '-' }}</td>
                                <td>{{ $hibah->notes ?: '-' }}</td>
                                <td class="hibah-action-cell">
                                    @if($hibah->status === 'diterima')
                                        <button
                                            type="button"
                                            class="btn btn-success btn-sm btn-approve-hibah"
                                            data-id="{{ $hibah->id }}"
                                            data-approve-url="{{ route('erm.obat-hibah.approve', $hibah->id) }}"
                                        >
                                            Approve
                                        </button>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">Belum ada data obat hibah.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $hibahs->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    @if(session('success'))
        Swal.fire('Berhasil', @json(session('success')), 'success');
    @endif

    @if(session('error'))
        Swal.fire('Gagal', @json(session('error')), 'error');
    @endif

    $(document).on('click', '.btn-approve-hibah', function () {
        var $button = $(this);
        var approveUrl = $button.data('approve-url');
        var hibahId = $button.data('id');

        Swal.fire({
            title: 'Konfirmasi',
            text: 'Yakin ingin menyetujui obat hibah ini? Stok akan ditambahkan ke gudang.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Setujui',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.value) {
                return;
            }

            Swal.fire({
                title: 'Memproses...',
                text: 'Approval obat hibah sedang diproses.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: function () {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: approveUrl,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                beforeSend: function () {
                    $button.prop('disabled', true).text('Processing...');
                },
                success: function (res) {
                    if (!res.success) {
                        Swal.fire('Gagal', res.message || 'Terjadi kesalahan', 'error');
                        $button.prop('disabled', false).text('Approve');
                        return;
                    }

                    var $row = $('tr[data-hibah-id="' + hibahId + '"]');
                    var statusHtml = '<span class="badge badge-success">' + (res.data.status_label || 'Diapprove') + '</span>';
                    if (res.data.approver_name) {
                        statusHtml += '<div class="small text-muted mt-1">Approve: ' + $('<div>').text(res.data.approver_name).html() + '</div>';
                    }

                    $row.find('.hibah-status-cell').html(statusHtml);
                    $row.find('.hibah-action-cell').html('<span class="text-muted">-</span>');

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: res.message,
                        timer: 1800,
                        showConfirmButton: false
                    });
                },
                error: function (xhr) {
                    Swal.fire('Gagal', xhr.responseJSON?.message || 'Gagal menyetujui obat hibah.', 'error');
                    $button.prop('disabled', false).text('Approve');
                }
            });
        });
    });
});
</script>
@endpush