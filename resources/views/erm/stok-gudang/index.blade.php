@extends('layouts.erm.app')
@section('title', 'Farmasi | Manajemen Stok')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection
@section('content')
<div class="container-fluid">
    <!-- Page-Title -->
    <style>
        /* Fix table layout and column widths so long names wrap into multiple lines */
        #stok-table { table-layout: fixed !important; width: 100% !important; }
        #stok-table th, #stok-table td { overflow: hidden; }
        /* Clickable headers: pointer cursor for sortable feel */
        #stok-table th { cursor: pointer; }
        /* Column widths (adjust proportions) */
        #stok-table th:nth-child(1), #stok-table td:nth-child(1) { width: 50%; white-space: normal; word-break: break-word; }
        #stok-table th:nth-child(2), #stok-table td:nth-child(2) { width: 10%; white-space: nowrap; text-align: right; }
        #stok-table th:nth-child(3), #stok-table td:nth-child(3) { width: 10%; white-space: nowrap; text-align: right; }
        #stok-table th:nth-child(4), #stok-table td:nth-child(4) { width: 18%; white-space: nowrap; }
        /* Make actions column slightly wider and allow overflow so buttons are not clipped */
        #stok-table th:nth-child(5), #stok-table td:nth-child(5) { width: 12%; white-space: nowrap; text-align: center; overflow: visible; }
        /* Ensure button itself doesn't wrap and displays correctly */
        #stok-table td:nth-child(6) .btn { white-space: nowrap; display: inline-block; }
        #stok-table td { vertical-align: middle; }
        /* Emphasize main columns: Nama Obat link and Stok value */
        #stok-table td:nth-child(1) a { font-weight: 700; }
        #stok-table td:nth-child(2) { font-weight: 700; }
        /* Ensure buttons don't force expansion, but allow obat name links to wrap */
        #stok-table .btn { white-space: nowrap; }
        #stok-table td:nth-child(2) a { white-space: normal; display: inline-block; max-width:100%; overflow-wrap: anywhere; word-wrap: break-word; }
        /* Compact filter styles */
        .compact-filters .form-group { margin-bottom: 6px; }
        .compact-filters label { font-size: 12px; font-weight: 600; margin-bottom: 4px; }
        .compact-filters .form-control-sm { height: calc(1.6rem + 2px); padding: .25rem .5rem; font-size: .875rem; }
        .compact-filters .btn-sm { padding: .35rem .55rem; font-size: .85rem; }
        .compact-filters .form-check-label { font-size: 13px; font-weight:700; }
        .compact-filters .form-text { font-size: 11px; margin-top: 2px; }
    </style>

    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <h4 class="page-title">Manajemen Stok</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Farmasi</a></li>
                            <li class="breadcrumb-item active">Manajemen Stok</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center mb-3">
                        <div class="col">
                            <h4 class="card-title">Data Stok Obat per Gudang</h4>
                        </div>
                    </div>
                    <!-- Filter Row (compact) -->
                    <div class="row compact-filters">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filter_gudang">Pilih Gudang</label>
                                <select class="form-control form-control-sm" id="filter_gudang">
                                    @foreach($gudangs as $gudang)
                                        <option value="{{ $gudang->id }}" {{ $gudang->id === $defaultGudang->id ? 'selected' : '' }}>
                                            {{ $gudang->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="search_obat">Cari Obat</label>
                                <input type="text" class="form-control form-control-sm" id="search_obat" placeholder="Ketik nama obat atau kode...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filter_kategori">Filter Kategori</label>
                                <select class="form-control form-control-sm" id="filter_kategori">
                                    <option value="">Semua Kategori</option>
                                    @foreach($kategoris as $kategori)
                                        <option value="{{ $kategori }}">{{ $kategori }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end justify-content-end">
                            <div class="form-group mb-0 d-flex align-items-center">
                                <!-- Hidden checkbox remains for existing JS listeners; toggle via icon button -->
                                <input type="checkbox" id="hide_inactive_obat" checked style="display:none;" />
                                <button type="button" id="btn-toggle-hide-inactive" class="btn btn-outline-primary btn-sm mr-2" data-toggle="tooltip" title="">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-secondary btn-sm" id="btn-reset-filter">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                    <button type="button" class="btn btn-success btn-sm ml-2" id="btn-download-stok">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;" id="stok-table">
                            <thead>
                                <tr>
                                    <th>Nama Obat</th>
                                    <th>Stok</th>
                                    <th>HPP</th>
                                    <th>Nilai Stok</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Detail Kartu Stok</h4>
                </div>
                <div class="card-body" id="kartu-stok-panel">
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-info-circle fa-2x mb-2"></i>
                        <div>Pilih sebuah obat dari daftar di kiri untuk melihat kartu stok detail di sini.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

                    <!-- Modal Download Data Stok -->
                    <div class="modal fade" id="downloadStokModal" tabindex="-1" role="dialog" aria-labelledby="downloadStokLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="downloadStokLabel">Download Data Stok</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form id="form-download-stok" class="form-horizontal">
                                        <div class="form-group">
                                            <label>Pilih Gudang</label>
                                            <select class="form-control" id="download_gudang" name="gudang_id">
                                                <option value="">Semua Gudang</option>
                                                @foreach($gudangs as $g)
                                                    <option value="{{ $g->id }}">{{ $g->nama }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Tipe Data</label>
                                            <select class="form-control" id="download_type" name="type">
                                                <option value="1">Live Data (Hari Ini)</option>
                                                <option value="2">Stok Opname (Stok Fisik)</option>
                                                <option value="3">Data Tanggal Tertentu</option>
                                            </select>
                                        </div>
                                        <div class="form-row date-range">
                                            <div class="form-group col-md-6">
                                                <label>Tanggal Mulai</label>
                                                <input type="date" class="form-control" id="download_date_start" name="date_start" />
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Tanggal Selesai</label>
                                                <input type="date" class="form-control" id="download_date_end" name="date_end" />
                                            </div>
                                        </div>

                                        <div class="form-group opname-group" style="display:none;">
                                            <label>Pilih Stok Opname</label>
                                            <select class="form-control" id="download_opname" name="stok_opname_id">
                                                <option value="">-- Pilih Stok Opname --</option>
                                                @foreach($stokOpnames as $op)
                                                    @php $gName = $op->gudang ? $op->gudang->nama : 'Gudang ID '.$op->gudang_id; @endphp
                                                    <option value="{{ $op->id }}" data-gudang="{{ $op->gudang_id }}">{{ $op->tanggal_opname }} — {{ $gName }} @if($op->notes) ({{ Str::limit($op->notes,30) }}) @endif</option>
                                                @endforeach
                                            </select>
                                            <small class="form-text text-muted">Pilih stok opname untuk mendownload stok fisik yang tercatat pada opname tersebut.</small>
                                        </div>
                                        <!-- Specific date group: used when type == 3 -->
                                        <div class="form-group specific-date-group" style="display:none;">
                                            <label>Tanggal Acuan</label>
                                            <input type="date" class="form-control" id="download_pivot_date" name="pivot_date" />
                                            <small class="form-text text-muted">Hitung stok per tanggal ini: menggunakan stok live saat ini lalu dikoreksi dengan kartu stok (masuk/keluar) setelah tanggal acuan.</small>
                                        </div>
                                    </form>
                                    <div class="text-muted small">Tipe <strong>Stok Opname</strong> akan men-download stok fisik yang tercatat pada stok opname dalam rentang tanggal yang dipilih. <strong>Live Data</strong> menggunakan stok saat ini (default hari ini). <strong>Data Tanggal Tertentu</strong> menghitung stok pada tanggal acuan dengan menyesuaikan transaksi setelah tanggal tersebut.</div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" id="confirm-download-stok"><i class="fas fa-download"></i> Unduh</button>
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                </div>
                            </div>
                        </div>
                    </div>
</div>

<!-- Nilai Stok Gudang & Keseluruhan -->
<div class="row mt-3">
    <div class="col-md-6">
        <div class="card shadow-sm border-left-primary">
            <div class="card-body d-flex align-items-center">
                <div class="mr-3">
                    <i class="fas fa-warehouse fa-2x text-primary"></i>
                </div>
                <div>
                    <div class="text-muted small">Nilai Stok Gudang Terpilih</div>
                    <div class="h4 mb-0 font-weight-bold" id="nilai-stok-gudang">Rp 0</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm border-left-success">
            <div class="card-body d-flex align-items-center">
                <div class="mr-3">
                    <i class="fas fa-coins fa-2x text-success"></i>
                </div>
                <div>
                    <div class="text-muted small">Nilai Stok Keseluruhan</div>
                    <div class="h4 mb-0 font-weight-bold" id="nilai-stok-keseluruhan">Rp 0</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Edit Min/Max -->
<div class="modal fade" id="editMinMaxModal" tabindex="-1" role="dialog" aria-labelledby="editMinMaxLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editMinMaxLabel">Edit Min / Max Stok</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-edit-minmax">
                    <input type="hidden" id="minmax_obat_id" name="obat_id" />
                    <input type="hidden" id="minmax_gudang_id" name="gudang_id" />
                    <div class="form-group">
                        <label for="min_stok">Min Stok</label>
                        <input type="number" step="1" min="0" class="form-control" id="min_stok" name="min_stok" />
                    </div>
                    <div class="form-group">
                        <label for="max_stok">Max Stok</label>
                        <input type="number" step="1" min="0" class="form-control" id="max_stok" name="max_stok" />
                    </div>
                </form>
                <div id="minmaxAlert"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="save-minmax-btn"><i class="fas fa-save"></i> Simpan</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Batch Details -->
<div class="modal fade" id="batchDetailsModal" tabindex="-1" role="dialog" aria-labelledby="batchDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="batchDetailsTitle">Detail Batch</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="batchDetailsContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="btn-save-batch-changes" style="display: none;">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
$(document).ready(function() {
    // Initialize select2
    $('.select2').select2();

    // Initialize DataTable
    var table = $('#stok-table').DataTable({
        processing: true,
        serverSide: true,
        searching: false, // Disable built-in search
        autoWidth: false,
        ajax: {
            url: '{{ route("erm.stok-gudang.data") }}',
            data: function(d) {
                d.gudang_id = $('#filter_gudang').val();
                d.search_obat = $('#search_obat').val();
                    d.kategori = $('#filter_kategori').val();
                d.hide_inactive = $('#hide_inactive_obat').is(':checked') ? 1 : 0;
            }
        },
        columns: [
            { data: 'nama_obat', name: 'nama_obat', searchable: false },
            { data: 'total_stok', name: 'total_stok', searchable: false },
            { data: 'hpp', name: 'hpp', searchable: false, orderable: true },
            { data: 'nilai_stok', name: 'nilai_stok', searchable: false, orderable: true },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'asc']],
        pageLength: 25,
        responsive: true,
        drawCallback: function(settings) {
            // Re-initialize tooltips after table redraw
            $('[data-toggle="tooltip"]').tooltip();
            feather.replace();
        }
    });


    // Reload table and update nilai stok when warehouse filter changes
    $('#filter_gudang').change(function() {
        table.ajax.reload();
        updateNilaiStok();
    });

    // Initial load of nilai stok
    updateNilaiStok();

    function updateNilaiStok() {
        var gudangId = $('#filter_gudang').val();
        $.ajax({
            url: '{{ route("erm.stok-gudang.nilai-stok") }}',
            type: 'GET',
            data: { gudang_id: gudangId },
            success: function(response) {
                $('#nilai-stok-gudang').text('Rp ' + numberFormat(response.nilai_gudang));
                $('#nilai-stok-keseluruhan').text('Rp ' + numberFormat(response.nilai_keseluruhan));
            },
            error: function() {
                $('#nilai-stok-gudang').text('Rp 0');
                $('#nilai-stok-keseluruhan').text('Rp 0');
            }
        });
    }

    function numberFormat(x) {
        if (x === null || x === undefined || x === '') return '0.00';
        var n = parseFloat(x);
        if (isNaN(n)) return '0.00';
        return n.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 4 });
    }

    // Search obat with delay
    var searchTimeout;
    $('#search_obat').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            table.ajax.reload();
        }, 500); // 500ms delay
    });

    // Kategori filter: reload table when changed (server-side filter applied)
    $('#filter_kategori').change(function() {
        table.ajax.reload();
    });

    // Reload table when checkbox filter changes
    $('#hide_inactive_obat').change(function() {
        table.ajax.reload();
    });

    // Toggle hidden checkbox when icon button is clicked and update icon/tooltip
    function updateHideIcon() {
        var checked = $('#hide_inactive_obat').is(':checked');
        var btn = $('#btn-toggle-hide-inactive');
        // If checked => we are HIDING inactive obat, so show eye-slash (means hidden)
        if (checked) {
            btn.find('i').removeClass('fa-eye').addClass('fa-eye-slash');
            btn.attr('title', 'Tampilkan obat yang tidak aktif');
            btn.removeClass('btn-outline-primary').addClass('btn-outline-secondary');
        } else {
            // Not checked => inactive are visible, show regular eye icon
            btn.find('i').removeClass('fa-eye-slash').addClass('fa-eye');
            btn.attr('title', 'Sembunyikan obat yang tidak aktif');
            btn.removeClass('btn-outline-secondary').addClass('btn-outline-primary');
        }
        // Re-init tooltip
        try { btn.tooltip('dispose'); } catch(e) {}
        btn.tooltip();
    }

    $('#btn-toggle-hide-inactive').on('click', function() {
        var cb = $('#hide_inactive_obat');
        cb.prop('checked', !cb.is(':checked'));
        cb.trigger('change');
        updateHideIcon();
    });

    // Initialize icon state
    updateHideIcon();

    // Reset filter button
    $('#btn-reset-filter').click(function() {
        $('#search_obat').val('');
        $('#filter_kategori').val('');
        $('#hide_inactive_obat').prop('checked', true); // Reset to default (checked)
        table.ajax.reload();
    });

    

    // Handle batch details button click
    $(document).on('click', '.show-batch-details', function() {
        var obatId = $(this).data('obat-id');
        var gudangId = $(this).data('gudang-id');
        
        $.ajax({
            url: '{{ route("erm.stok-gudang.batch-details") }}',
            type: 'GET',
            data: {
                obat_id: obatId,
                gudang_id: gudangId
            },
            success: function(response) {
                $('#batchDetailsTitle').text('Detail Batch - ' + response.obat + ' (' + response.gudang + ')');
                
                var tableHtml = '<div class="table-responsive">';
                tableHtml += '<table class="table table-bordered" id="batch-table">';
                tableHtml += '<thead>';
                tableHtml += '<tr>';
                tableHtml += '<th width="20%">Batch</th>';
                tableHtml += '<th width="20%">Stok</th>';
                tableHtml += '<th width="20%">Tanggal Expired</th>';
                tableHtml += '<th width="20%">Status</th>';
                tableHtml += '<th width="20%">Aksi</th>';
                tableHtml += '</tr>';
                tableHtml += '</thead>';
                tableHtml += '<tbody>';
                
                response.data.forEach(function(item, index) {
                    tableHtml += '<tr data-id="' + item.id + '">';
                    tableHtml += '<td>' + item.batch + '</td>';
                    tableHtml += '<td>';
                    tableHtml += '<span class="stok-display">' + item.stok_display + '</span>';
                    // Use decimal step and allow decimal input (2 decimals)
                    tableHtml += '<input type="number" class="form-control stok-input" value="' + item.stok + '" style="display:none;" step="0.01" min="0">';
                    // Keterangan input (hidden until edit) - required when performing an edit that changes stok
                    tableHtml += '<input type="text" class="form-control form-control-sm mt-2 keterangan-input" placeholder="Keterangan (wajib saat edit)" style="display:none;">';
                    tableHtml += '</td>';
                    tableHtml += '<td>' + item.expiration_date + '</td>';
                    tableHtml += '<td>' + item.status + '</td>';
                    tableHtml += '<td>';
                    tableHtml += '<button class="btn btn-sm btn-primary btn-edit-stok" data-id="' + item.id + '">';
                    tableHtml += '<i class="fas fa-edit"></i> Edit';
                    tableHtml += '</button>';
                    tableHtml += '<button class="btn btn-sm btn-success btn-save-stok" data-id="' + item.id + '" style="display:none;">';
                    tableHtml += '<i class="fas fa-check"></i> Simpan';
                    tableHtml += '</button>';
                    tableHtml += '<button class="btn btn-sm btn-secondary btn-cancel-stok" data-id="' + item.id + '" style="display:none; margin-left: 5px;">';
                    tableHtml += '<i class="fas fa-times"></i> Batal';
                    tableHtml += '</button>';
                    tableHtml += '</td>';
                    tableHtml += '</tr>';
                });
                
                tableHtml += '</tbody></table></div>';
                tableHtml += '<div class="alert alert-info">';
                tableHtml += '<i class="fas fa-info-circle"></i> Klik tombol "Edit" untuk mengubah stok batch. Perubahan akan dicatat di kartu stok.';
                tableHtml += '</div>';
                
                $('#batchDetailsContent').html(tableHtml);
                // Normalize stok input/display values to integers (strip formatting like "1,00" or thousand separators)
                $('#batchDetailsContent').find('tr').each(function() {
                    var row = $(this);
                    var stokDisplayEl = row.find('.stok-display');
                    var stokInputEl = row.find('.stok-input');
                    if (stokDisplayEl.length && stokInputEl.length) {
                        // Take the displayed text and normalize to a float (handle thousand separators and comma decimals)
                            var displayed = stokDisplayEl.text().trim();
                            // Convert Indonesian formatting: remove thousands separators (.) and replace decimal comma with dot
                            var numeric = displayed.replace(/\./g, '').replace(/,/g, '.').replace(/[^0-9.\-]/g, '');
                            var floatVal = 0.0;
                            if (numeric !== '') {
                                floatVal = parseFloat(numeric);
                                if (isNaN(floatVal)) floatVal = 0.0;
                            }
                            // Round to 2 decimals
                            floatVal = Math.round(floatVal * 100) / 100;
                            // Update input value and display formatted with 2 decimals
                            stokInputEl.val(floatVal);
                            stokDisplayEl.text(floatVal.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 4 }));
                    }
                });

                $('#btn-save-batch-changes').hide();
                $('#batchDetailsModal').modal('show');
            },
            error: function() {
                alert('Terjadi kesalahan saat mengambil data batch');
            }
        });
    });

    // Handle kartu stok button click - load kartu stok detail into right panel
    $(document).on('click', '.btn-kartu-stok', function(e) {
        e.preventDefault();
        var obatId = $(this).data('obat-id');
        var gudangId = $(this).data('gudang-id');

        // Optional: show loading state
        $('#kartu-stok-panel').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x mb-2"></i><div>Memuat kartu stok...</div></div>');

        $.ajax({
            url: '{{ route("erm.kartustok.detail") }}',
            type: 'GET',
            data: {
                obat_id: obatId,
                gudang_id: gudangId
                // Keep date range empty (controller will use defaults). If you want to pass start/end, add inputs and send here.
            },
            success: function(response) {
                $('#kartu-stok-panel').html(response);
                // Re-initialize any tooltips or icons inside the loaded HTML
                $('[data-toggle="tooltip"]').tooltip();
                feather.replace();
            },
            error: function() {
                $('#kartu-stok-panel').html('<div class="text-danger">Gagal memuat kartu stok.</div>');
            }
        });
    });

    // Handle delete stok button click
    $(document).on('click', '.btn-delete-stok', function(e) {
        e.preventDefault();
        var btn = $(this);
        var obatId = btn.data('obat-id');
        var gudangId = btn.data('gudang-id');

        if (!confirm('Yakin ingin mengosongkan semua stok untuk obat ini di gudang terpilih? Tindakan ini tidak dapat dibatalkan.')) return;

        btn.prop('disabled', true).text('Menghapus...');

        $.ajax({
            url: '{{ route("erm.stok-gudang.delete") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                obat_id: obatId,
                gudang_id: gudangId
            },
            success: function(res) {
                if (res.success) {
                    alert(res.message || 'Stok berhasil dikosongkan');
                    table.ajax.reload();
                    updateNilaiStok();
                } else {
                    alert(res.message || 'Gagal mengosongkan stok');
                }
            },
            error: function(xhr) {
                var msg = 'Terjadi kesalahan saat menghapus stok';
                try { if (xhr && xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message; } catch(e){}
                alert(msg);
            },
            complete: function() {
                // Restore icon-only trash button
                btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
            }
        });
    });

    // Handle edit stok button
    $(document).on('click', '.btn-edit-stok', function() {
        var row = $(this).closest('tr');
        var stokDisplay = row.find('.stok-display');
        var stokInput = row.find('.stok-input');
        var keteranganInput = row.find('.keterangan-input');
        var btnEdit = row.find('.btn-edit-stok');
        var btnSave = row.find('.btn-save-stok');
        var btnCancel = row.find('.btn-cancel-stok');
        
        // Store original value for cancel
        stokInput.data('original-value', stokInput.val());
        
        // Switch to edit mode
        stokDisplay.hide();
        stokInput.show().focus();
        keteranganInput.show();
        btnEdit.hide();
        btnSave.show();
        btnCancel.show();
        
        $('#btn-save-batch-changes').show();
    });

    // Handle cancel edit
    $(document).on('click', '.btn-cancel-stok', function() {
        var row = $(this).closest('tr');
        var stokDisplay = row.find('.stok-display');
        var stokInput = row.find('.stok-input');
        var keteranganInput = row.find('.keterangan-input');
        var btnEdit = row.find('.btn-edit-stok');
        var btnSave = row.find('.btn-save-stok');
        var btnCancel = row.find('.btn-cancel-stok');
        
        // Restore original value
        stokInput.val(stokInput.data('original-value'));
        keteranganInput.val('');
        
        // Switch back to display mode
        stokDisplay.show();
        stokInput.hide();
        keteranganInput.hide();
        btnEdit.show();
        btnSave.hide();
        btnCancel.hide();
        
        // Hide save button if no more edits
        if ($('.btn-save-stok:visible').length === 0) {
            $('#btn-save-batch-changes').hide();
        }
    });

    // Handle save individual stok
    $(document).on('click', '.btn-save-stok', function() {
        var button = $(this);
        var row = button.closest('tr');
        var id = button.data('id');
        var stokBaruRaw = row.find('.stok-input').val();
        var keteranganVal = row.find('.keterangan-input').val() || '';
        // Parse as float and round to 2 decimals
        var stokBaru = parseFloat(Number(stokBaruRaw || 0));
        if (isNaN(stokBaru)) stokBaru = 0;
        stokBaru = Math.round(stokBaru * 100) / 100;

        if (isNaN(stokBaru) || stokBaru < 0) {
            alert('Stok tidak boleh negatif');
            return;
        }

        // Check whether stok actually changed; if so require keterangan
        var originalVal = parseFloat(Number(row.find('.stok-input').data('original-value') || 0));
        var selisih = Math.round((stokBaru - originalVal) * 100) / 100;
        if (selisih !== 0 && keteranganVal.trim() === '') {
            alert('Keterangan wajib diisi jika ada perubahan stok');
            return;
        }

        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: '{{ route("erm.stok-gudang.update-batch-stok") }}',
            type: 'POST',
            data: {
                keterangan: keteranganVal,
                _token: '{{ csrf_token() }}',
                id: id,
                stok: stokBaru
            },
            success: function(response) {
                if (response.success) {
                    var stokDisplay = row.find('.stok-display');
                    var stokInput = row.find('.stok-input');
                    var btnEdit = row.find('.btn-edit-stok');
                    var btnSave = row.find('.btn-save-stok');
                    var btnCancel = row.find('.btn-cancel-stok');
                    
                    // Update display with decimal formatting (2 decimals)
                    var stokFormatted = Number(stokBaru).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 4 });
                    stokDisplay.text(stokFormatted);
                    stokInput.val(stokBaru);
                    
                    // Switch back to display mode
                    stokDisplay.show();
                    stokInput.hide();
                    btnEdit.show();
                    btnSave.hide();
                    btnCancel.hide();
                    
                    // Show success message
                    var alertHtml = '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                    alertHtml += '<i class="fas fa-check-circle"></i> ' + response.message;
                    alertHtml += '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
                    alertHtml += '<span aria-hidden="true">&times;</span>';
                    alertHtml += '</button>';
                    alertHtml += '</div>';
                    $('#batchDetailsContent').prepend(alertHtml);
                    
                    // Auto remove alert after 3 seconds
                    setTimeout(function() {
                        $('.alert-success').fadeOut();
                    }, 3000);
                    
                    // Reload main table
                    table.ajax.reload(null, false);
                    
                    // Hide save button if no more edits
                    if ($('.btn-save-stok:visible').length === 0) {
                        $('#btn-save-batch-changes').hide();
                    }
                } else {
                    alert('Gagal menyimpan: ' + response.message);
                }
            },
            error: function() {
                alert('Terjadi kesalahan saat menyimpan');
            },
            complete: function() {
                button.prop('disabled', false).html('<i class="fas fa-check"></i> Simpan');
            }
        });
    });

    // Handle Edit Min/Max button click (from main table)
    $(document).on('click', '.btn-edit-minmax', function() {
        var obatId = $(this).data('obat-id');
        var gudangId = $(this).data('gudang-id');
        var minVal = $(this).data('min');
        var maxVal = $(this).data('max');

        $('#minmax_obat_id').val(obatId);
        $('#minmax_gudang_id').val(gudangId);
        $('#min_stok').val(minVal !== undefined ? minVal : '');
        $('#max_stok').val(maxVal !== undefined ? maxVal : '');
        $('#minmaxAlert').html('');
        $('#editMinMaxModal').modal('show');
    });

    // Submit min/max update
    $('#save-minmax-btn').click(function() {
        var btn = $(this);
        var form = $('#form-edit-minmax');
        var data = {
            _token: '{{ csrf_token() }}',
            obat_id: $('#minmax_obat_id').val(),
            gudang_id: $('#minmax_gudang_id').val(),
            min_stok: $('#min_stok').val(),
            max_stok: $('#max_stok').val()
        };

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: '{{ route("erm.stok-gudang.update-minmax") }}',
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    $('#minmaxAlert').html('<div class="alert alert-success">' + response.message + '</div>');
                    table.ajax.reload(null, false);
                    setTimeout(function() {
                        $('#editMinMaxModal').modal('hide');
                    }, 800);
                } else {
                    $('#minmaxAlert').html('<div class="alert alert-danger">' + (response.message || 'Gagal menyimpan') + '</div>');
                }
            },
            error: function(xhr) {
                var msg = 'Terjadi kesalahan saat menyimpan';
                if (xhr && xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                $('#minmaxAlert').html('<div class="alert alert-danger">' + msg + '</div>');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> Simpan');
            }
        });
    });

    // Download stok modal
    $('#btn-download-stok').on('click', function() {
        $('#downloadStokModal').modal('show');
    });
    function toggleDownloadFields() {
        var type = $('#download_type').val();
        if (type == '2') {
            $('.date-range').hide();
            $('.opname-group').show();
            $('.specific-date-group').hide();
        } else if (type == '3') {
            $('.date-range').hide();
            $('.opname-group').hide();
            $('.specific-date-group').show();
        } else {
            $('.date-range').show();
            $('.opname-group').hide();
            $('.specific-date-group').hide();
        }
    }

    // Initialize modal fields
    toggleDownloadFields();

    $('#download_type').on('change', function() {
        toggleDownloadFields();
    });

    // Filter opname options when gudang changes
    $('#download_gudang').on('change', function() {
        var gudang = $(this).val();
        $('#download_opname option').each(function() {
            var optGudang = $(this).data('gudang') + '';
            if (!optGudang) return; // first placeholder
            if (!gudang || optGudang === gudang) $(this).show(); else $(this).hide();
        });
        // Reset opname selection
        $('#download_opname').val('');
    });

    $('#confirm-download-stok').on('click', function() {
        var gudang = $('#download_gudang').val();
        var type = $('#download_type').val();
        var params = [];
        if (gudang) params.push('gudang_id=' + encodeURIComponent(gudang));
        params.push('type=' + encodeURIComponent(type));

        if (type == '2') {
            var opnameId = $('#download_opname').val();
            if (opnameId) params.push('stok_opname_id=' + encodeURIComponent(opnameId));
        } else if (type == '3') {
            var pivotDate = $('#download_pivot_date').val();
            if (pivotDate) params.push('pivot_date=' + encodeURIComponent(pivotDate));
        } else {
            var dateStart = $('#download_date_start').val();
            var dateEnd = $('#download_date_end').val();
            if (dateStart) params.push('date_start=' + encodeURIComponent(dateStart));
            if (dateEnd) params.push('date_end=' + encodeURIComponent(dateEnd));
        }

        var url = '{{ route("erm.stok-gudang.export") }}';
        if (params.length > 0) url += '?' + params.join('&');
        // Trigger browser download
        window.location = url;
        $('#downloadStokModal').modal('hide');
    });
});
</script>
@endsection
