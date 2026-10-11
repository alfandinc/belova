@extends('layouts.erm.app')
@section('title', 'Farmasi | Retur ke Supplier')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection
@section('content')
<div class="container-fluid">
    <style>
        .rt-tabs .nav-link { padding: .35rem .8rem; font-size: .875rem; }
        #returTable tr.rt-pending td:first-child { box-shadow: inset 3px 0 0 #ffc107; }
        #returTable tr { cursor: pointer; }
    </style>

    <div class="d-flex flex-wrap align-items-center justify-content-between mt-3 mb-3">
        <div>
            <h4 class="page-title mb-0">Retur ke Supplier</h4>
            <small class="text-muted">Kembalikan barang dari faktur yang sudah disetujui. Stok berkurang setelah retur disetujui.</small>
        </div>
        <button class="btn btn-primary mt-2 mt-md-0" id="btn-create-retur"><i class="fa fa-plus"></i> Ajukan Retur</button>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start">
                <ul class="nav nav-pills rt-tabs mb-3" id="status-tabs">
                    <li class="nav-item"><a class="nav-link active" href="#" data-status="">Semua <span class="badge badge-light ml-1" data-count="all">0</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-status="diajukan">Diajukan <span class="badge badge-warning ml-1" data-count="diajukan">0</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-status="diapprove">Disetujui <span class="badge badge-light ml-1" data-count="diapprove">0</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-status="ditolak">Ditolak <span class="badge badge-light ml-1" data-count="ditolak">0</span></a></li>
                </ul>
                <input type="search" id="searchRetur" class="form-control form-control-sm mb-3" style="max-width:300px" placeholder="Cari no retur, faktur, pemasok">
            </div>
            <table id="returTable" class="table table-bordered table-sm w-100">
                <thead class="thead-light">
                    <tr>
                        <th>No Retur</th>
                        <th>Tanggal</th>
                        <th>Faktur / Pemasok</th>
                        <th class="text-right">Item</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRetur" tabindex="-1" role="dialog" aria-labelledby="modalReturLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalReturLabel">Ajukan Retur ke Supplier</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" id="modalReturBody"></div>
        </div>
    </div>
</div>
<div class="modal fade" id="modalDetailRetur" tabindex="-1" role="dialog" aria-labelledby="modalDetailReturLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetailReturLabel">Detail Retur</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" id="modalDetailReturBody"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('erm.fakturretur._actions-js')
<script>
$(function() {
    var currentStatus = '';
    var STATUS_BADGE = { diajukan: 'badge-warning', menunggu_retur: 'badge-warning', diapprove: 'badge-success', ditolak: 'badge-danger' };

    var table = $('#returTable').DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 400,
        dom: 'rt<"d-flex flex-wrap justify-content-between align-items-center mt-2"lip>',
        pageLength: 25,
        order: [[1, 'desc']],
        ajax: {
            url: '{{ route('erm.fakturretur.index') }}',
            data: function(d) { d.status = currentStatus; }
        },
        createdRow: function(row, data) {
            $(row).attr('data-id', data.id);
            if (data.status === 'diajukan' || data.status === 'menunggu_retur') $(row).addClass('rt-pending');
        },
        columns: [
            { data: 'no_retur', name: 'no_retur', render: function(d) { return '<strong>' + d + '</strong>'; } },
            { data: 'tanggal_retur', name: 'tanggal_retur', render: function(d) { return d ? moment(d).format('DD/MM/YYYY') : '-'; } },
            { data: 'no_faktur', name: 'no_faktur', orderable: false, render: function(d, t, row) { return d + '<div class="small text-muted">' + row.pemasok_nama + '</div>'; } },
            { data: 'items_count', searchable: false, className: 'text-right', render: function(d, t, row) { return d + ' obat<div class="small text-muted">' + (row.total_qty || 0) + ' unit</div>'; } },
            { data: 'status', name: 'status', searchable: false, render: function(d, t, row) { return '<span class="badge ' + (STATUS_BADGE[d] || 'badge-secondary') + '">' + row.status_label + '</span>'; } },
            {
                data: null, orderable: false, searchable: false, className: 'text-right',
                render: function(d, t, row) {
                    return (row.status === 'diajukan' || row.status === 'menunggu_retur')
                        ? '<button class="btn btn-sm btn-warning btn-detail-retur" data-id="' + row.id + '"><i class="fas fa-clipboard-check"></i> Tinjau</button>'
                        : '<button class="btn btn-sm btn-outline-info btn-detail-retur" data-id="' + row.id + '"><i class="fas fa-eye"></i> Detail</button>';
                }
            },
            { data: 'pemasok_nama', name: 'pemasok_nama', visible: false }
        ],
        language: {
            processing: '<i class="fas fa-spinner fa-spin"></i> Memuat...',
            zeroRecords: 'Tidak ada retur yang cocok',
            emptyTable: 'Belum ada retur',
            info: '_START_–_END_ dari _TOTAL_ retur',
            infoEmpty: '0 retur',
            infoFiltered: '',
            lengthMenu: 'Tampil _MENU_',
            paginate: { previous: '‹', next: '›' }
        },
        drawCallback: function() {
            var counts = (this.api().ajax.json() || {}).counts || {};
            $('#status-tabs [data-count]').each(function() { $(this).text(counts[$(this).data('count')] || 0); });
        }
    });

    ReturActions.init(function() { table.draw(false); });

    $('#status-tabs').on('click', '.nav-link', function(e) {
        e.preventDefault();
        $('#status-tabs .nav-link').removeClass('active');
        $(this).addClass('active');
        currentStatus = $(this).data('status') || '';
        table.draw();
    });

    var searchTimer;
    $('#searchRetur').on('input', function() {
        var v = this.value;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() { table.search(v).draw(); }, 400);
    });

    $('#btn-create-retur').on('click', function() {
        $.get('{{ route('erm.fakturretur.create') }}').done(function(res) {
            $('#modalReturBody').html(res);
            $('#modalRetur').modal('show');
        }).fail(function() {
            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Form retur tidak dapat dimuat.' });
        });
    });

    function openDetail(id) {
        $.get('{{ url('/erm/fakturretur') }}/' + id).done(function(res) {
            $('#modalDetailReturBody').html(res);
            $('#modalDetailRetur').modal('show');
        }).fail(function() {
            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Detail retur tidak dapat dimuat.' });
        });
    }
    $(document).on('click', '.btn-detail-retur', function(e) {
        e.preventDefault();
        e.stopPropagation();
        openDetail($(this).data('id'));
    });
    $('#returTable tbody').on('click', 'tr[data-id]', function(e) {
        if ($(e.target).closest('a, button').length) return;
        openDetail($(this).data('id'));
    });
});
</script>
@endpush
