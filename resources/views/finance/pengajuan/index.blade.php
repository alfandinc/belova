@extends('layouts.finance.app')
@section('title', 'Finance | Pengajuan Dana')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection

@section('content')
    <style>
        /* Constrain items column without forcing table-layout: fixed which collapses other columns */
        #pengajuanTable td.items-list-cell {
            max-width: 360px; /* adjust as needed */
            white-space: normal !important;
            word-break: break-word;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: middle; /* center vertically like other cells */
        }
        /* Small, fixed width for the 'No' column */
        #pengajuanTable td.col-no, #pengajuanTable th.col-no {
            text-align: center;
            white-space: nowrap;
            padding-left: 6px;
            padding-right: 6px;
            font-size: 13px;
            max-width: 36px;
            min-width: 28px;
            width: 32px;
        }
        /* Force grand total values to align to the right edge of the cell */
        #pengajuanTable td.grand-total-cell, #pengajuanTable th.grand-total-cell {
            text-align: right !important;
            padding-right: 12px;
            vertical-align: middle; /* center vertically */
        }
        /* Ensure any inner elements also align right and span full width */
        #pengajuanTable td.grand-total-cell > * {
            display: block;
            width: 100%;
            text-align: right !important;
        }
        /* blinking badge for empty approvals */
        @keyframes blinkAnim { 0% { opacity: 1; } 50% { opacity: 0.2; } 100% { opacity: 1; } }
        /* apply animation directly to approvals-empty so it blinks */
        .approvals-empty { animation: blinkAnim 1.2s linear infinite; }
        .approvals-empty .fa { margin-right: 6px; }
        /* ensure inner lists wrap nicely and stay scrollable when long */
        #pengajuanTable td.items-list-cell > * {
            display: block;
            max-height: 140px;
            overflow: auto;
        }
        #pengajuanTable td.items-list-cell ul,
        #pengajuanTable td.items-list-cell ol {
            margin: 0;
            padding-left: 16px;
        }
        /* Ensure form labels in the pengajuan modal use Title Case instead of all-caps */
        #pengajuanModal label { text-transform: capitalize !important; }
        /* show red asterisk for required fields */
        #pengajuanModal label.required:after { content: " *"; color: #e74c3c; margin-left: 4px; }
    </style>
<div class="page-content">
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Pengajuan Dana</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Finance</a></li>
                            <li class="breadcrumb-item active">Pengajuan Dana</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between flex-nowrap">
                            <h4 class="card-title mb-0">Daftar Pengajuan Dana</h4>
                            <div class="col-auto d-flex align-items-center flex-nowrap">
                                <!-- reduce max width so button stays on same line; use flex-nowrap to avoid wrapping -->
                                <input type="text" id="filter_tanggal" class="form-control form-control-sm mr-2" style="min-width:140px; max-width:220px; width:220px;" placeholder="Pilih rentang tanggal" readonly>
                                <select id="filter_jenis" class="form-control form-control-sm mr-2" style="min-width:140px; max-width:180px; width:160px;">
                                    <option value="">Semua</option>
                                    <option value="Pembayaran Inkaso">Pembayaran Inkaso</option>
                                    <option value="Pembelian Barang">Pembelian Barang</option>
                                    <option value="Operasional">Operasional</option>
                                </select>
                                <select id="filter_sumber" class="form-control form-control-sm mr-2" style="min-width:140px; max-width:180px; width:160px;">
                                    <option value="">Semua</option>
                                    <option value="Kas Bank">Kas Bank</option>
                                    <option value="Kas Kecil">Kas Kecil</option>
                                </select>
                                <select id="filter_approval" class="form-control form-control-sm mr-2" style="min-width:140px; max-width:180px; width:160px;">
                                    <option value="menunggu" selected>Menunggu</option>
                                    <option value="approved">Approved</option>
                                    <option value="declined">Ditolak</option>
                                </select>
                                <button type="button" class="btn btn-outline-secondary btn-sm mr-2" id="clearFilterTanggal" title="Clear filter">Clear</button>
                                <button type="button" class="btn btn-primary ml-2" id="btnAddPengajuan">
                                    <i class="fas fa-plus mr-1"></i> Buat Pengajuan
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="pengajuanTable" class="table table-bordered dt-responsive" style="width:100%">
                                <thead>
                                    <tr>
                                        <th class="col-no">No</th>
                                        <th>Detail</th>
                                        <th>Nama Pengaju</th>
                                        <th class="d-none">Tanggal</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Diajukan ke</th>
                                        <th>Approvals</th>
                                        <th>Action</th>
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

            <!-- Bukti Modal: show existing bukti and allow uploading additional files -->
            <div class="modal fade" id="buktiModal" tabindex="-1" aria-labelledby="buktiModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="buktiModalLabel">Upload Bukti Transaksi</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-2">
                                <strong>Gambar tersimpan:</strong>
                                <div id="buktiModalPreview" class="mt-2 d-flex flex-wrap"></div>
                            </div>
                            <hr />
                            <div class="mb-2">
                                <label class="form-label">Tambah file</label>
                                <div class="d-flex align-items-center">
                                    <input type="file" id="buktiModalInput" name="bukti_transaksi[]" accept="image/*" multiple style="display:block">
                                    <div class="ml-2"><small id="buktiModalFilesLabel" class="text-muted">Tidak ada file terpilih</small></div>
                                </div>
                                <div id="buktiModalNewPreview" class="mt-2 d-flex flex-wrap"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="button" id="buktiModalUpload" class="btn btn-primary">Upload</button>
                        </div>
                    </div>
                </div>
            </div>

<!-- Add/Edit Pengajuan Modal (skeleton) -->
<div class="modal fade" id="pengajuanModal" tabindex="-1" aria-labelledby="pengajuanModalLabel" aria-hidden="true">
    <!-- widened modal: increase max-width and use percentage width for better responsiveness -->
    <div class="modal-dialog modal-xl" style="max-width:1400px; width:95%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pengajuanModalLabel">Buat Pengajuan Dana</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <form id="pengajuanForm">
                @csrf
                <input type="hidden" id="pengajuan_id" name="pengajuan_id">
                <div class="modal-body">
        
                    <!-- Minimal inputs for now; expand later -->
                    <!-- Compact 2-row layout: Row 1 (kode, sumber, perusahaan, employee), Row 2 (tanggal, jenis, rekening, bukti) -->
                    <div class="form-row g-2 pengajuan-compact">
                        <div class="col-md-3">
                            <label for="kode_pengajuan">Kode Pengajuan</label>
                            <input type="text" class="form-control" id="kode_pengajuan" name="kode_pengajuan" readonly>
                        </div>
                        <div class="col-md-3">
                            <label for="sumber_dana" class="required">Sumber Dana</label>
                            <select id="sumber_dana" name="sumber_dana" class="form-control">
                                <option value="">-- Pilih Sumber Dana --</option>
                                <option value="Kas Bank">Kas Bank</option>
                                <option value="Kas Kecil">Kas Kecil</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="perusahaan">Perusahaan</label>
                            <select id="perusahaan" name="perusahaan" class="form-control">
                                <option value="">-- Pilih Perusahaan --</option>
                                <option value="CV Belia Abadi">CV Belia Abadi</option>
                                <option value="CV Belova Indonesia">CV Belova Indonesia</option>
                                <option value="Belova Corp">Belova Corp</option>
                                <option value="CV Grha Asri">CV Grha Asri</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="employee_id" class="required">Nama Pengaju</label>
                            <select id="employee_id" name="employee_id" class="form-control select2" style="width:100%">
                                <option value="">-- Pilih Employee --</option>
                                @php $employees = \App\Models\HRD\Employee::with('user')->orderBy('nama')->get(); @endphp
                                @foreach($employees as $emp)
                                    @php $divId = $emp->division_id ?? '';
                                        $divName = $emp->division->name ?? '';
                                    @endphp
                                    <option value="{{ $emp->id }}" data-division-id="{{ $divId }}" data-division-name="{{ $divName }}">{{ $emp->user->name ?? $emp->nama }} ({{ $emp->id }})</option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" id="division_id" name="division_id" value="">
                    </div>

                    <div class="form-row g-2 mt-2 pengajuan-compact">
                        <div class="col-md-2">
                            <label for="tanggal_pengajuan" class="required">Tanggal</label>
                            <input type="date" class="form-control" id="tanggal_pengajuan" name="tanggal_pengajuan">
                        </div>
                        <div class="col-md-2">
                            <label for="jenis_pengajuan" class="required">Jenis</label>
                            <select id="jenis_pengajuan" name="jenis_pengajuan" class="form-control">
                                <option value="Pembayaran Inkaso">Pembayaran Inkaso</option>
                                <option value="Pembelian Barang">Pembelian Barang</option>
                                <option value="Operasional">Operasional</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="rekening_id">Rekening <small class="text-muted" style="font-weight:400;">(Bank / No. Rekening / Atas Nama)</small></label>
                            <div class="d-flex align-items-center">
                                <div style="flex:1">
                                    <select id="rekening_id" name="rekening_id" class="form-control select2" style="width:100%">
                                        <option value="">-- Pilih Rekening --</option>
                                        @php $reks = \App\Models\Finance\FinanceRekening::orderBy('bank')->get(); @endphp
                                        @foreach($reks as $r)
                                            <option value="{{ $r->id }}">{{ $r->bank }} / {{ $r->no_rekening }} / {{ $r->atas_nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="btn btn-outline-secondary ml-2" id="btnToggleRekInline" title="Tambah Rekening"><i class="fa fa-plus"></i></button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="bukti_transaksi">Bukti Transaksi <small class="text-muted" style="font-weight:400;">(Gambar) - bisa pilih beberapa file</small></label>
                            <div class="input-group">
                                <input type="file" class="d-none" id="bukti_transaksi" name="bukti_transaksi[]" accept="image/*" multiple>
                                <div class="input-group-prepend">
                                    <button type="button" class="btn btn-outline-secondary" id="btnChooseBukti"><i class="fa fa-upload"></i> Pilih File</button>
                                </div>
                                <input type="text" id="bukti_files_label" class="form-control" readonly placeholder="Tidak ada file terpilih">
                            </div>
                            <small class="form-text text-muted">Maks 2MB per file. Format: jpg, png, gif.</small>
                            <div id="bukti_preview" class="mt-2" style="display:none">
                                <!-- multiple thumbnails will be injected here -->
                            </div>
                        </div>
                    </div>

                    <!-- Deskripsi removed as per request (duplicate rekening block removed) -->

                    <!-- inline rekening inputs (moved to top area) -->
                    <div id="rekeningInline" class="mt-2" style="display:none;">
                        <div class="pt-2 border-top">
                            <div class="mb-1"><small class="text-muted">Tambah Rekening Baru — isi data di bawah lalu klik <strong>Simpan Rekening</strong></small></div>
                            <div class="row g-2 align-items-center" id="rekeningInlineForm">
                                <div class="col-md-5">
                                    <input type="text" id="rek_bank_inline" name="bank" class="form-control" placeholder="Bank">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" id="rek_no_inline" name="no_rekening" class="form-control" placeholder="No. Rekening">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" id="rek_atas_inline" name="atas_nama" class="form-control" placeholder="Atas Nama">
                                </div>
                                    <div class="col-12 d-flex justify-content-end mt-1">
                                    <button type="button" class="btn btn-secondary btn-sm mr-2" id="btnCancelRekInline">Batal</button>
                                    <button type="button" id="saveRekeningInline" class="btn btn-primary btn-sm">Simpan Rekening</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add Rekening Modal -->
                    <div class="modal fade" id="rekeningModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Tambah Rekening</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                </div>
                                <div id="rekeningForm">
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label for="rek_bank">Bank</label>
                                            <input type="text" id="rek_bank" name="bank" class="form-control">
                                        </div>
                                        <div class="form-group">
                                            <label for="rek_no">No. Rekening</label>
                                            <input type="text" id="rek_no" name="no_rekening" class="form-control">
                                        </div>
                                        <div class="form-group">
                                            <label for="rek_atas">Atas Nama</label>
                                            <input type="text" id="rek_atas" name="atas_nama" class="form-control">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                        <button type="button" id="saveRekening" class="btn btn-primary">Simpan</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- inline faktur select is rendered in footer as #select_faktur_inline -->
                    </div>

                    
                    
                    <!-- Items section (compact) -->
                    <div class="form-group mt-3">
                        <label>Items Pengajuan</label>
                        <!-- moved faktur select outside of table for clearer layout -->
                        <div class="mb-2">
                            <select id="select_faktur_inline" class="form-control form-control-sm" style="min-width:220px; max-width:360px; width:260px;" data-placeholder="Cari faktur (no_faktur / no_permintaan)"></select>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th style="width:4%">#</th>
                                        <th>Nama Item</th>
                                        <th>Notes</th>
                                        <th style="width:10%">Qty</th>
                                        <th style="width:15%">Harga</th>
                                        <th style="width:15%">Total</th>
                                        <th style="width:6%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5" class="text-end"><strong>Total</strong></td>
                                        <td><input type="text" id="grand_total_display" class="form-control" readonly></td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end align-items-center">
                                                <button type="button" id="addItemRow" class="btn btn-sm btn-success mr-2" title="Tambah Item"><i class="fa fa-plus"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <input type="hidden" id="items_json" name="items_json">
                    </div>
                </div>
                <div class="modal-footer">
                    <!-- removed data-dismiss so modal can only be closed manually via the X button; keep Save behavior -->
                    <button type="button" class="btn btn-secondary" id="btnCancelPengajuan">Batal</button>
                    <button type="submit" id="savePengajuan" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // server-provided current employee id (logged-in user) to default main employee select
    var __currentEmployeeId = '{{ auth()->check() && optional(auth()->user()->employee)->id ? auth()->user()->employee->id : '' }}';
    if (typeof $.fn.select2 === 'function') {
        $('#employee_id, #rekening_id').select2({ dropdownParent: $('#pengajuanModal'), width: '100%' });
    }

    // Make pengajuan modal only closable via the X button (no backdrop click, no ESC)
    // Keep show:false so we control when it is shown; subsequent .modal('show') calls will respect these options.
    if (typeof $('#pengajuanModal').modal === 'function') {
        $('#pengajuanModal').modal({ backdrop: 'static', keyboard: false, show: false });
    }

    // Initialize daterangepicker for filter_tanggal (reuse project convention)
    if (typeof $.fn.daterangepicker === 'function' && typeof moment !== 'undefined') {
        $('#filter_tanggal').daterangepicker({
            locale: { format: 'YYYY-MM-DD' },
            autoUpdateInput: true,
            startDate: moment().format('YYYY-MM-DD'),
            endDate: moment().format('YYYY-MM-DD'),
            opens: 'left',
            singleDatePicker: false,
            showDropdowns: true
        }, function(start, end) {
            $('#filter_tanggal').val(start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD'));
            table.ajax.reload();
        });
        // default to empty (no filter) to show all by default; user can pick range to filter
        $('#filter_tanggal').val('');
    }

    var table = $('#pengajuanTable').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        columnDefs: [
            { targets: 0, width: '32px', className: 'col-no' },
            { targets: 1, width: '140px' },
            { targets: 2, width: '220px' },
            { targets: 3, width: '120px' },
            { targets: 4, width: '360px', className: 'items-list-cell' },
            { targets: 5, width: '120px', className: 'text-end grand-total-cell' },
            { targets: 6, width: '200px' },
            { targets: 7, width: '160px' },
            { targets: 8, width: '120px' }
        ],
        ajax: {
            url: '{!! route('finance.pengajuan.data') !!}',
            data: function(d) {
                // include date range filter parameters
                var tanggal = $('#filter_tanggal').val();
                var start = '', end = '';
                if (tanggal && tanggal.indexOf(' - ') !== -1) {
                    var parts = tanggal.split(' - ');
                    start = parts[0];
                    end = parts[1] || parts[0];
                }
                d.start_date = start;
                d.end_date = end;
                // include jenis, sumber_dana and approval status filters
                d.jenis = $('#filter_jenis').val() || '';
                d.sumber_dana = $('#filter_sumber').val() || '';
                var approval = $('#filter_approval').val();
                d.approval_status = approval || 'menunggu';
            }
        },
        columns: [
            // render a sequential row number instead of DB id
            { data: 'id', name: 'id', render: function(data, type, row, meta) {
                    return meta.settings._iDisplayStart + meta.row + 1;
                }
            },
        { data: 'kode_pengajuan', name: 'kode_pengajuan', orderable: true, orderData: [3] },
        { data: 'employee_display', name: 'employee_display', defaultContent: '', orderable: true, orderData: [3] },
        // format tanggal_pengajuan for display as '1 Januari 2025' (Indonesian)
        { data: 'tanggal_pengajuan', name: 'tanggal_pengajuan', visible: false, render: function(data, type, row, meta) {
                    if (!data) return '';
                    // keep raw data for ordering/searching; only format for display/filter
                    if (type === 'display' || type === 'filter') {
                        try {
                            var d = new Date(data);
                            return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
                        } catch (e) {
                            return data;
                        }
                    }
                    return data;
                }
            },
            { data: 'items_list', name: 'items_list', orderable: false, searchable: false },
            { data: 'grand_total', name: 'grand_total', render: function(data, type, row, meta) {
                    if (data === null || data === undefined) data = 0;
                    if (type === 'display' || type === 'filter') {
                        try {
                            var n = Number(data);
                            // Prefix Indonesian Rupiah symbol and format number
                            return 'Rp ' + n.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        } catch (e) {
                            return data;
                        }
                    }
                    // raw data used for ordering/searching
                    return data;
                }, orderable: false, searchable: false },
            { data: 'diajukan_ke', name: 'diajukan_ke', orderable: false, searchable: false, className: 'diajukan-cell' },
            // server returns rendered HTML list for approvals (approver name + date)
            { data: 'approvals_list', name: 'approvals_list', orderable: false, searchable: false, className: 'approvals-cell' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'actions-cell' }
        ],
        createdRow: function(row, data, dataIndex) {
            try {
            // items_list is now at column index 4 — mark it so CSS can constrain it
            $(row).find('td').eq(4).addClass('items-list-cell');
            // mark first cell as 'col-no' to apply narrow styling
            $(row).find('td').eq(0).addClass('col-no');

                // Move jenis_pengajuan and tanggal into the Kode cell; show tanggal first then jenis badge
                var jenis = data.jenis_pengajuan || '';
                var $kodeCell = $(row).find('td').eq(1);
                // append tanggal under the Nama Pengaju (employee_display) cell (below division text)
                try {
                    var tanggalRaw = data.tanggal_pengajuan || '';
                    var $empCell = $(row).find('td').eq(2);
                    if (tanggalRaw && $empCell.find('.tanggal-badge').length === 0) {
                        var d = new Date(tanggalRaw);
                        if (!isNaN(d.getTime())) {
                            var formatted = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
                            var $tgl = $('<div class="mt-1 tanggal-badge"><small class="text-muted">'+formatted+'</small></div>');
                            // if employee display container exists, append there; otherwise append to the cell
                            var $empDisplay = $empCell.find('.employee-display').first();
                            if ($empDisplay.length) {
                                $empDisplay.append($tgl);
                            } else {
                                $empCell.append($tgl);
                            }
                        }
                    }
                } catch(e) {}

                if (jenis) {
                    var badgeClass = 'badge-secondary';
                    var k = jenis.toString().toLowerCase();
                    if (k.indexOf('operasional') !== -1) badgeClass = 'badge-primary';
                    else if (k.indexOf('pembelian') !== -1) badgeClass = 'badge-success';
                    else if (k.indexOf('inkaso') !== -1 || k.indexOf('pembayaran') !== -1) badgeClass = 'badge-warning';
                    // avoid duplicating if server-side already included badge
                    if ($kodeCell.find('.jenis-badge').length === 0) {
                        var $badge = $('<div class="mt-1 jenis-badge"><small><span class="badge '+badgeClass+'">'+jenis+'</span></small></div>');
                        $kodeCell.append($badge);
                    }
                }
                // append rekening info under kode if present
                try {
                    var rek = data.rekening || null;
                    if (rek && $kodeCell.find('.rekening-badge').length === 0) {
                        var rekText = '';
                        if (rek.bank) rekText += rek.bank;
                        if (rek.no_rekening) rekText += (rekText ? ' / ' : '') + rek.no_rekening;
                        if (rek.atas_nama) rekText += (rekText ? ' / ' : '') + rek.atas_nama;
                        if (rekText) {
                            var $rek = $('<div class="mt-1 rekening-badge">'+rekText+'</div>');
                            $kodeCell.append($rek);
                        }
                    }
                } catch(e) {}
                // If approvals list empty, show blinking warning badge in approvals column
                var approvalsRaw = (data.approvals_list || '').toString().trim();
                var $approvalsCell = $(row).find('td.approvals-cell').first();
                if ($approvalsCell.length) {
                    if (!approvalsRaw) {
                        var warnHtml = '<div class="jenis-badge"><small><span class="badge badge-warning approvals-empty"><i class="fa fa-exclamation-triangle"></i> Menunggu Persetujuan</span></small></div>';
                        $approvalsCell.html(warnHtml);
                    }
                }
            } catch(e) {}
        },
        responsive: true,
        // tanggal_pengajuan is now at column index 3 (0-based), so order by that
        order: [[3, 'desc']]
    });

    // Open modal for create
    $('#btnAddPengajuan').on('click', function() {
        $('#pengajuanModalLabel').text('Buat Pengajuan Dana');
        $('#pengajuanForm')[0].reset();
        $('#pengajuan_id').val('');
        $('.invalid-feedback').text('');
        $('.is-invalid').removeClass('is-invalid');
        if (typeof $('#employee_id').select2 === 'function') {
            // default to currently logged-in employee when creating a new pengajuan
            if (__currentEmployeeId && __currentEmployeeId !== '') {
                $('#employee_id').val(__currentEmployeeId).trigger('change');
            } else {
                $('#employee_id').val('').trigger('change');
            }
    }
        // reset items table for a fresh create
        $('#itemsTable tbody').empty();
        addItemRow();
        recalcItems();
        // clear rekening select, division hidden, file input and preview, grand total and hidden items_json
        if (typeof $('#rekening_id').select2 === 'function') { $('#rekening_id').val('').trigger('change'); }
        $('#division_id').val('');
        $('#bukti_transaksi').val('');
        $('#bukti_files_label').val('Tidak ada file terpilih');
        $('#bukti_preview').hide();
        $('#bukti_preview img').attr('src', '');
        $('#grand_total_display').val('0.00');
        $('#items_json').val('');
        // set tanggal_pengajuan default to today
        var today = new Date().toISOString().slice(0,10);
        $('#tanggal_pengajuan').val(today);
        // fetch generated kode from server
        $.ajax({
            url: '/finance/pengajuan-dana/generate-kode',
            method: 'GET',
            success: function(res) {
                if (res.kode) {
                    $('#kode_pengajuan').val(res.kode);
                }
                $('#pengajuanModal').modal('show');
            },
            error: function() {
                // still show modal even if kode generation fails
                $('#pengajuanModal').modal('show');
            }
        });
    });

    // when modal is fully hidden, also clear everything to avoid stale data
    $('#pengajuanModal').on('hidden.bs.modal', function () {
        // reset whole form and selects
        $('#pengajuanForm')[0].reset();
        $('#pengajuan_id').val('');
        $('.invalid-feedback').text('');
        $('.is-invalid').removeClass('is-invalid');
        if (typeof $('#employee_id').select2 === 'function') { $('#employee_id').val('').trigger('change'); }
        $('#division_id').val('');
        if (typeof $('#rekening_id').select2 === 'function') { $('#rekening_id').val('').trigger('change'); }
        $('#bukti_transaksi').val('');
        $('#bukti_files_label').val('Tidak ada file terpilih');
        $('#bukti_preview').hide();
        $('#bukti_preview img').attr('src', '');
        $('#grand_total_display').val('0.00');
        $('#items_json').val('');
        $('#itemsTable tbody').empty();
        addItemRow();
        recalcItems();
        // reset tanggal_pengajuan to today as default
        var today = new Date().toISOString().slice(0,10);
        $('#tanggal_pengajuan').val(today);
    });

    // Preview selected image
    $('#bukti_transaksi').on('change', function(e) {
        var files = this.files || [];
        var $preview = $('#bukti_preview');
        $preview.empty();
        // update label with filenames or count
        if (!files || files.length === 0) {
            $('#bukti_files_label').val('Tidak ada file terpilih');
        } else if (files.length === 1) {
            $('#bukti_files_label').val(files[0].name);
        } else {
            $('#bukti_files_label').val(files.length + ' file terpilih');
        }

        if (files.length) {
            // render thumbnails for each selected file
            Array.from(files).forEach(function(file){
                if (!file.type || file.type.indexOf('image') === -1) return;
                var reader = new FileReader();
                reader.onload = function(evt) {
                    var $img = $('<img>').attr('src', evt.target.result).css({ 'max-width':'120px', 'max-height':'80px', 'margin-right':'6px', 'margin-bottom':'6px' });
                    $preview.append($img);
                };
                reader.readAsDataURL(file);
            });
            $preview.show();
        } else {
            $preview.hide();
            $preview.empty();
        }
    });

    // wire Choose File button to the hidden input
    $(document).on('click', '#btnChooseBukti', function(){
        $('#bukti_transaksi').trigger('click');
    });

    // Items table management
    function recalcItems() {
        var grand = 0;
        $('#itemsTable tbody tr').each(function(i, tr) {
            var qty = parseFloat($(tr).find('.item-qty').val() || 0);
            var price = parseFloat($(tr).find('.item-price').val() || 0);
            var total = (qty * price) || 0;
            $(tr).find('.item-total').val(total.toFixed(2));
            grand += total;
            $(tr).find('td:first').text(i+1);
        });
        $('#grand_total_display').val(grand.toFixed(2));
    }

    function addItemRow(data) {
    // default qty is empty so auto-appended blank rows won't be counted as items
    data = data || {desc:'', qty:'', price:0, notes: ''};
        var $tr = $('<tr>');
        $tr.append('<td class="align-middle text-center"></td>');
        $tr.append('<td><input type="text" class="form-control item-desc" placeholder="Nama Item" value="'+(data.desc||'')+'"></td>');
    // notes input (replaces per-item employee select)
    $tr.append('<td><input type="text" class="form-control item-notes" name="item_notes[]" placeholder="Catatan" value="'+(data.notes||'')+'"></td>');
        $tr.append('<td><input type="number" min="0" step="1" class="form-control item-qty" value="'+(data.qty||'')+'"></td>');
        $tr.append('<td><input type="number" min="0" step="0.01" class="form-control item-price" value="'+(data.price||0)+'"></td>');
        $tr.append('<td><input type="text" readonly class="form-control item-total" value="0.00"></td>');
        // nicer remove button with icon; we handle last-row protection below
        $tr.append('<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-item" title="Hapus item"><i class="fa fa-trash"></i></button></td>');
        $('#itemsTable tbody').append($tr);
        // focus the newly added row's description for quick entry
        $tr.find('.item-desc').focus();
        // populate notes if provided (edit flow)
        if (data.notes) {
            $tr.find('.item-notes').val(data.notes);
        }
        recalcItems();
    }

    // initial one row
    addItemRow();

    $(document).on('click', '#addItemRow', function(){ addItemRow(); });

    // remove with protection: if only 1 row left, clear fields instead of removing
    $(document).on('click', '.remove-item', function(){
        var $tbody = $('#itemsTable tbody');
        var $rows = $tbody.find('tr');
        var $tr = $(this).closest('tr');
        if ($rows.length <= 1) {
            // clear the inputs in the last row instead of removing it
            $tr.find('.item-desc').val('');
            $tr.find('.item-qty').val(1);
            $tr.find('.item-price').val(0);
            $tr.find('.item-total').val('0.00');
            recalcItems();
            // subtle highlight to show cleared
            $tr.addClass('table-warning');
            setTimeout(function(){ $tr.removeClass('table-warning'); }, 800);
            return;
        }
        // animate removal for clarity
        $tr.fadeOut(180, function(){
            $(this).remove();
            recalcItems();
        });
    });

    // recalc on qty/price changes
    $(document).on('input', '.item-qty, .item-price', function(){ recalcItems(); });

    // keyboard UX: Enter in desc jumps to qty, Enter in qty jumps to price, Enter in price adds new row
    $(document).on('keydown', '.item-desc', function(e){
        if (e.key === 'Enter') {
            e.preventDefault();
            $(this).closest('td').next().find('.item-qty').focus();
        }
    });
    $(document).on('keydown', '.item-qty', function(e){
        if (e.key === 'Enter') {
            e.preventDefault();
            $(this).closest('td').next().find('.item-price').focus();
        }
    });
    $(document).on('keydown', '.item-price', function(e){
        if (e.key === 'Enter') {
            e.preventDefault();
            // if we're on the last row, add a new row; otherwise move to next desc
            var $tr = $(this).closest('tr');
            var $next = $tr.next('tr');
            if ($next.length === 0) {
                addItemRow();
            } else {
                $next.find('.item-desc').focus();
            }
        }
    });

    // auto-add a new blank row when the user edits the last row (so they can keep adding quickly)
    $(document).on('blur', '.item-desc, .item-qty, .item-price', function(){
        var $tbody = $('#itemsTable tbody');
        var $rows = $tbody.find('tr');
        var $last = $rows.last();
        // check if last row has any content
        var filled = false;
        $last.find('input').each(function(){ if ($(this).val() && $(this).val().toString().trim() !== '' && $(this).val() !== '0' && $(this).val() !== '0.00') filled = true; });
        if (filled) {
            // add a new blank row if none exists after a short delay (allow recalc)
            if ($rows.length === 0 || $rows.last().find('.item-desc').val() !== '') {
                addItemRow();
            }
        }
    });

    // Save pengajuan (create/update) using FormData to include file
    $('#pengajuanForm').on('submit', function(e) {
        e.preventDefault();
        var id = $('#pengajuan_id').val();
        var url = id ? ('/finance/pengajuan-dana/' + id) : '/finance/pengajuan-dana';
        var method = id ? 'POST' : 'POST'; // use POST always; for update we'll append _method

        var formEl = document.getElementById('pengajuanForm');
        // serialize items into hidden input
        var items = [];
        $('#itemsTable tbody tr').each(function(){
            var $tr = $(this);
            var desc = $tr.find('.item-desc').val();
            var descTrim = desc ? desc.toString().trim() : '';
            var qty = parseFloat($tr.find('.item-qty').val()||0);
            var price = parseFloat($tr.find('.item-price').val()||0);
            var notes = $tr.find('.item-notes').val() || null;
            // If the row is a faktur row, we embed fakturbeli_id into payload
            var fakturId = $tr.data('fakturbeli-id') || null;
            if (descTrim !== '') {
                if (fakturId) {
                    items.push({desc: descTrim, qty: 1, price: price || 0, fakturbeli_id: fakturId, notes: notes});
                } else {
                    items.push({desc: descTrim, qty: qty || 0, price: price || 0, notes: notes});
                }
            }
        });
        $('#items_json').val(JSON.stringify(items));

        var formData = new FormData(formEl);
        // If updating, spoof PUT
        if (id) formData.append('_method', 'PUT');

        // Clear validation
        $('.invalid-feedback').text('');
        $('.is-invalid').removeClass('is-invalid');

        $.ajax({
            url: url,
            method: method,
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            beforeSend: function() {
                $('#savePengajuan').attr('disabled', true).text('Menyimpan...');
            },
            success: function(res) {
                Swal.fire('Sukses', res.message || 'Data tersimpan', 'success');
                $('#pengajuanModal').modal('hide');
                table.ajax.reload(null, false);
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors || {};
                    Object.keys(errors).forEach(function(key) {
                        $('#' + key).addClass('is-invalid');
                        $('#' + key + '-error').text(errors[key][0]);
                    });
                } else {
                    Swal.fire('Error', 'Terjadi kesalahan pada server', 'error');
                }
            },
            complete: function() {
                $('#savePengajuan').attr('disabled', false).text('Simpan');
            }
        });
    });

    // Toggle inline rekening form
    $(document).on('click', '#btnToggleRekInline', function() {
        $('#rekeningInline').toggle();
        if ($('#rekeningInline').is(':visible')) {
            $('#rek_bank_inline').focus();
        }
    });

    $(document).on('click', '#btnCancelRekInline', function() {
        $('#rekeningInline').hide();
    });

    // Click handler for inline save button (avoid nested form submit)
    $(document).on('click', '#saveRekeningInline', function(e) {
        e.preventDefault();
        var btn = $(this);
        btn.attr('disabled', true).text('Menyimpan...');
        var payload = {
            bank: $('#rek_bank_inline').val(),
            no_rekening: $('#rek_no_inline').val(),
            atas_nama: $('#rek_atas_inline').val()
        };
        $.ajax({
            url: '{{ route('finance.rekening.store') }}',
            method: 'POST',
            data: payload,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(res) {
                Swal.fire('Sukses', 'Rekening berhasil ditambahkan', 'success');
                $('#rekeningInline').hide();
                // clear inline inputs
                $('#rek_bank_inline, #rek_no_inline, #rek_atas_inline').val('');
                // Add new option to select and select it
                var opt = new Option(res.data.bank + ' / ' + (res.data.no_rekening||'') + ' / ' + (res.data.atas_nama||''), res.data.id, true, true);
                $('#rekening_id').append(opt).trigger('change');
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors || {};
                    var msgs = Object.keys(errors).map(function(k){ return errors[k][0]; }).join('\n');
                    Swal.fire('Validasi', msgs, 'warning');
                } else {
                    Swal.fire('Error', 'Gagal menambahkan rekening', 'error');
                }
            },
            complete: function() {
                btn.attr('disabled', false).text('Simpan Rekening');
            }
        });
    });

    // Rekening modal save (same behavior as inline)
    // Click handler for Rekening modal save button (avoid nested form submit)
    $(document).on('click', '#saveRekening', function(e) {
        e.preventDefault();
        var btn = $(this);
        btn.attr('disabled', true).text('Menyimpan...');
        var payload = {
            bank: $('#rek_bank').val(),
            no_rekening: $('#rek_no').val(),
            atas_nama: $('#rek_atas').val()
        };
        var $modal = $('#rekeningModal');
        $.ajax({
            url: '{{ route('finance.rekening.store') }}',
            method: 'POST',
            data: payload,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(res) {
                Swal.fire('Sukses', 'Rekening berhasil ditambahkan', 'success');
                // append and select
                var opt = new Option(res.data.bank + ' / ' + (res.data.no_rekening||'') + ' / ' + (res.data.atas_nama||''), res.data.id, true, true);
                $('#rekening_id').append(opt).trigger('change');
                $modal.modal('hide');
                // clear modal inputs
                $('#rek_bank, #rek_no, #rek_atas').val('');
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors || {};
                    // display validation messages inline
                    $modal.find('.is-invalid').removeClass('is-invalid');
                    $modal.find('.invalid-feedback').remove();
                    Object.keys(errors).forEach(function(k){
                        var field = $modal.find('[name="'+k+'"]');
                        field.addClass('is-invalid');
                        field.after('<div class="invalid-feedback">'+errors[k][0]+'</div>');
                    });
                } else {
                    Swal.fire('Error', 'Gagal menambahkan rekening', 'error');
                }
            },
            complete: function() {
                btn.attr('disabled', false).text('Simpan');
            }
        });
    });

    // Edit pengajuan
    $('#pengajuanTable').on('click', '.edit-pengajuan', function() {
        var id = $(this).data('id');
        $('.invalid-feedback').text('');
        $('.is-invalid').removeClass('is-invalid');
        $.ajax({
            url: '/finance/pengajuan-dana/' + id,
            method: 'GET',
            success: function(res) {
                $('#pengajuanModalLabel').text('Edit Pengajuan');
                $('#pengajuan_id').val(res.id);
                $('#kode_pengajuan').val(res.kode_pengajuan);
                $('#employee_id').val(res.employee_id).trigger('change');
                $('#division_id').val(res.division_id).trigger('change');
                $('#tanggal_pengajuan').val(res.tanggal_pengajuan);
                $('#jenis_pengajuan').val(res.jenis_pengajuan);
                // populate new fields
                $('#sumber_dana').val(res.sumber_dana || '');
                $('#perusahaan').val(res.perusahaan || '');
                $('#rekening_id').val(res.rekening_id).trigger('change');
                if (res.bukti_transaksi) {
                    // res.bukti_transaksi may be JSON array or single path
                    var preview = $('#bukti_preview');
                    preview.empty();
                    try {
                        var arr = typeof res.bukti_transaksi === 'string' ? JSON.parse(res.bukti_transaksi) : res.bukti_transaksi;
                        if (Array.isArray(arr)) {
                            arr.forEach(function(p){
                                if (!p) return;
                                var $img = $('<img>').attr('src', '/storage/' + p).css({ 'max-width':'120px', 'max-height':'80px', 'margin-right':'6px', 'margin-bottom':'6px' });
                                preview.append($img);
                            });
                            preview.show();
                            try { $('#bukti_files_label').val((arr.length||0) + ' file tersimpan'); } catch(e){}
                        } else {
                            // treat as single path
                            var url = '/storage/' + res.bukti_transaksi;
                            preview.append($('<img>').attr('src', url).css({ 'max-width':'120px', 'max-height':'80px' }));
                            preview.show();
                            try { var fn = url.split('/').pop(); $('#bukti_files_label').val(fn); } catch(e){}
                        }
                    } catch (e) {
                        // fallback: treat as single path string
                        var url = '/storage/' + res.bukti_transaksi;
                        preview.append($('<img>').attr('src', url).css({ 'max-width':'120px', 'max-height':'80px' }));
                        preview.show();
                        try { var fn = url.split('/').pop(); $('#bukti_files_label').val(fn); } catch(e){}
                    }
                } else {
                    $('#bukti_preview').hide();
                    $('#bukti_preview img').attr('src', '');
                }
                // populate items table
                $('#itemsTable tbody').empty();
                if (res.items && res.items.length) {
                    res.items.forEach(function(it){
                        var rowData = { desc: it.nama_item, qty: it.jumlah, price: it.harga_satuan, notes: it.notes || '' };
                        addItemRow(rowData);
                        // if item is faktur-type, mark row so serialization and UI are correct
                        if (it.fakturbeli_id) {
                            var $last = $('#itemsTable tbody tr').last();
                            $last.data('fakturbeli-id', it.fakturbeli_id);
                            $last.find('.item-desc').prop('readonly', true);
                            $last.find('.item-qty').prop('readonly', true);
                            $last.find('.item-price').prop('readonly', true);
                        }
                    });
                } else {
                    addItemRow();
                }
                // set grand total display if available
                $('#grand_total_display').val(parseFloat(res.grand_total || 0).toFixed(2));
                recalcItems();

                $('#pengajuanModal').modal('show');
            },
            error: function() {
                Swal.fire('Error', 'Gagal memuat data', 'error');
            }
        });
    });

    // Auto-fill division when employee selected
    $('#employee_id').on('change', function() {
        var opt = $(this).find('option:selected');
        var divId = opt.data('division-id');
        if (divId) {
            $('#division_id').val(divId).trigger('change');
        }
    });

    // Delete pengajuan
    $('#pengajuanTable').on('click', '.delete-pengajuan', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, hapus!'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: '/finance/pengajuan-dana/' + id,
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(res) {
                        Swal.fire('Terhapus!', res.message || 'Data telah dihapus', 'success');
                        table.ajax.reload(null, false);
                    },
                    error: function() {
                        Swal.fire('Error', 'Gagal menghapus data', 'error');
                    }
                });
            }
        });
    });

    // Approve pengajuan (only visible to approvers; button rendered server-side)
    $('#pengajuanTable').on('click', '.approve-pengajuan', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Setujui pengajuan? ',
            text: 'Anda akan menandai pengajuan ini sebagai disetujui',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, setujui',
            cancelButtonText: 'Batal'
        }).then(function(result){
            if (!result.value) return;
            $.ajax({
                url: '/finance/pengajuan-dana/' + id + '/approve',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                beforeSend: function(){
                    // optional UI feedback
                },
                success: function(res){
                    Swal.fire('Disetujui', res.message || 'Pengajuan telah disetujui', 'success');
                    table.ajax.reload(null, false);
                },
                error: function(xhr){
                    if (xhr.status === 422 || xhr.status === 400) {
                        Swal.fire('Gagal', xhr.responseJSON.message || 'Validasi gagal', 'warning');
                    } else {
                        Swal.fire('Error', 'Terjadi kesalahan pada server', 'error');
                    }
                }
            });
        });
    });

    // Decline pengajuan (simple confirm; no reason required)
    $('#pengajuanTable').on('click', '.decline-pengajuan', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Tolak pengajuan?',
            text: 'Anda akan menolak pengajuan ini.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Tolak',
            cancelButtonText: 'Batal'
        }).then(function(result){
            if (!result.value) return; // cancelled
            $.ajax({
                url: '/finance/pengajuan-dana/' + id + '/decline',
                method: 'POST',
                data: {},
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(res){
                    Swal.fire('Ditolak', res.message || 'Pengajuan telah ditolak', 'success');
                    table.ajax.reload(null, false);
                },
                error: function(xhr){
                    if (xhr.status === 422 || xhr.status === 400) {
                        Swal.fire('Gagal', xhr.responseJSON.message || 'Validasi gagal', 'warning');
                    } else {
                        Swal.fire('Error', 'Terjadi kesalahan pada server', 'error');
                    }
                }
            });
        });
    });

    // Upload bukti handler - open file picker and POST files to upload endpoint
    $('#pengajuanTable').on('click', '.upload-bukti', function() {
        // open Bukti modal and load existing images; allow uploading more
        var id = $(this).data('id');
        if (!id) return;
        // clear modal state
        $('#buktiModal').data('id', id);
        $('#buktiModalPreview').empty();
        $('#buktiModalInput').val('');
        $('#buktiModalFilesLabel').text('Tidak ada file terpilih');

        // fetch existing bukti via the pengajuan GET endpoint (same used for edit)
        $.ajax({
            url: '/finance/pengajuan-dana/' + id,
            method: 'GET',
            success: function(res){
                if (!res) return;
                var preview = $('#buktiModalPreview');
                preview.empty();
                try {
                    var arr = null;
                    if (res.bukti_transaksi) {
                        arr = (typeof res.bukti_transaksi === 'string') ? JSON.parse(res.bukti_transaksi) : res.bukti_transaksi;
                    }
                    if (Array.isArray(arr)) {
                        arr.forEach(function(p){ if (!p) return; var $img = $('<img>').attr('src','/storage/' + p).css({'max-width':'160px','max-height':'120px','margin-right':'8px','margin-bottom':'8px'}); preview.append($img); });
                    } else if (res.bukti_transaksi) {
                        var url = '/storage/' + res.bukti_transaksi;
                        preview.append($('<img>').attr('src', url).css({'max-width':'160px','max-height':'120px'}));
                    }
                } catch(e) {
                    var url = '/storage/' + res.bukti_transaksi;
                    preview.append($('<img>').attr('src', url).css({'max-width':'160px','max-height':'120px'}));
                }
                $('#buktiModal').modal('show');
            },
            error: function(){ Swal.fire('Error', 'Gagal memuat bukti', 'error'); }
        });
    });

    // file input change inside bukti modal - update label and preview of selected files
    $(document).on('change', '#buktiModalInput', function(e){
        var files = this.files || [];
        var $label = $('#buktiModalFilesLabel');
        var $preview = $('#buktiModalNewPreview');
        $preview.empty();
        if (!files || files.length === 0) {
            $label.text('Tidak ada file terpilih');
            return;
        }
        if (files.length === 1) $label.text(files[0].name); else $label.text(files.length + ' file terpilih');
        Array.from(files).forEach(function(file){ if (!file.type || file.type.indexOf('image') === -1) return; var reader = new FileReader(); reader.onload = function(evt){ var $img = $('<img>').attr('src', evt.target.result).css({'max-width':'120px','max-height':'90px','margin-right':'6px','margin-bottom':'6px'}); $preview.append($img); }; reader.readAsDataURL(file); });
    });

    // Upload files from Bukti modal
    $(document).on('click', '#buktiModalUpload', function(){
        var id = $('#buktiModal').data('id');
        if (!id) return;
        var input = document.getElementById('buktiModalInput');
        var files = input.files || [];
        if (!files || files.length === 0) {
            Swal.fire('Info', 'Pilih file terlebih dahulu', 'info');
            return;
        }
        var fd = new FormData();
        Array.from(files).forEach(function(f){ fd.append('bukti_transaksi[]', f); });
        $.ajax({
            url: '/finance/pengajuan-dana/' + id + '/upload-bukti',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            beforeSend: function(){ Swal.fire({ title: 'Uploading...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } }); },
            success: function(res){
                Swal.fire('Sukses', res.message || 'Upload berhasil', 'success');
                $('#buktiModal').modal('hide');
                table.ajax.reload(null, false);
            },
            error: function(xhr){ var msg = 'Terjadi kesalahan pada server'; if (xhr && xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message; Swal.fire('Error', msg, 'error'); }
        });
    });

    // Clear date filter button
    $(document).on('click', '#clearFilterTanggal', function() {
        $('#filter_tanggal').val('');
        table.ajax.reload();
    });

    // Approval status filter change -> reload table
    $(document).on('change', '#filter_approval', function() {
        table.ajax.reload();
    });
    // Jenis filter change -> reload table
    $(document).on('change', '#filter_jenis', function() {
        table.ajax.reload();
    });
    // Sumber Dana filter change -> reload table
    $(document).on('change', '#filter_sumber', function() {
        table.ajax.reload();
    });

    // Footer cancel button: hide modal (will trigger existing hidden.bs.modal reset logic)
    $(document).on('click', '#btnCancelPengajuan', function() {
        $('#pengajuanModal').modal('hide');
    });

    // Initialize inline Select2 for faktur search (in footer)
    if (typeof $.fn.select2 === 'function') {
        $('#select_faktur_inline').select2({
            dropdownParent: $('#pengajuanModal'),
            width: 'resolve',
            placeholder: $('#select_faktur_inline').data('placeholder') || 'Cari faktur...',
            minimumInputLength: 2,
            ajax: {
                url: '{!! route('erm.fakturbeli.select2') !!}',
                dataType: 'json',
                delay: 250,
                data: function(params) { return { q: params.term }; },
                processResults: function(data) { return data; },
                cache: true
            }
        });

        // when a faktur is selected, fetch its JSON and insert a readonly row
        $('#select_faktur_inline').on('select2:select', function(e) {
            var id = e.params && e.params.data && e.params.data.id;
            if (!id) return;
            $.ajax({
                url: '/erm/fakturpembelian/' + id + '/json',
                method: 'GET',
                        success: function(res) {
                            // build description like: "Faktur: {no_faktur} (item1, item2, item3)"
                            var no = res.no_faktur || id || '';
                            var price = parseFloat(res.total || 0);
                            // try multiple common locations for item arrays (robust against varying payloads)
                            var itemArray = [];
                            var tryPaths = [
                                ['items'],
                                ['data','items'],
                                ['items_list'],
                                ['lines'],
                                ['detail'],
                                ['detail_items'],
                                ['items_pembelian'],
                                ['itemsList'],
                                ['itemsPembelian'],
                                ['items_array']
                            ];
                            tryPaths.some(function(path){
                                var o = res;
                                for (var i=0;i<path.length;i++) {
                                    if (o && Object.prototype.hasOwnProperty.call(o, path[i])) {
                                        o = o[path[i]];
                                    } else { o = null; break; }
                                }
                                if (Array.isArray(o) && o.length) { itemArray = o; return true; }
                                return false;
                            });

                            // if still empty, try to detect the first array-valued property on res
                            if (!itemArray.length) {
                                for (var k in res) {
                                    if (!res.hasOwnProperty(k)) continue;
                                    if (Array.isArray(res[k]) && res[k].length) { itemArray = res[k]; break; }
                                }
                            }

                            // if items is a JSON string, try parsing
                            if (!itemArray.length && typeof res.items === 'string') {
                                try { var parsed = JSON.parse(res.items); if (Array.isArray(parsed)) itemArray = parsed; } catch(e){}
                            }

                            var itemNames = [];
                            if (Array.isArray(itemArray) && itemArray.length) {
                                itemNames = itemArray.map(function(it){
                                    if (!it) return null;
                                    if (typeof it === 'string') return it;
                                    return it.nama_item || it.nama || it.nama_barang || it.barang_nama || it.obat_nama || it.name || it.item_name || it.description || it.keterangan || it.label || null;
                                }).filter(function(x){ return x && x.toString().trim() !== ''; });
                            }
                            // use full item list for the nama_item (it's stored as TEXT in DB)
                            var fullList = itemNames.join(', ');
                            var desc = 'Faktur: ' + no + (fullList ? ' (' + fullList + ')' : '');
                            var $tbody = $('#itemsTable tbody');
                            // prefer reusing the first empty row (description empty). If none, append a new row.
                            var $empty = $tbody.find('tr').filter(function() {
                                var v = $(this).find('.item-desc').val() || '';
                                return v.toString().trim() === '';
                            }).first();

                            if ($empty.length) {
                                // populate the empty row; store full description and also set title for full view
                                $empty.find('.item-desc').val(desc).prop('readonly', true).attr('title', desc);
                                $empty.find('.item-qty').val(1).prop('readonly', true);
                                $empty.find('.item-price').val(price).prop('readonly', true);
                                $empty.find('.item-total').val((1 * price).toFixed(2));
                                $empty.data('fakturbeli-id', res.id);
                            } else {
                                // no empty row; append a new faktur row (do not default per-item employee)
                                addItemRow({ desc: desc, qty: 1, price: price });
                                var $new = $tbody.find('tr').last();
                                $new.data('fakturbeli-id', res.id);
                                $new.find('.item-desc').prop('readonly', true).attr('title', desc);
                                $new.find('.item-qty').prop('readonly', true).val(1);
                                $new.find('.item-price').prop('readonly', true).val(price);
                            }

                            recalcItems();
                            // clear selection to allow adding another
                            $('#select_faktur_inline').val(null).trigger('change');
                        },
                error: function() {
                    Swal.fire('Error', 'Gagal mengambil data faktur', 'error');
                }
            });
        });
    }

    // Show approvals modal when badge/button clicked
    function _esc(s) { return $('<div>').text(s || '').html(); }
    $(document).on('click', '.show-approvals', function(e){
        e.preventDefault();
        var id = $(this).data('id');
        if (!id) return;
        var url = '{{ url('finance/pengajuan-dana') }}' + '/' + id + '/approvals';
        $.get(url, function(res, status, xhr){
            // if response is HTML (e.g., redirect to login), treat as failure
            var contentType = (xhr && xhr.getResponseHeader) ? xhr.getResponseHeader('Content-Type') : '';
            if (contentType && contentType.indexOf('application/json') === -1) {
                // not JSON — likely a redirect or error page
                Swal.fire('Error', 'Gagal memuat data persetujuan', 'error');
                return;
            }
            if (!res || !res.success) {
                Swal.fire('Error', 'Gagal memuat data persetujuan', 'error');
                return;
            }
            var list = res.data || [];
            var html = '';
            html += '<div class="modal fade" id="approvalsModal" tabindex="-1" aria-hidden="true">';
            html += '<div class="modal-dialog">';
            html += '<div class="modal-content">';
            html += '<div class="modal-header">';
            html += '<h5 class="modal-title">Daftar Persetujuan</h5>';
            html += '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
            html += '</div>';
            html += '<div class="modal-body">';
            html += '<table class="table table-sm table-bordered">';
            html += '<thead><tr><th style="width:6%">#</th><th>Nama</th><th>Jabatan</th><th style="width:28%">Tanggal</th><th style="width:8%">Status</th></tr></thead>';
            html += '<tbody>';
            if (list.length) {
                // group by tingkat (level)
                var groups = {};
                list.forEach(function(it){
                    var lvl = (typeof it.tingkat !== 'undefined' && it.tingkat !== null && it.tingkat !== '') ? it.tingkat : '0';
                    if (!groups[lvl]) groups[lvl] = [];
                    groups[lvl].push(it);
                });
                // sort tingkat numeric ascending
                var levels = Object.keys(groups).sort(function(a,b){ return Number(a) - Number(b); });
                var counter = 1;
                levels.forEach(function(lvl){
                    // group header row to indicate tingkat
                    html += '<tr class="table-secondary"><td colspan="4"><strong>Tingkat ' + _esc(lvl) + '</strong></td><td></td></tr>';
                    groups[lvl].forEach(function(it){
                        html += '<tr>';
                        html += '<td>' + (counter++) + '</td>';
                        html += '<td>' + _esc(it.name) + '</td>';
                        html += '<td>' + _esc(it.jabatan) + '</td>';
                        html += '<td>' + _esc(it.date) + '</td>';
                        var icon = '';
                        try {
                            if (it.status === 'approved') {
                                icon = '<i class="fa fa-check-circle text-success" title="Disetujui"></i>';
                            } else if (it.status === 'declined' || it.status === 'rejected') {
                                icon = '<i class="fa fa-times-circle text-danger" title="Ditolak"></i>';
                            } else {
                                icon = '<i class="fa fa-clock text-muted" title="Menunggu"></i>';
                            }
                        } catch(e) { icon = ''; }
                        html += '<td class="text-center">' + icon + '</td>';
                        html += '</tr>';
                    });
                });
            } else {
                html += '<tr><td colspan="5" class="text-center">Belum ada persetujuan</td></tr>';
            }
            html += '</tbody></table>';
            // informational note: only one approval needed per tingkat (styled red)
            html += '<div class="mt-2"><small class="text-danger">Catatan: Hanya perlu 1 approval tiap tingkat.</small></div>';
            html += '</div>';
            html += '<div class="modal-footer">';
            html += '<button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>';
            html += '</div></div></div></div>';

            // ensure only one approvals modal exists
            $('#approvalsModal').remove();
            $('body').append(html);
            $('#approvalsModal').modal({ backdrop: 'static', keyboard: false });
            $('#approvalsModal').modal('show');
        }).fail(function(){
            Swal.fire('Error', 'Gagal memuat data persetujuan', 'error');
        });
    });

});
</script>
@endsection
