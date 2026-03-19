@extends('layouts.erm.app')
@section('title', 'ERM | FakturPembelian')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection  
@section('content')
<div class="container-fluid">
            <!-- Page-Title -->
    <!-- Title and Button Row -->
    <div class="row mt-3 align-items-center mb-1">
        <div class="col-md-4">
            <h2 class="mb-0">Input Faktur Pembelian</h2>
        </div>
        <div class="col-md-8">
            <div class="d-flex flex-wrap justify-content-md-end align-items-center gap-2">
                <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalCariPermintaan"><i class="fa fa-search"></i> Cari Faktur Berdasarkan No Permintaan</button>
                <button class="btn btn-success btn-sm ml-2" data-toggle="modal" data-target="#modalExportItems"><i class="fa fa-download"></i> Download Item Faktur (Excel)</button>
                <div class="input-group input-group-sm ml-md-3" style="width: 260px;">
                    <div class="input-group-prepend">
                        <span class="input-group-text" title="Pilih tanggal terima">
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>
                    <input type="text" id="tanggalTerimaRange" class="form-control form-control-sm" autocomplete="off" placeholder="Select date">
                </div>
                <button class="btn btn-secondary btn-sm ml-2" id="resetTanggalTerima">Reset</button>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box py-1 mb-2">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Farmasi</li>
                            <li class="breadcrumb-item active">Input Faktur Pembelian</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
        <!-- end page title end breadcrumb -->
        <div class="mb-3">
                <div class="d-flex flex-wrap align-items-end justify-content-between">
                    <div class="d-flex flex-wrap align-items-end">
                        <div class="mr-4 mb-2">
                            <label for="statusFilter" class="d-block mb-1 font-weight-bold text-uppercase small">Status</label>
                            <select id="statusFilter" class="form-control" style="width:150px;">
                                <option value="">Semua</option>
                                <option value="diminta">Diminta</option>
                                <option value="diterima">Diterima</option>
                                <option value="diapprove">Diapprove</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <span class="d-block mb-1 font-weight-bold text-uppercase small text-muted">Hide</span>
                            <div class="d-flex flex-wrap align-items-center">
                                <div class="custom-control custom-switch mr-3" title="Sembunyikan faktur diretur">
                                    <input class="custom-control-input" type="checkbox" id="hideDireturCheckbox" checked>
                                    <label class="custom-control-label small pt-1" for="hideDireturCheckbox">Diretur</label>
                                </div>
                                <div class="custom-control custom-switch" title="Sembunyikan faktur belum approve lebih dari 7 hari">
                                    <input class="custom-control-input" type="checkbox" id="hideUnapprovedOver7DaysCheckbox" checked>
                                    <label class="custom-control-label small pt-1" for="hideUnapprovedOver7DaysCheckbox">Pending &gt; 7 Hari</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-end mb-2">
                        <input type="text" id="searchNoFaktur" class="form-control ml-md-2 mb-2 mb-md-0" style="width:180px;" placeholder="Cari No Faktur">
                        <input type="text" id="searchNamaObat" class="form-control ml-md-2 mb-2 mb-md-0" style="width:180px;" placeholder="Cari Nama Obat">
                        <input type="text" id="searchPemasok" class="form-control ml-md-2" style="width:180px;" placeholder="Cari Pemasok">
                    </div>
                </div>
        </div>
        <!-- Modal Cari Permintaan -->
        <div class="modal fade" id="modalCariPermintaan" tabindex="-1" role="dialog" aria-labelledby="modalCariPermintaanLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalCariPermintaanLabel">Cari Faktur Berdasarkan No Permintaan</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="formCariPermintaan">
                            <div class="form-group">
                                <label for="inputNoPermintaan">No Permintaan</label>
                                <input type="text" class="form-control" id="inputNoPermintaan" name="no_permintaan" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                        <button type="button" class="btn btn-primary" id="btnCariPermintaan">Cari</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal Export Items -->
        <div class="modal fade" id="modalExportItems" tabindex="-1" role="dialog" aria-labelledby="modalExportItemsLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalExportItemsLabel">Export Item Faktur - Pilih Rentang Tanggal Terima</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="formExportItems" method="GET" action="{{ route('erm.fakturbeli.items.export') }}">
                            <div class="form-group">
                                <label for="exportTanggalTerimaRange">Tanggal Terima (Range)</label>
                                <input type="text" id="exportTanggalTerimaRange" name="tanggal_terima_range" class="form-control" placeholder="Pilih rentang tanggal" autocomplete="off">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                        <button type="button" class="btn btn-success" id="btnExportItems">Download</button>
                    </div>
                </div>
            </div>
        </div>
        <table class="table table-bordered" id="fakturbeli-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>No Faktur</th>
                        <th>Nama Obat</th>
                        <th>Pemasok</th>
                        <th>Timeline</th>
                        <th>Total Harga</th>
                        <th>Status</th>
                        <th style="width: 280px; min-width: 260px; max-width: 320px;">Aksi</th>
                    </tr>
                </thead>
            </table>
</div>
@endsection
@push('scripts')
<script>
$(function() {
    function formatTanggalIndonesia(value) {
        if (!value || value === '-') {
            return '-';
        }

        var parsed = moment(value, ['YYYY-MM-DD', moment.ISO_8601], true);
        if (!parsed.isValid()) {
            parsed = moment(value);
        }

        if (!parsed.isValid()) {
            return value;
        }

        return parsed.locale('id').format('D MMMM YYYY');
    }

    // Handler for cari permintaan
    $('#btnCariPermintaan').on('click', function() {
        var noPermintaan = $('#inputNoPermintaan').val().trim();
        if (!noPermintaan) {
            Swal.fire('Error', 'No Permintaan harus diisi', 'error');
            return;
        }
        // Cari faktur dengan no_permintaan
        $.ajax({
            url: '/erm/fakturpembelian/cari-by-no-permintaan',
            type: 'GET',
            data: { no_permintaan: noPermintaan },
            success: function(res) {
                if (res.success && res.faktur_id) {
                    // Redirect to edit page
                    window.location.href = '/erm/fakturpembelian/' + res.faktur_id + '/edit';
                } else {
                    Swal.fire('Tidak ditemukan', res.message || 'Faktur dengan no permintaan tersebut tidak ditemukan', 'warning');
                }
            },
            error: function() {
                Swal.fire('Error', 'Terjadi kesalahan saat mencari faktur', 'error');
            }
        });
    });
    var fakturTable = $('#fakturbeli-table').DataTable({
    processing: true,
    serverSide: true,
    dom: 'rt<"row align-items-center mt-3"<"col-sm-12 col-md-4"l><"col-sm-12 col-md-4 text-center"i><"col-sm-12 col-md-4 d-flex justify-content-md-end"p>>',
    ajax: {
            url: '{{ route('erm.fakturbeli.index') }}',
            data: function(d) {
                d.tanggal_terima_range = $('#tanggalTerimaRange').val();
                d.status = $('#statusFilter').val();
                d.hide_diretur = $('#hideDireturCheckbox').is(':checked') ? 1 : 0;
                d.hide_unapproved_over_7_days = $('#hideUnapprovedOver7DaysCheckbox').is(':checked') ? 1 : 0;
                d.search_no_faktur = $('#searchNoFaktur').val();
                d.search_nama_obat = $('#searchNamaObat').val();
                d.search_pemasok = $('#searchPemasok').val();
            }
            },
        order: [[4, 'desc']], // timeline column (backed by requested_date)
        columns: [
            { data: null, name: 'no', orderable: false, searchable: false, render: function (data, type, row, meta) {
                return meta.row + meta.settings._iDisplayStart + 1;
            }},
            { data: 'no_faktur', name: 'no_faktur', render: function(data, type, row) {
                if (type !== 'display') {
                    return data ? data : '-';
                }

                var noFaktur = data ? data : '-';
                var buktiLink = row.bukti
                    ? `<div class="mt-1"><a href='/storage/${row.bukti}' target='_blank'><em>Lihat Bukti</em></a></div>`
                    : '';

                return `<div>${noFaktur}${buktiLink}</div>`;
            }},
            { data: 'nama_obat', name: 'nama_obat', orderable: false, searchable: false },
            { data: 'pemasok', name: 'pemasok' },
            { data: 'requested_date', name: 'requested_date', render: function(data, type, row) {
                if (type !== 'display') {
                    return data || row.received_date || row.due_date || '';
                }

                var requested = formatTanggalIndonesia(row.requested_date ? row.requested_date : '-');
                var received = formatTanggalIndonesia(row.received_date ? row.received_date : '-');
                var due = formatTanggalIndonesia(row.due_date ? row.due_date : '-');
                var status = (row.status || '').toString().toLowerCase();
                var lines = [];

                function pushLine(label, value, color) {
                    lines.push('<div><strong style="color:' + color + ';">' + label + '</strong> <span style="color:' + color + ';">' + value + '</span></div>');
                }

                if (status === 'diminta') {
                    pushLine('Diminta Pada:', requested, '#f59e0b');
                } else if (status === 'diapprove') {
                    pushLine('Diterima Pada:', received, '#2563eb');
                    pushLine('Jatuh Tempo Pada:', due, '#dc2626');
                } else if (status === 'diterima') {
                    pushLine('Diminta Pada:', requested, '#f59e0b');
                    pushLine('Diterima Pada:', received, '#2563eb');
                } else {
                    pushLine('Diminta Pada:', requested, '#f59e0b');
                    if (received !== '-') {
                        pushLine('Diterima Pada:', received, '#2563eb');
                    }
                    if (due !== '-') {
                        pushLine('Jatuh Tempo Pada:', due, '#dc2626');
                    }
                }

                return lines.join('');
            }},
            { data: 'total', name: 'total', render: function(data) {
                return data ? parseFloat(data).toLocaleString('id-ID', {style:'currency', currency:'IDR'}) : '-';
            }},
            { data: 'status', name: 'status', render: function(data, type, row) {
                let badgeClass = '';
                let approvedBy = '';
                switch(data) {
                    case 'diminta': badgeClass = 'badge-warning'; break;
                    case 'diterima': badgeClass = 'badge-info'; break;
                    case 'diapprove': badgeClass = 'badge-success';
                        if (row.approved_by_user_name) {
                            approvedBy = `<br><small class='text-success'>Approved by: ${row.approved_by_user_name}</small>`;
                        }
                        break;
                    case 'diretur': badgeClass = 'badge-danger'; break;
                    default: badgeClass = 'badge-secondary'; break;
                }
                return `<span class="badge ${badgeClass}">${data}</span>${approvedBy}`;
            }},
            { data: 'action', name: 'action', orderable: false, searchable: false, render: function(data, type, row) {
                if (row.status === 'diretur') {
                    return '';
                }
                return data;
            } },
        ],
        columnDefs: [
            { targets: 4, width: '260px' },
            { targets: -1, width: '280px' }
        ]
    });

        // Date Range Picker for Tanggal Terima
        $('#tanggalTerimaRange').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear',
                format: 'YYYY-MM-DD'
            }
        });

    $('#tanggalTerimaRange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        fakturTable.ajax.reload();
    });
    $('#tanggalTerimaRange').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
        fakturTable.ajax.reload();
    });
    $('#resetTanggalTerima').on('click', function() {
        $('#tanggalTerimaRange').val('');
        fakturTable.ajax.reload();
    });

    // Status filter handler
    $('#statusFilter').on('change', function() {
        fakturTable.ajax.reload();
    });
    // Hide diretur filter handler
    $('#hideDireturCheckbox').on('change', function() {
        fakturTable.ajax.reload();
    });
    $('#hideUnapprovedOver7DaysCheckbox').on('change', function() {
        fakturTable.ajax.reload();
    });
    // Debounce helper to avoid excessive reloads
    function debounce(fn, delay) {
        let timer = null;
        return function() {
            const ctx = this, args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function() {
                fn.apply(ctx, args);
            }, delay);
        };
    }

    // Custom search handlers (include searchNoFaktur)
    $('#searchNoFaktur, #searchNamaObat, #searchPemasok').on('input', debounce(function() {
        fakturTable.ajax.reload();
    }, 300));

    // Also allow Enter key on No Faktur to immediately trigger search
    $('#searchNoFaktur').on('keyup', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            fakturTable.ajax.reload();
        }
    });
    // Export modal: initialize daterangepicker and handle submit
    $('#exportTanggalTerimaRange').daterangepicker({
        autoUpdateInput: false,
        locale: {
            cancelLabel: 'Clear',
            format: 'YYYY-MM-DD'
        }
    });
    $('#exportTanggalTerimaRange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
    });
    $('#exportTanggalTerimaRange').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
    });

    $('#btnExportItems').on('click', function() {
        var range = $('#exportTanggalTerimaRange').val().trim();
        if (!range) {
            Swal.fire('Error', 'Pilih rentang tanggal terima terlebih dahulu', 'error');
            return;
        }
        // Submit the GET form which will trigger the XLSX download
        $('#formExportItems').submit();
        $('#modalExportItems').modal('hide');
    });
    // Delete handler
    $('#fakturbeli-table').on('click', '.btn-delete-faktur', function() {
        if(confirm('Yakin ingin menghapus faktur ini?')) {
            let id = $(this).data('id');
            $.ajax({
                url: '/erm/fakturpembelian/' + id,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(res) {
                    if(res.success) {
                        alert(res.message);
                        fakturTable.ajax.reload();
                    }
                },
                error: function() {
                    alert('Gagal menghapus faktur!');
                }
            });
        }
    });
    
    // Approve handler
    $('#fakturbeli-table').on('click', '.btn-approve-faktur', function() {
        let id = $(this).data('id');
        Swal.fire({
            title: 'Konfirmasi',
            text: 'Yakin ingin menyetujui faktur ini? Stok obat akan diperbarui.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Setujui',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: '{{ url("/erm/fakturpembelian") }}/' + id + '/approve',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        if(res.success) {
                            Swal.fire('Berhasil', res.message, 'success');
                            fakturTable.ajax.reload();
                        } else {
                            Swal.fire('Gagal', res.message || 'Terjadi kesalahan', 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal', 'Gagal menyetujui faktur: ' + (xhr.responseJSON?.message || 'Terjadi kesalahan'), 'error');
                    }
                });
            }
        });
    });
    
    // Debug HPP handler
    $('#fakturbeli-table').on('click', '.btn-debug-hpp', function() {
        let id = $(this).data('id');
        $.ajax({
            url: '{{ url("/erm/fakturpembelian") }}/' + id + '/debug-hpp',
            type: 'GET',
            success: function(res) {
                if(res.success) {
                    // Format the data for better readability
                    let debugInfo = '';
                    debugInfo += `<h4>Faktur Info</h4>`;
                    debugInfo += `<p>No Faktur: ${res.faktur.no_faktur || '-'}</p>`;
                    debugInfo += `<p>Subtotal: Rp${parseFloat(res.faktur.subtotal || 0).toLocaleString('id-ID')}</p>`;
                    debugInfo += `<p>Global Diskon: Rp${parseFloat(res.faktur.global_diskon || 0).toLocaleString('id-ID')}</p>`;
                    debugInfo += `<p>Global Pajak: Rp${parseFloat(res.faktur.global_pajak || 0).toLocaleString('id-ID')}</p>`;
                    debugInfo += `<p>Total: Rp${parseFloat(res.faktur.total || 0).toLocaleString('id-ID')}</p>`;
                    debugInfo += `<p>Calculated Subtotal: Rp${parseFloat(res.faktur.invoiceSubtotalCalculated || 0).toLocaleString('id-ID')}</p>`;
                    // Keep the items table minimal: only columns necessary for the HPP formula
                    debugInfo += `<h4>Items Info</h4>`;
                    debugInfo += `<table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Obat</th>
                                <th>Old Stock</th>
                                <th>Qty (received)</th>
                                <th>Purchase Cost<br><small class="text-muted">(total)</small></th>
                                <!-- HPP group side-by-side -->
                                <th>Old HPP<br><small class="text-muted">(Rp/unit)</small></th>
                                <th>HPP/unit<br><small class="text-muted">(purchase)</small></th>
                                <th>New HPP<br><small class="text-muted">(Rp/unit)</small></th>
                                <!-- Selling HPP (Jual) -->
                                <th>HPP Jual/unit<br><small class="text-muted">(selling)</small></th>
                                <th>New HPP Jual<br><small class="text-muted">(Rp/unit)</small></th>
                                <th>New Stock</th>
                                <th>Formula (concise)</th>
                            </tr>
                        </thead>
                        <tbody>`;
                    res.items.forEach(item => {
                        // Build human-friendly formatted numbers
                        const fmt = (v) => 'Rp' + parseFloat(v || 0).toLocaleString('id-ID');
                        const num = (v) => (v === null || v === undefined) ? '-' : v;

                        // New direct-set formula (no averaging): master HPP/HPP_Jual are set to the discount-excluded per-unit price
                        const useHppJualPerUnit = item.hppJualPerUnit || item.hppPerUnit || 0;
                        let directFormulaPurchase = `New HPP (direct) = HPP Jual per unit (exclude discount)<br>= ${fmt(useHppJualPerUnit)}`;
                        let directFormulaJual = `New HPP Jual (direct) = HPP Jual per unit (exclude discount)<br>= ${fmt(useHppJualPerUnit)}`;

                        // Deltas for direct method
                        let deltaDirect = `Selisih (direct): ${fmt(item.selisihHpp_direct)}`;
                        let deltaDirectJual = `Selisih Jual (direct): ${fmt(item.selisihHppJual_direct)}`;

                        // Also provide legacy/simple formulas for comparison
                        let conciseFormulaPurchase = `Direct: ${fmt(item.newHpp)}<br><em>${deltaDirect}</em>`;
                        let conciseFormulaJual = `Direct: ${fmt(item.newHppJual)}<br><em>${deltaDirectJual}</em>`;
                        debugInfo += `<tr>
                            <td>${item.obat_nama}</td>
                            <td>${num(item.oldStok)}</td>
                            <td>${num(item.qty)}</td>
                            <td>${fmt(item.purchaseCost)}</td>
                            <td>${fmt(item.oldHpp)}</td>
                            <td>${fmt(item.hppPerUnit)}</td>
                            <td>${fmt(item.newHpp)}</td>
                            <td>${fmt(item.hppJualPerUnit || 0)}</td>
                            <td>${fmt(item.newHppJual || 0)}</td>
                            <td>${num(item.newStok)}</td>
                            <td style="font-size:0.9em;">
                                <strong>Purchase HPP:</strong><br>${conciseFormulaPurchase}
                                ${ conciseFormulaJual ? `<hr><strong>Selling HPP:</strong><br>${conciseFormulaJual}` : '' }
                            </td>
                        </tr>`;
                    });
                    debugInfo += `</tbody></table>`;
                    // Create modal to display the debug info
                    let modal = $(
                        `<div class="modal fade" id="debugHppModal" tabindex="-1" role="dialog">
                            <div class="modal-dialog modal-xl" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Debug HPP Calculation</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        ${debugInfo}
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>`
                    );
                    // Append to body, show and then remove on close
                    $('body').append(modal);
                    modal.modal('show');
                    modal.on('hidden.bs.modal', function() {
                        $(this).remove();
                    });
                } else {
                    alert(res.message || 'Terjadi kesalahan');
                }
            },
            error: function(xhr) {
                alert('Gagal menampilkan debug HPP: ' + (xhr.responseJSON?.message || 'Terjadi kesalahan'));
            }
        });
    });
});
</script>
@endpush
