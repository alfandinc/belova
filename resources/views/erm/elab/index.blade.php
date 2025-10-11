@extends('layouts.erm.app')
@section('title', 'ERM | E-Laboratorium')
@section('navbar')
    @include('layouts.erm.navbar-lab')
@endsection

@section('content')

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item active">Laboratorium</li>
                            <li class="breadcrumb-item">E-Lab</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->

    <div class="card">
        <div class="card-header bg-primary">
            <h4 class="card-title text-white">Daftar Kunjungan Laboratorium</h4>
            @hasanyrole('Lab|Admin')
            <button class="btn btn-light btn-sm float-right" id="btn-show-canceled" style="margin-top:-30px;">Lihat Kunjungan Dibatalkan</button>
            @endhasanyrole
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-5">
                    <label for="filter_tanggal_range">Filter Rentang Tanggal Kunjungan</label>
                    <input type="text" id="filter_tanggal_range" class="form-control" autocomplete="off" placeholder="Pilih rentang tanggal">
                </div>
            </div>
            <table class="table table-bordered w-100" id="rawatjalan-table">
                <thead>
                    <tr>
                        <th>No RM</th>
                        <th>Nama Pasien</th>
                        <th>Detail Lab</th>
                        <th>Nominal</th>
                        <th>Tanggal Kunjungan</th>
                        <th>Metode Bayar</th>
                        <th>Lab</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
            </table>
            <!-- Total nominal card -->
            <div class="row mt-3">
                <div class="col-md-4 offset-md-8">
                    <div class="card border-primary">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Total Nominal</h6>
                                <small class="text-muted">(berdasarkan filter)</small>
                            </div>
                            <div>
                                <h5 id="total-nominal" class="mb-0">Rp 0</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal: Canceled Visitations -->
<div class="modal fade" id="modalCanceledVisitations" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kunjungan Dibatalkan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered w-100" id="canceled-table">
                        <thead>
                                <tr>
                                        <th>No RM</th>
                                        <th>Nama Pasien</th>
                                        <th>Tanggal</th>
                                        <th>Metode Bayar</th>
                                        <th>Aksi</th>
                                </tr>
                        </thead>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function () {
    // Style tweaks for the Detail Lab column
    var style = document.createElement('style');
    style.innerHTML = '\n        .lab-detail-list { max-width: 520px; white-space: normal; }\n        .lab-detail-column { white-space: normal; }\n        @media (max-width: 768px) { .lab-detail-list { max-width: 300px; } }\n    ';
    document.head.appendChild(style);

    // Actions column alignment: place left and right horizontally
    var actionStyle = document.createElement('style');
    actionStyle.innerHTML = '\n        .action-cell { display: flex; flex-direction: row; align-items: center; justify-content: space-between; gap: 8px; width: 100%; }\n        .action-cell .action-left { display: flex; align-items: center; justify-content: flex-start; }\n        .action-cell .action-right { display: flex; align-items: center; justify-content: flex-end; }\n        .action-cell .btn { display: inline-block; }\n    ';
    document.head.appendChild(actionStyle);
    // Date range picker for filter
    $('#filter_tanggal_range').daterangepicker({
        autoUpdateInput: true,
        locale: {
            format: 'YYYY-MM-DD',
            cancelLabel: 'Clear',
            applyLabel: 'Terapkan',
            fromLabel: 'Dari',
            toLabel: 'Sampai',
            customRangeLabel: 'Custom',
            daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
            monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            firstDay: 1
        },
        opens: 'left',
        startDate: moment().startOf('day'),
        endDate: moment().startOf('day'),
        maxDate: moment(),
    });

    let table = $('#rawatjalan-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        // Ensure the detail column is wider (index 2 after reordering)
        columnDefs: [
            { targets: 2, width: '35%', className: 'lab-detail-column' }
        ],
        order: [[4, 'desc']], // Order by tanggal_visitation column (index 4) in descending order
        ajax: {
            url: '{{ route("erm.elab.index") }}',
            data: function(d) {
                let range = $('#filter_tanggal_range').val();
                if (range) {
                    let dates = range.split(' - ');
                    d.tanggal_start = dates[0];
                    d.tanggal_end = dates[1] ? dates[1] : dates[0];
                }
            }
        },
        columns: [
            { data: 'no_rm', searchable: false, orderable: false },
            { data: 'nama_pasien', searchable: false, orderable: false },
            // New column: detail lab per visitation. Server may return html (string) or an array of objects
            { data: 'lab_details', searchable: false, orderable: false, render: function(data, type, row) {
                    if (!data) return '-';

                    // If server already returned HTML string, use it directly for display
                    if (typeof data === 'string') {
                        if (type === 'sort' || type === 'type') return data;
                        return data;
                    }

                    // If server returned an array of lab test objects, render a compact list
                    if (Array.isArray(data)) {
                        if (type === 'sort' || type === 'type') {
                            // For sorting, return concatenated test names
                            return data.map(function(item){ return item.nama || item.lab_test_nama || ''; }).join(', ');
                        }

                        // Display a small list with dates per test
                        moment.locale('id');
                        let html = '<div class="lab-detail-list">';
                        data.forEach(function(item){
                            // fields we might receive: nama, lab_test_nama, requested_at, processed_at, completed_at
                            let nama = item.nama || item.lab_test_nama || '-';
                            let req = item.requested_at || item.tanggal_diminta || item.tanggal_diminta_raw || null;
                            let proc = item.processed_at || item.tanggal_diproses || null;
                            let comp = item.completed_at || item.tanggal_selesai || null;

                            function fmt(d){
                                if (!d) return '-';
                                try { return moment(d).format('D MMMM YYYY'); } catch(e){ return d; }
                            }

                            html += '<div class="mb-1">'
                                + '<strong>' + escapeHtml(nama) + '</strong><br/>'
                                + '<small class="text-muted">Diminta: ' + fmt(req) + ' | Diproses: ' + fmt(proc) + ' | Selesai: ' + fmt(comp) + '</small>'
                            + '</div>';
                        });
                        html += '</div>';
                        return html;
                    }

                    // Fallback: stringify
                    return String(data);
                }
            },
            { data: 'nominal', searchable: false, orderable: false, render: function(data, type, row) {
                    // data is numeric nominal (sum of harga). Format to 'Rp 1.000'
                    if (!data) return 'Rp 0';
                    return 'Rp ' + Number(data).toLocaleString('id-ID');
                }
            },
            // Format tanggal_visitation using moment.js to 'D MMMM YYYY' (e.g. '1 Januari 2025')
            { 
                data: 'tanggal_visitation', 
                name: 'tanggal_visitation', 
                orderable: true,
                render: function(data, type, row) {
                    // For sorting, return the raw date value
                    if (type === 'sort' || type === 'type') {
                        return data;
                    }
                    
                    // For display, format the date
                    if (!data) return '-';
                    // Ensure moment has Indonesian locale available; format and capitalize month
                    try {
                        moment.locale('id');
                        let formatted = moment(data).format('D MMMM YYYY');
                        // Capitalize first letter of month (moment with 'id' returns lowercase months)
                        // e.g. '1 januari 2025' -> '1 Januari 2025'
                        return formatted.replace(/\b([a-z])/g, function(m) { return m.toUpperCase(); });
                    } catch (e) {
                        return data;
                    }
                }
            },
            { data: 'metode_bayar', searchable: false, orderable: false },
            // keep dokumen data available on the row but don't render as a separate column
            { data: 'dokumen', searchable: false, orderable: false, visible: false },
            { data: null, searchable: false, orderable: false, render: function(data, type, row) {
                    // row.dokumen may contain HTML for the 'Lihat' button from the server
                    let docHtml = row.dokumen || '';
                    let disabled = (row.status_kunjungan == 7) ? 'disabled' : '';
                    let title = (row.status_kunjungan == 7) ? 'Sudah dibatalkan' : 'Batalkan kunjungan';
                    let cancelBtn = '<button class="btn btn-sm btn-outline-danger btn-cancel-visitation" data-id="'+ (row.id || '') +'" '+disabled+' title="'+ title +'">Cancel</button>';
                    // wrap docHtml and cancel button in left/right containers for horizontal alignment
                    let left = '<div class="action-left">' + (docHtml || '') + '</div>';
                    let right = '<div class="action-right">' + cancelBtn + '</div>';
                    let wrapper = '<div class="action-cell">' + left + right + '</div>';
                    return wrapper;
                }
            },
            { data: 'status_kunjungan', visible: false, searchable: false },
        ],
        createdRow: function(row, data, dataIndex) {
            if (data.status_kunjungan == 2) {
                $(row).css('color', 'orange');
            }
        }
    });

    // Helper to format number to Indonesian Rupiah
    function formatRupiah(number) {
        if (!number) return 'Rp 0';
        return 'Rp ' + Number(number).toLocaleString('id-ID');
    }

    // Small helper to escape HTML to avoid XSS when inserting strings as HTML
    function escapeHtml(string) {
        if (string === null || string === undefined) return '';
        return String(string).replace(/[&<>",']/g, function (s) {
            return ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            })[s];
        });
    }

    // Update total nominal display. Prefer server-provided aggregated total if available
    function updateTotalNominal(json) {
        // If server returned aggregated total (total_nominal), use it
        if (json && json.total_nominal !== undefined && json.total_nominal !== null) {
            $('#total-nominal').text(formatRupiah(json.total_nominal));
            return;
        }

        // Otherwise, sum the nominal column from the currently displayed rows
        let api = table.api ? table.api() : table; // support different DataTables init
        let data = api.rows({ page: 'current' }).data();
        let sum = 0;
        for (let i = 0; i < data.length; i++) {
            let val = data[i].nominal;
            if (!val) continue;
            // ensure numeric
            let num = Number(val);
            if (!isNaN(num)) sum += num;
        }
        $('#total-nominal').text(formatRupiah(sum));
    }

    // When table draws (paging, filter, sort, initial load), update total
    table.on('draw.dt', function(e, settings) {
        // If server-side processing with returned JSON, the draw event's settings.json may contain our payload
        // DataTables exposes the last JSON in settings.json
        let json = settings.json || null;
        updateTotalNominal(json);
    });

    // Also intercept the ajax response to capture server-provided total_nominal when available
    $.fn.dataTable.ext.errMode = 'throw';
    // If using DataTables ajax option as object, we can provide a dataSrc wrapper
    // Instead, attach a global ajax handler for this specific table's ajax
    $(document).on('xhr.dt', '#rawatjalan-table', function(e, settings, json, xhr) {
        updateTotalNominal(json);
    });

    // Event ganti rentang tanggal
    $('#filter_tanggal_range').on('apply.daterangepicker cancel.daterangepicker', function(ev, picker) {
        table.ajax.reload();
    });

    // Cancel visitation handler
    $(document).on('click', '.btn-cancel-visitation', function(){
        const id = $(this).data('id');
        if(!id) return;
        if(!confirm('Batalkan kunjungan ini?')) return;
        $.ajax({
            url: '{{ route('erm.elab.visitation.cancel', ['id' => '___ID___']) }}'.replace('___ID___', id),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(res){
                table.ajax.reload(null,false);
            },
            error: function(xhr){
                alert(xhr.responseJSON?.message || 'Gagal membatalkan kunjungan');
            }
        });
    });

    // Show canceled visits modal
    let canceledTableInitialized = false;
    $('#btn-show-canceled').on('click', function(){
        $('#modalCanceledVisitations').modal('show');
        if(!canceledTableInitialized){
            $('#canceled-table').DataTable({
                processing:true, serverSide:true, responsive:true,
                ajax: '{{ route('erm.elab.canceled.list') }}',
                order: [[2,'desc']],
                columns:[
                    {data:'no_rm', orderable:false, searchable:false},
                    {data:'nama_pasien', orderable:false, searchable:false},
                    {data:'tanggal_visitation', name:'tanggal_visitation'},
                    {data:'metode_bayar', orderable:false, searchable:false},
                    {data:'actions', orderable:false, searchable:false}
                ]
            });
            canceledTableInitialized = true;
        } else {
            $('#canceled-table').DataTable().ajax.reload();
        }
    });

    // Restore visitation
    $(document).on('click', '.btn-restore-visitation', function(){
        const id = $(this).data('id');
        if(!confirm('Pulihkan kunjungan ini?')) return;
        $.ajax({
            url: '{{ route('erm.elab.visitation.restore', ['id' => '___ID___']) }}'.replace('___ID___', id),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(res){
                // Reload both tables
                table.ajax.reload(null,false);
                if(canceledTableInitialized){
                    $('#canceled-table').DataTable().ajax.reload(null,false);
                }
            },
            error: function(xhr){
                alert(xhr.responseJSON?.message || 'Gagal memulihkan kunjungan');
            }
        });
    });
});
</script>
@endsection