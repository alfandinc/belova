@extends('layouts.erm.app')
@section('title', 'ERM | FakturPembelian')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection  
@section('content')
<div class="container-fluid">
            <!-- Page-Title -->
    <!-- Title and Button Row -->
    <div class="row mt-3 align-items-center">
        <div class="col-md-6">
            <h2 class="mb-0">Input Faktur Pembelian</h2>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
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
                <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalCariPermintaan"><i class="fa fa-search"></i> Cari Faktur Berdasarkan No Permintaan</button>
                <div class="form-inline mt-2">
                    <label for="tanggalTerimaRange" class="mr-2">Filter Tanggal Terima:</label>
                    <input type="text" id="tanggalTerimaRange" class="form-control" style="width:220px;" autocomplete="off" placeholder="Pilih rentang tanggal">
                    <button class="btn btn-secondary btn-sm ml-2" id="resetTanggalTerima">Reset</button>
                        <label for="statusFilter" class="ml-4 mr-2">Status:</label>
                        <select id="statusFilter" class="form-control" style="width:150px;">
                            <option value="">Semua</option>
                            <option value="diminta">Diminta</option>
                            <option value="diterima">Diterima</option>
                            <option value="diapprove">Diapprove</option>
                        </select>
                    <div class="form-check ml-4">
                        <input class="form-check-input" type="checkbox" id="hideDireturCheckbox" checked>
                        <label class="form-check-label" for="hideDireturCheckbox">Sembunyikan Faktur Diretur</label>
                    </div>
                        <!-- Custom search inputs -->
                        <input type="text" id="searchNoFaktur" class="form-control ml-4" style="width:180px;" placeholder="Cari No Faktur">
                        <input type="text" id="searchNamaObat" class="form-control ml-2" style="width:180px;" placeholder="Cari Nama Obat">
                        <input type="text" id="searchPemasok" class="form-control ml-2" style="width:180px;" placeholder="Cari Pemasok">
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
        <table class="table table-bordered" id="fakturbeli-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>No Faktur</th>
                        <th>Nama Obat</th>
                        <th>Pemasok</th>
                        <th>Tanggal Permintaan</th>
                        <th>Tanggal Terima</th>
                        <th>Jatuh Tempo</th>
                        <th>Total Harga</th>
                        <th>Status</th>
                        <th>Bukti</th>
                        <th style="width: 280px; min-width: 260px; max-width: 320px;">Aksi</th>
                    </tr>
                </thead>
            </table>
</div>
@endsection
@push('scripts')
<script>
$(function() {
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
    dom: 'lrtip', // Remove default search input
    ajax: {
            url: '{{ route('erm.fakturbeli.index') }}',
            data: function(d) {
                d.tanggal_terima_range = $('#tanggalTerimaRange').val();
                d.status = $('#statusFilter').val();
                d.hide_diretur = $('#hideDireturCheckbox').is(':checked') ? 1 : 0;
                    d.search_no_faktur = $('#searchNoFaktur').val();
                    d.search_nama_obat = $('#searchNamaObat').val();
                    d.search_pemasok = $('#searchPemasok').val();
                }
            },
        order: [[4, 'desc']], // received_date column (index 4)
        columns: [
            { data: null, name: 'no', orderable: false, searchable: false, render: function (data, type, row, meta) {
                return meta.row + meta.settings._iDisplayStart + 1;
            }},
            { data: 'no_faktur', name: 'no_faktur', render: function(data) {
                return data ? data : '-';
            }},
            { data: 'nama_obat', name: 'nama_obat', orderable: false, searchable: false },
            { data: 'pemasok', name: 'pemasok' },
            { data: 'requested_date', name: 'requested_date' },
            { data: 'received_date', name: 'received_date', render: function(data) {
                return data ? data : '-';
            }},
            { data: 'due_date', name: 'due_date', render: function(data) {
                return data ? data : '-';
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
            { data: 'bukti', name: 'bukti', render: function(data) {
                return data ? `<a href='/storage/${data}' target='_blank'>Lihat</a>` : '-';
            }},
            { data: 'action', name: 'action', orderable: false, searchable: false, render: function(data, type, row) {
                if (row.status === 'diretur') {
                    return '';
                }
                return data;
            } },
        ],
        columnDefs: [
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
