@extends('layouts.erm.app')
@section('title', 'Farmasi | Stok per Gudang')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection
@section('content')
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <h4 class="page-title">Stok per Gudang</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Farmasi</a></li>
                            <li class="breadcrumb-item active">Stok per Gudang</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center mb-3">
                        <div class="col">
                            <h4 class="card-title">Data Stok Obat per Gudang</h4>
                        </div>
                    </div>
                    <!-- Filter Row -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filter_gudang">Pilih Gudang:</label>
                                <select class="form-control select2" id="filter_gudang">
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
                                <label for="search_obat">Cari Obat:</label>
                                <input type="text" class="form-control" id="search_obat" placeholder="Ketik nama obat atau kode obat...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filter_status">Filter Status:</label>
                                <select class="form-control" id="filter_status">
                                    <option value="">Semua Status</option>
                                    <option value="minimum">Stok Minimum</option>
                                    <option value="maksimum">Stok Maksimum</option>
                                    <option value="normal">Normal</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-group">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-secondary" id="btn-reset-filter">
                                        <i class="fas fa-undo"></i> Reset Filter
                                    </button>
                                    <button type="button" class="btn btn-success" id="btn-export-excel">
                                        <i class="fas fa-file-excel"></i> Export Excel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Additional Filter Row -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="hide_inactive_obat" checked>
                                <label class="form-check-label" for="hide_inactive_obat">
                                    <strong>Sembunyikan obat yang tidak aktif</strong>
                                </label>
                                <small class="form-text text-muted">Centang untuk hanya menampilkan obat yang aktif saja</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;" id="stok-table">
                            <thead>
                                <tr>
                                    <th>Kode Obat</th>
                                    <th>Nama Obat</th>
                                    <th>Gudang</th>
                                    <th>Nilai Stok</th>
                                    <th>Total Stok</th>
                                    <th>Min Stok</th>
                                    <th>Max Stok</th>
                                    <th>Status Stok</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
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
        ajax: {
            url: '{{ route("erm.stok-gudang.data") }}',
            data: function(d) {
                d.gudang_id = $('#filter_gudang').val();
                d.search_obat = $('#search_obat').val();
                d.filter_status = $('#filter_status').val();
                d.hide_inactive = $('#hide_inactive_obat').is(':checked') ? 1 : 0;
            }
        },
        columns: [
            { data: 'kode_obat', name: 'kode_obat', searchable: false },
            { data: 'nama_obat', name: 'nama_obat', searchable: false },
            { data: 'nama_gudang', name: 'nama_gudang', searchable: false },
            { data: 'nilai_stok', name: 'nilai_stok', searchable: false },
            { data: 'total_stok', name: 'total_stok', searchable: false },
            { data: 'min_stok', name: 'min_stok', searchable: false },
            { data: 'max_stok', name: 'max_stok', searchable: false },
            { 
                data: 'status_stok', 
                name: 'status_stok',
                searchable: true,
                render: function(data) {
                    return data;
                }
            },
            { 
                data: 'actions', 
                name: 'actions',
                orderable: false,
                searchable: false
            }
        ],
        order: [[1, 'asc']],
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

    // Filter status change - client side filtering
    $('#filter_status').change(function() {
        var status = $(this).val();
        if (status === '') {
            // Show all rows
            table.column(7).search('').draw();
        } else {
            // Filter by status
            var searchTerm = '';
            if (status === 'minimum') {
                searchTerm = 'Stok Minimum';
            } else if (status === 'maksimum') {
                searchTerm = 'Stok Maksimum';
            } else if (status === 'normal') {
                searchTerm = 'Normal';
            }
            table.column(7).search(searchTerm).draw();
        }
    });

    // Reload table when checkbox filter changes
    $('#hide_inactive_obat').change(function() {
        table.ajax.reload();
    });

    // Reset filter button
    $('#btn-reset-filter').click(function() {
        $('#search_obat').val('');
        $('#filter_status').val('');
        $('#hide_inactive_obat').prop('checked', true); // Reset to default (checked)
        table.ajax.reload();
    });

    // Export Excel button
    $('#btn-export-excel').click(function() {
        var gudangId = $('#filter_gudang').val();
        var searchObat = $('#search_obat').val();
        var hideInactive = $('#hide_inactive_obat').is(':checked') ? 1 : 0;

        var params = [];
        if (gudangId) params.push('gudang_id=' + encodeURIComponent(gudangId));
        if (searchObat) params.push('search_obat=' + encodeURIComponent(searchObat));
        if (hideInactive !== undefined) params.push('hide_inactive=' + encodeURIComponent(hideInactive));

        var url = '{{ route("erm.stok-gudang.export") }}';
        if (params.length) url += '?' + params.join('&');

        // Open in new tab to trigger download without interfering with current page
        window.open(url, '_blank');
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

    // Handle edit stok button
    $(document).on('click', '.btn-edit-stok', function() {
        var row = $(this).closest('tr');
        var stokDisplay = row.find('.stok-display');
        var stokInput = row.find('.stok-input');
        var btnEdit = row.find('.btn-edit-stok');
        var btnSave = row.find('.btn-save-stok');
        var btnCancel = row.find('.btn-cancel-stok');
        
        // Store original value for cancel
        stokInput.data('original-value', stokInput.val());
        
        // Switch to edit mode
        stokDisplay.hide();
        stokInput.show().focus();
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
        var btnEdit = row.find('.btn-edit-stok');
        var btnSave = row.find('.btn-save-stok');
        var btnCancel = row.find('.btn-cancel-stok');
        
        // Restore original value
        stokInput.val(stokInput.data('original-value'));
        
        // Switch back to display mode
        stokDisplay.show();
        stokInput.hide();
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
        // Parse as float and round to 2 decimals
        var stokBaru = parseFloat(Number(stokBaruRaw || 0));
        if (isNaN(stokBaru)) stokBaru = 0;
        stokBaru = Math.round(stokBaru * 100) / 100;
        
        if (isNaN(stokBaru) || stokBaru < 0) {
            alert('Stok tidak boleh negatif');
            return;
        }
        
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
        
        $.ajax({
            url: '{{ route("erm.stok-gudang.update-batch-stok") }}',
            type: 'POST',
            data: {
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
});
</script>
@endsection
