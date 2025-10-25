@extends('layouts.finance.app')
@section('title', 'Finance | Pengajuan Dana')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection

@section('content')
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
                                <button type="button" class="btn btn-outline-secondary btn-sm mr-2" id="clearFilterTanggal" title="Clear filter">Clear</button>
                                <button type="button" class="btn btn-primary ml-2" id="btnAddPengajuan">
                                    <i class="fas fa-plus mr-1"></i> Buat Pengajuan
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="pengajuanTable" class="table table-bordered dt-responsive nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Kode</th>
                                        <th>Employee</th>
                                        <th>Tanggal</th>
                                        <th>Jenis</th>
                                        <th>Items</th>
                                        <th>Grand Total</th>
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
                    <div class="form-row g-2 pengajuan-compact">
                        <div class="col-md-4">
                            <label for="kode_pengajuan">Kode Pengajuan</label>
                            <input type="text" class="form-control" id="kode_pengajuan" name="kode_pengajuan" readonly>
                        </div>

                        <div class="col-md-4">
                            <label for="rekening_id">Rekening (Bank / No. Rekening / Atas Nama)</label>
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
                            <label for="bukti_transaksi">Bukti Transaksi (Gambar) - bisa pilih beberapa file</label>
                            <input type="file" class="form-control" id="bukti_transaksi" name="bukti_transaksi[]" accept="image/*" multiple>
                            <small class="form-text text-muted">Maks 2MB per file. Format: jpg, png, gif.</small>
                            <div id="bukti_preview" class="mt-1" style="display:none">
                                <!-- multiple thumbnails will be injected here -->
                            </div>
                        </div>
                    </div>

                    <div class="form-row g-2 mt-2 pengajuan-compact">
                        <div class="col-md-4">
                            <label for="employee_id">Employee</label>
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
                        <div class="col-md-3">
                            <label for="division_id">Division</label>
                            <select id="division_id" name="division_id" class="form-control select2" style="width:100%">
                                <option value="">-- Pilih Division --</option>
                                @php $divs = \App\Models\HRD\Division::orderBy('name')->get(); @endphp
                                @foreach($divs as $d)
                                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="tanggal_pengajuan">Tanggal</label>
                            <input type="date" class="form-control" id="tanggal_pengajuan" name="tanggal_pengajuan">
                        </div>
                        <div class="col-md-3">
                            <label for="jenis_pengajuan">Jenis</label>
                            <select id="jenis_pengajuan" name="jenis_pengajuan" class="form-control">
                                <option value="Operasional">Operasional</option>
                                <option value="Pembelian">Pembelian</option>
                                <option value="Remburse">Remburse</option>
                            </select>
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
                                        <th>Pegawai</th>
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
                                        <td colspan="5" class="text-end"><strong>Grand Total</strong></td>
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
        $('#employee_id, #division_id, #rekening_id').select2({ dropdownParent: $('#pengajuanModal'), width: '100%' });
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
            }
        },
        columns: [
            // render a sequential row number instead of DB id
            { data: 'id', name: 'id', render: function(data, type, row, meta) {
                    return meta.settings._iDisplayStart + meta.row + 1;
                }
            },
        { data: 'kode_pengajuan', name: 'kode_pengajuan', orderable: false },
        { data: 'employee_display', name: 'employee_display', defaultContent: '', orderable: false },
        // format tanggal_pengajuan for display as '1 Januari 2025' (Indonesian)
        { data: 'tanggal_pengajuan', name: 'tanggal_pengajuan', render: function(data, type, row, meta) {
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
            { data: 'jenis_pengajuan', name: 'jenis_pengajuan' },
            { data: 'items_list', name: 'items_list', orderable: false, searchable: false },
            { data: 'grand_total', name: 'grand_total', render: function(data, type, row, meta) {
                    if (data === null || data === undefined) return '0.00';
                    if (type === 'display' || type === 'filter') {
                        try {
                            var n = Number(data);
                            return n.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        } catch (e) {
                            return data;
                        }
                    }
                    return data;
                }, orderable: false, searchable: false },
            // server returns rendered HTML list for approvals (approver name + date)
            { data: 'approvals_list', name: 'approvals_list', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
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
        // clear rekening select, file input and preview, grand total and hidden items_json
        if (typeof $('#rekening_id').select2 === 'function') { $('#rekening_id').val('').trigger('change'); }
        $('#bukti_transaksi').val('');
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
        if (typeof $('#division_id').select2 === 'function') { $('#division_id').val('').trigger('change'); }
        if (typeof $('#rekening_id').select2 === 'function') { $('#rekening_id').val('').trigger('change'); }
        $('#bukti_transaksi').val('');
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
        data = data || {desc:'', qty:'', price:0};
        var $tr = $('<tr>');
        $tr.append('<td class="align-middle text-center"></td>');
        $tr.append('<td><input type="text" class="form-control item-desc" placeholder="Nama Item" value="'+(data.desc||'')+'"></td>');
        // employee select: clone options from main #employee_id to keep consistency
        var empOptions = '';
        try { if ($('#employee_id').length) empOptions = $('#employee_id').html(); } catch(e) { empOptions = ''; }
        $tr.append('<td><select class="form-control item-employee" name="item_employee_id[]"><option value="">-- Pilih Pegawai --</option>'+empOptions+'</select></td>');
        $tr.append('<td><input type="number" min="0" step="1" class="form-control item-qty" value="'+(data.qty||'')+'"></td>');
        $tr.append('<td><input type="number" min="0" step="0.01" class="form-control item-price" value="'+(data.price||0)+'"></td>');
        $tr.append('<td><input type="text" readonly class="form-control item-total" value="0.00"></td>');
        // nicer remove button with icon; we handle last-row protection below
        $tr.append('<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-item" title="Hapus item"><i class="fa fa-trash"></i></button></td>');
        $('#itemsTable tbody').append($tr);
        // focus the newly added row's description for quick entry
        $tr.find('.item-desc').focus();
        // initialize select2 for the dynamic employee select if available
        if (typeof $.fn.select2 === 'function') {
            $tr.find('.item-employee').select2({ dropdownParent: $('#pengajuanModal'), width: '100%' });
            // only set per-item employee when explicit data.employee_id is provided (edit flow)
            if (data.employee_id) {
                $tr.find('.item-employee').val(data.employee_id).trigger('change');
            }
        } else {
            if (data.employee_id) {
                $tr.find('.item-employee').val(data.employee_id);
            }
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
            var empId = $tr.find('.item-employee').val() || null;
            // If the row is a faktur row, we embed fakturbeli_id into payload
            var fakturId = $tr.data('fakturbeli-id') || null;
            if (descTrim !== '') {
                if (fakturId) {
                    items.push({desc: descTrim, qty: 1, price: price || 0, fakturbeli_id: fakturId, employee_id: empId});
                } else {
                    items.push({desc: descTrim, qty: qty || 0, price: price || 0, employee_id: empId});
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
                $('#rekening_id').val(res.rekening_id).trigger('change');
                $('#nama_bank').val(res.nama_bank || '');
                $('#no_rekening').val(res.no_rekening || '');
                $('#atas_nama').val(res.atas_nama || '');
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
                        } else {
                            // treat as single path
                            var url = '/storage/' + res.bukti_transaksi;
                            preview.append($('<img>').attr('src', url).css({ 'max-width':'120px', 'max-height':'80px' }));
                            preview.show();
                        }
                    } catch (e) {
                        // fallback: treat as single path string
                        var url = '/storage/' + res.bukti_transaksi;
                        preview.append($('<img>').attr('src', url).css({ 'max-width':'120px', 'max-height':'80px' }));
                        preview.show();
                    }
                } else {
                    $('#bukti_preview').hide();
                    $('#bukti_preview img').attr('src', '');
                }
                // populate items table
                $('#itemsTable tbody').empty();
                if (res.items && res.items.length) {
                    res.items.forEach(function(it){
                        var rowData = { desc: it.nama_item, qty: it.jumlah, price: it.harga_satuan, employee_id: it.employee_id || '' };
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

    // Clear date filter button
    $(document).on('click', '#clearFilterTanggal', function() {
        $('#filter_tanggal').val('');
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
});
</script>
@endsection
