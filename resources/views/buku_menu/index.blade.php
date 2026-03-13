@extends('layouts.marketing.app')

@section('title', 'Buku Menu')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-0">Buku Menu</h4>
                        <small class="text-muted">Daftar Obat & Tindakan</small>
                    </div>
                    <a href="/" class="btn btn-outline-secondary btn-sm">Kembali</a>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="input-group" style="max-width:420px;">
                            <input id="buku-search" type="search" class="form-control form-control-sm" placeholder="Search Obat, Tindakan, Lab Test">
                            <div class="input-group-append">
                                <button id="buku-search-clear" class="btn btn-outline-secondary btn-sm" type="button">Clear</button>
                            </div>
                        </div>
                    </div>
                    <ul class="nav nav-tabs" id="bukuMenuTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="obat-tab" data-toggle="tab" href="#tab-obat" role="tab" aria-controls="tab-obat" aria-selected="true">Obat</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="tindakan-tab" data-toggle="tab" href="#tab-tindakan" role="tab" aria-controls="tab-tindakan" aria-selected="false">Tindakan</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="labtest-tab" data-toggle="tab" href="#tab-labtest" role="tab" aria-controls="tab-labtest" aria-selected="false">Lab Test</a>
                        </li>
                    </ul>

                    <div class="tab-content mt-3" id="bukuMenuTabsContent">
                        <div class="tab-pane fade show active" id="tab-obat" role="tabpanel" aria-labelledby="obat-tab">
                            <table id="obat-table" class="table table-bordered table-striped w-100">
                                <thead>
                                    <tr>
                                        <th style="width:60px;">No</th>
                                        <th>Nama Obat</th>
                                        <th style="width:120px;">Satuan</th>
                                        <th style="width:140px;">Stok Total</th>
                                        <th style="width:180px;">Harga Non Fornas</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <div class="tab-pane fade" id="tab-tindakan" role="tabpanel" aria-labelledby="tindakan-tab">
                                                <table id="tindakan-table" class="table table-bordered table-striped w-100">
                                                    <thead>
                                                        <tr>
                                                            <th style="width:60px;">No</th>
                                                            <th>Nama Tindakan</th>
                                                            <th style="width:220px;">Kode Tindakan</th>
                                                            <th style="width:160px;">Tipe</th>
                                                            <th style="width:220px;">List Harga</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                </table>
                        </div>
                        <div class="tab-pane fade" id="tab-labtest" role="tabpanel" aria-labelledby="labtest-tab">
                            <table id="labtest-table" class="table table-bordered table-striped w-100">
                                <thead>
                                    <tr>
                                        <th style="width:60px;">No</th>
                                        <th>Nama Lab Test</th>
                                        <th style="width:200px;">Kategori</th>
                                        <th style="width:160px;">Harga</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    var obatTable = $('#obat-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('buku-menu.data') }}',
        dom: 'lrtip',
        pageLength: 25,
        order: [[1, 'asc']],
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'nama', name: 'nama' },
            { data: 'satuan', name: 'satuan' },
            { data: 'total_stok', name: 'total_stok', searchable: false },
            { data: 'harga_nonfornas', name: 'harga_nonfornas', searchable: false },
        ]
    });

    var tindakanTable = $('#tindakan-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('buku-menu.tindakan-data') }}',
        dom: 'lrtip',
        pageLength: 25,
        order: [[1, 'asc']],
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'nama', name: 'nama' },
            { data: 'kode_tindakan_names', name: 'kode_tindakan_names', orderable: false, searchable: false },
            { data: 'jenis_harga', name: 'jenis_harga', orderable: false, searchable: false },
            { data: 'list_harga', name: 'list_harga', orderable: false, searchable: false },
        ],
        columnDefs: [
            { targets: 2, className: 'text-left' },
            { targets: 3, className: 'text-left' },
            { targets: 1, className: 'text-left' }
        ]
    });

    var labtestTable = $('#labtest-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('buku-menu.labtest-data') }}',
        dom: 'lrtip',
        pageLength: 25,
        order: [[1, 'asc']],
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'nama', name: 'nama' },
            { data: 'kategori', name: 'kategori', orderable: false, searchable: false },
            { data: 'harga', name: 'harga', orderable: false, searchable: false },
        ],
        columnDefs: [
            { targets: 3, className: 'text-left' },
            { targets: 1, className: 'text-left' }
        ]
    });

    // Fix DataTables column sizing when a table is initialized in a hidden tab
    $('a[data-toggle="tab"]').on('shown.bs.tab', function () {
        obatTable.columns.adjust();
        tindakanTable.columns.adjust();
        labtestTable.columns.adjust();
    });

    // Keep track of currently visible/active table for global search
    var currentTable = obatTable;
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr('href');
        if (target === '#tab-obat') currentTable = obatTable;
        else if (target === '#tab-tindakan') currentTable = tindakanTable;
        else if (target === '#tab-labtest') currentTable = labtestTable;
    });

    // Debounce helper
    function debounce(fn, delay) {
        var timer = null;
        return function () {
            var context = this, args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(context, args); }, delay);
        };
    }

    // Wire global search input to current table
    $('#buku-search').on('input', debounce(function () {
        var v = $(this).val();
        if (currentTable) {
            currentTable.search(v).draw();
        }
    }, 300));

    $('#buku-search-clear').on('click', function () {
        $('#buku-search').val('');
        if (currentTable) { currentTable.search('').draw(); }
    });
});
</script>
@endpush

@push('styles')
<style>
/* Price block layout used in Tindakan view */
.price-block { width: 100%; }
.price-block .row { display: block; padding: 2px 8px; border-top: 1px solid #f1f3f5; }
.price-block .row:first-child { border-top: none; }
.price-block .label { color: #6c757d; display: block; }
.price-block .value { font-weight: 600; display: block; text-align: left; }
.price-block .value span { display: inline-block; }
/* Specific styling for list_harga: original (left, red and crossed), active (right, bold) */
.list-harga .price-row { display: flex; justify-content: space-between; align-items: center; padding: 2px 8px; border-top: 1px solid #f1f3f5; }
.list-harga .price-row:first-child { border-top: none; }
.list-harga .original-price { color: #d9534f; text-decoration: line-through; margin-right: 8px; }
.list-harga .active-price { font-weight: 700; text-align: right; }
/* kode tindakan list bullets */
.kode-list { margin: 0; padding-left: 18px; }
.kode-list li { list-style-type: disc; margin: 0; padding: 0; color: #333; }
</style>
@endpush
