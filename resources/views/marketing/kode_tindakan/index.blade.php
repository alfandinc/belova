@extends('layouts.marketing.app')

@section('title', 'Master Kode Tindakan')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Master Kode Tindakan</h4>
            <div class="d-flex align-items-center">
                <div class="mr-2">
                    <select id="filterStatus" class="form-control form-control-sm">
                        <option value="all">Semua</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </div>
                <div class="mr-2">
                    <select id="filterObat" class="form-control form-control-sm">
                        <option value="all">Semua Obat</option>
                        <option value="has">Dengan Obat</option>
                        <option value="none">Tanpa Obat</option>
                    </select>
                </div>
                <div>
                    @if(auth()->check() && (
                        (method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('Admin')) ||
                        (isset(auth()->user()->role) && auth()->user()->role === 'Admin') ||
                        (isset(auth()->user()->is_admin) && auth()->user()->is_admin)
                    ))
                        <button class="btn btn-info" id="btnImportCsv"><i class="mdi mdi-file-import"></i> Import CSV</button>
                        <button class="btn btn-success" id="btnMakeAllActive"><i class="mdi mdi-check-circle-outline"></i> Aktifkan Semua</button>
                        <button class="btn btn-danger" id="btnMakeAllInactive"><i class="mdi mdi-close-circle-outline"></i> Nonaktifkan Semua</button>
                    @endif
                    <button class="btn btn-primary" id="btnAddKodeTindakan"><i class="mdi mdi-plus"></i> Tambah Kode Tindakan</button>
                </div>
            </div>
        </div>

                <!-- Import CSV Modal -->
                <div class="modal fade" id="importCsvModal" tabindex="-1" role="dialog" aria-labelledby="importCsvModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <form id="importCsvForm">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="importCsvModalLabel">Import Kode Tindakan dari CSV</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label for="csvFile">Pilih file CSV (kolom: nama)</label>
                                        <input type="file" id="csvFile" name="csv" accept=".csv,text/csv" class="form-control-file" required />
                                        <small class="form-text text-muted">File should contain one column with the name of the kode tindakan. Header optional.</small>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary">Import</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                        <!-- Bulk Action Modal (activate/deactivate by date-range preview) -->
                        <div class="modal fade" id="bulkActionModal" tabindex="-1" role="dialog" aria-labelledby="bulkActionModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="bulkActionModalLabel">Bulk Action Preview</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-row mb-2">
                                            <div class="form-group col-md-5">
                                                <label>Start</label>
                                                <input type="date" id="bulkStart" class="form-control">
                                            </div>
                                            <div class="form-group col-md-5">
                                                <label>End</label>
                                                <input type="date" id="bulkEnd" class="form-control">
                                            </div>
                                            <div class="form-group col-md-2 d-flex align-items-end">
                                                <button id="bulkPreviewBtn" class="btn btn-primary btn-block">Preview</button>
                                            </div>
                                        </div>
                                        <div>
                                            <table class="table table-sm table-bordered" id="bulkPreviewTable">
                                                <thead>
                                                    <tr>
                                                        <th style="width:4%"><input type="checkbox" id="bulkSelectAll"></th>
                                                        <th>Nama</th>
                                                        <th style="width:18%">Dibuat</th>
                                                        <th style="width:12%">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <input type="hidden" id="bulkSetActiveValue" value="1">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                        <button type="button" id="bulkApplyBtn" class="btn btn-primary">Apply</button>
                                    </div>
                                </div>
                            </div>
                        </div>
        <div class="card-body">
            <table id="kodeTindakanTable" class="table table-bordered table-striped" style="width:100%">
                <thead>
                    <tr>
                        <th style="width:10%">Kode</th>
                        <th style="width:40%">Nama</th>
                        <th style="width:35%">Obat / Jumlah</th>
                        <th style="width:10%">Status</th>
                        <!-- Removed from UI for now: HPP / Harga Jasmed / Harga Jual / Harga Bottom
                        <th>HPP</th>
                        <th>Harga Jasmed</th>
                        <th>Harga Jual</th>
                        <th>Harga Bottom</th>
                        -->
                        <th style="width:10%">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<!-- Make this modal non-dismissible by backdrop click or Escape; only the header X immediately closes -->
<div class="modal fade" id="kodeTindakanModal" tabindex="-1" role="dialog" aria-labelledby="kodeTindakanModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-xl" role="document" style="max-width:1100px;">
    <div class="modal-content">
    <form id="kodeTindakanForm" novalidate>
        <div class="modal-header">
          <h5 class="modal-title" id="kodeTindakanModalLabel">Tambah Kode Tindakan</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
                    <input type="hidden" id="kodeTindakanId" name="id">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="kode">Kode</label>
                            <input type="text" class="form-control" id="kode" name="kode">
                        </div>
                        <div class="form-group col-md-8">
                            <label for="nama">Nama</label>
                            <input type="text" class="form-control" id="nama" name="nama" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-12">
                            <input type="hidden" name="is_active" value="0">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="is_active" name="is_active" checked>
                                <label class="form-check-label" for="is_active">Aktif</label>
                            </div>
                        </div>
                    </div>
                    <!-- HPP and price inputs temporarily removed from UI. Uncomment to restore.
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="hpp">HPP</label>
                            <input type="number" step="0.01" class="form-control" id="hpp" name="hpp">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="harga_jual">Harga Jual</label>
                            <input type="number" step="0.01" class="form-control" id="harga_jual" name="harga_jual">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="harga_bottom">Harga Bottom</label>
                            <input type="number" step="0.01" class="form-control" id="harga_bottom" name="harga_bottom">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="harga_jasmed">Harga Jasmed</label>
                            <input type="number" step="0.01" class="form-control" id="harga_jasmed" name="harga_jasmed">
                        </div>
                    </div>
                    -->
                    <hr>
                    <label>Obat dan BHP</label>
                    <table class="table table-bordered" id="obatTable">
                        <thead>
                            <tr>
                                <th style="width:60%">Obat dan BHP</th>
                                <th style="width:12%">Jumlah</th>
                                <th style="width:24%">Satuan Dosis</th>
                                <th style="width:4%"></th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-success btn-sm" id="addObatRow">Tambah Obat</button>
        </div>
                <div class="modal-footer">
                    <!-- Footer cancel does not auto-dismiss; asks for confirmation -->
                    <button type="button" class="btn btn-secondary" id="kodeTindakanModalCancel">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#kodeTindakanTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('marketing.kode_tindakan.data') }}',
            type: 'GET'
        },
        // enforce column widths and include status column
        columnDefs: [
            { width: '10%', targets: 0 },
            { width: '35%', targets: 1 },
            { width: '35%', targets: 2 },
            { width: '10%', targets: 3 },
            { width: '10%', targets: 4 }
        ],
        ajax: {
            url: '{{ route('marketing.kode_tindakan.data') }}',
            type: 'GET',
            data: function(d) {
                // send status filter to server: 'all' | 'active' | 'inactive'
                var s = $('#filterStatus').val();
                if (s && s !== 'all') {
                    d.status = s;
                }
                var ob = $('#filterObat').val();
                if (ob && ob !== 'all') {
                    d.obats_filter = ob;
                }
            }
        },
        columns: [
            { data: 'kode', name: 'kode' },
            { data: 'nama', name: 'nama' },
            { data: 'obats_summary', name: 'obats_summary', orderable: false, searchable: false, defaultContent: '-' },
            { data: 'status', name: 'is_active', orderable: false, searchable: false },
            // HPP / Harga fields removed from UI — keep code here commented for easy restore
            /* { data: 'hpp', name: 'hpp', render: function(data){ return data === null ? '-' : $.fn.dataTable.render.number(',', '.', 2).display(data); } },
            { data: 'harga_jasmed', name: 'harga_jasmed', render: function(data){ return data === null ? '-' : $.fn.dataTable.render.number(',', '.', 2).display(data); } },
            { data: 'harga_jual', name: 'harga_jual', render: function(data){ return data === null ? '-' : $.fn.dataTable.render.number(',', '.', 2).display(data); } },
            { data: 'harga_bottom', name: 'harga_bottom', render: function(data){ return data === null ? '-' : $.fn.dataTable.render.number(',', '.', 2).display(data); } }, */
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                        return `<button class="btn btn-sm btn-warning btn-edit" data-id="${row.id}">Edit</button>
                            <button class="btn btn-sm btn-danger btn-delete" data-id="${row.id}">Hapus</button>`;
                }
            }
        ]
    });

    // small helper: escape HTML in JS (avoid dependency on lodash)
    function escapeHtml(unsafe) {
        if (!unsafe && unsafe !== 0) return '';
        return String(unsafe)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // reload table when filter changes (preserve current page)
    $('#filterStatus').on('change', function() {
        table.ajax.reload(null, false);
    });
    $('#filterObat').on('change', function() {
        table.ajax.reload(null, false);
    });

    // Add Obat Row
    function obatRow(idx, obat = {}) {
        const satuanOptions = [
            'Mg', 'Ml', 'Gram', 'Tablet', 'Kapsul', 'Botol', 'Strip', 'Tube', 'Ampul', 'Sachet', 'Vial', 'Pcs', 'Lainnya'
        ];
        // Satuan will be shown as read-only text pulled from the selected Obat
        const satuanValue = obat.satuan_dosis || obat.satuan || '';
        let satuanField = `<input type="text" name="obats[${idx}][satuan_dosis]" class="form-control satuan-text" value="${satuanValue}" readonly>`;

            return `<tr>
            <td style="width:60%"><select name="obats[${idx}][obat_id]" class="form-control obat-select" required style="width:100%"></select></td>
            <td style="width:12%"><input type="number" name="obats[${idx}][qty]" class="form-control" min="0.01" step="0.01" inputmode="decimal" pattern="^\\d+(\\.\\d{1,2})?$" data-rule-min="0.01" data-msg-min="Jumlah harus minimal 0.01" value="${obat.qty || 1}" required></td>
            <td style="width:24%">${satuanField}</td>
            <td style="width:4%"><button type="button" class="btn btn-danger btn-sm remove-obat">Hapus</button></td>
        </tr>`;
    }

    function refreshObatRows() {
        $('#obatTable tbody tr').each(function(i, tr) {
            $(tr).find('select, input').each(function() {
                let name = $(this).attr('name');
                if (name) {
                    let newName = name.replace(/obats\[\d+\]/, `obats[${i}]`);
                    $(this).attr('name', newName);
                }
            });
        });
    }

    function initObatSelect2(context, selected = null) {
        $(context).find('.obat-select').select2({
            placeholder: 'Cari Obat',
            minimumInputLength: 2,
            ajax: {
                url: '/obat/search',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    // Normalize results and strip packaging suffixes like " - 14 Pcs"
                    function cleanResult(item) {
                        if (!item) return item;
                        // If server returned label as 'text', use it; otherwise try 'nama' or 'name'
                        let raw = item.text || item.nama || item.name || '';
                        // Remove trailing patterns like " - 14 Pcs", " - 100", etc. (case-insensitive)
                        let display = raw.replace(/\s*-\s*\d+\s*(pcs)?$/i, '').trim();
                        // Preserve original raw text in a separate prop in case we need it
                        item._raw_text = raw;
                        // Overwrite text used by select2 with cleaned display
                        item.text = display || raw;
                        return item;
                    }

                    if (Array.isArray(data.results)) {
                        return { results: data.results.map(cleanResult) };
                    } else if (Array.isArray(data)) {
                        return { results: data.map(cleanResult) };
                    } else if (data && Array.isArray(data.data)) {
                        // In case server returns { data: [...] }
                        return { results: data.data.map(cleanResult) };
                    } else if (data) {
                        return { results: [cleanResult(data)] };
                    } else {
                        return { results: [] };
                    }
                },
                cache: true
            },
            width: '100%',
            allowClear: true
        });
        // When an obat is selected, populate the satuan field from returned data
        $(context).find('.obat-select').on('select2:select', function(e) {
            const data = e.params.data || {};
            const satuan = data.satuan || data.satuan_dosis || '';
            const $row = $(this).closest('tr');
            $row.find('.satuan-text').val(satuan);
        });

        // When cleared, empty the satuan field
        $(context).find('.obat-select').on('select2:clear', function() {
            const $row = $(this).closest('tr');
            $row.find('.satuan-text').val('');
        });

        if (selected) {
            // selected may include satuan property provided by server when editing
            // Clean common packaging suffixes (e.g. " - 14 Pcs") for the displayed text
            let rawText = selected.text || '';
            let displayText = rawText.replace(/\s*-\s*\d+\s*pcs$/i, '');
            let option = new Option(displayText, selected.id, true, true);
            $(context).find('.obat-select').append(option).trigger('change');
            const satuan = selected.satuan || selected.satuan_dosis || '';
            $(context).find('.satuan-text').val(satuan);
        }
    }

    // Add new row
    $('#addObatRow').on('click', function() {
        let idx = $('#obatTable tbody tr').length;
        let $row = $(obatRow(idx));
        $('#obatTable tbody').append($row);
        initObatSelect2($row);
    });

    // Remove row
    $(document).on('click', '.remove-obat', function() {
        $(this).closest('tr').remove();
        refreshObatRows();
    });

    // Show modal for add
    $('#btnAddKodeTindakan').click(function() {
        $('#kodeTindakanForm')[0].reset();
        $('#kodeTindakanId').val('');
        $('#obatTable tbody').empty();
        // default new kode tindakan to active
        $('#is_active').prop('checked', true);
        $('#kodeTindakanModalLabel').text('Tambah Kode Tindakan');
        $('#kodeTindakanModal').modal('show');
    });

    // Import CSV modal open
    $('#btnImportCsv').on('click', function() {
        $('#importCsvForm')[0].reset();
        $('#importCsvModal').modal('show');
    });

    // Handle CSV import form submit
    $('#importCsvForm').on('submit', function(e) {
        e.preventDefault();
        var fileInput = $('#csvFile')[0];
        if (!fileInput.files || !fileInput.files.length) {
            Swal.fire('Pilih file', 'Silakan pilih file CSV terlebih dahulu.', 'warning');
            return;
        }
        var fd = new FormData();
        fd.append('csv', fileInput.files[0]);
        fd.append('_token', '{{ csrf_token() }}');
        Swal.fire({title: 'Mengimpor...', allowOutsideClick: false, didOpen: ()=>{Swal.showLoading();}});
        $.ajax({
            url: '/erm/marketing/kodetindakan/import',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function(res) {
                $('#importCsvModal').modal('hide');
                table.ajax.reload(null, false);
                // Build detailed report
                var html = '<div style="max-height:320px; overflow:auto; text-align:left;">';
                html += '<p><strong>Dibuat:</strong> ' + (res.created||0) + '</p>';
                if (res.created_items && res.created_items.length) {
                    html += '<ul>';
                    res.created_items.forEach(function(it) {
                        html += '<li>#' + it.row + ' — ' + escapeHtml(it.nama) + ' (id: ' + (it.id||'') + ')</li>';
                    });
                    html += '</ul>';
                }
                html += '<p><strong>Dilewati:</strong> ' + (res.skipped||0) + '</p>';
                if (res.skipped_items && res.skipped_items.length) {
                    html += '<ul>';
                    res.skipped_items.forEach(function(it) {
                        html += '<li>#' + it.row + ' — ' + escapeHtml(it.nama) + ' — ' + escapeHtml(it.reason) + '</li>';
                    });
                    html += '</ul>';
                }
                if (res.renamed_items && res.renamed_items.length) {
                    html += '<hr><p><strong>Renamed existing records:</strong></p><ul>';
                    res.renamed_items.forEach(function(it) {
                        html += '<li>Old #'+it.old_id+' — ' + escapeHtml(it.old_name) + ' → ' + escapeHtml(it.new_name) + '</li>';
                    });
                    html += '</ul>';
                }
                if (res.errors && res.errors.length) {
                    html += '<hr><p><strong>Errors:</strong></p><ul>';
                    res.errors.forEach(function(err) { html += '<li>' + escapeHtml(err) + '</li>'; });
                    html += '</ul>';
                }
                html += '</div>';
                Swal.fire({title: 'Import Selesai', html: html, width: 700});
            },
            error: function(xhr) {
                var msg = 'Terjadi kesalahan saat import';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                Swal.fire('Gagal', msg, 'error');
            }
        });
    });

    // Footer Cancel: ask confirmation before hiding since modal is non-dismissible
    $(document).on('click', '#kodeTindakanModalCancel', function() {
        Swal.fire({
            title: 'Tutup formulir?',
            text: 'Perubahan yang belum disimpan akan hilang. Yakin ingin menutup?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, tutup',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (result.value) {
                $('#kodeTindakanModal').modal('hide');
            }
        });
    });

    // Show modal for edit
    $('#kodeTindakanTable').on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        $.get('/marketing/kodetindakan/' + id, function(data) {
            $('#kodeTindakanId').val(data.id);
            $('#kode').val(data.kode);
            $('#nama').val(data.nama);
            // HPP / Harga fields are removed from UI; these were previously set here:
            // $('#hpp').val(data.hpp);
            // $('#harga_jasmed').val(data.harga_jasmed);
            // $('#harga_jual').val(data.harga_jual);
            // $('#harga_bottom').val(data.harga_bottom);
            $('#obatTable tbody').empty();
            if (data.obats && data.obats.length) {
                data.obats.forEach(function(obat, idx) {
                    let $row = $(obatRow(idx, obat));
                    $('#obatTable tbody').append($row);
                    // Pass unit info so the select2 initializer can populate the satuan field
                    initObatSelect2($row, {id: obat.obat_id, text: obat.obat_nama, satuan: (obat.satuan_dosis || obat.satuan || '')});
                });
            }
            // set active checkbox
            $('#is_active').prop('checked', data.is_active ? true : false);
            $('#kodeTindakanModalLabel').text('Edit Kode Tindakan');
            $('#kodeTindakanModal').modal('show');
        });
    });

    // Save (add/edit)
    $('#kodeTindakanForm').submit(function(e) {
        e.preventDefault();
        var id = $('#kodeTindakanId').val();
        var url = id ? '/marketing/kodetindakan/' + id : '/marketing/kodetindakan';
        var method = id ? 'PUT' : 'POST';
        var formData = $(this).serialize();
        $.ajax({
            url: url,
            type: method,
            data: formData,
            success: function(res) {
                $('#kodeTindakanModal').modal('hide');
                    table.ajax.reload(null, false);
                Swal.fire('Berhasil', 'Data berhasil disimpan', 'success');
            },
            error: function(xhr) {
                Swal.fire('Gagal', xhr.responseJSON.message || 'Terjadi kesalahan', 'error');
            }
        });
    });

    // Delete
    $('#kodeTindakanTable').on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Hapus Kode Tindakan?',
            text: 'Data yang dihapus tidak dapat dikembalikan!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: '/marketing/kodetindakan/' + id,
                    type: 'DELETE',
                    data: {_token: '{{ csrf_token() }}'},
                    success: function(res) {
                        table.ajax.reload(null, false);
                        Swal.fire('Berhasil', 'Data berhasil dihapus', 'success');
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal', xhr.responseJSON.message || 'Terjadi kesalahan', 'error');
                    }
                });
            }
        });
    });

    // Make all inactive
    $('#btnMakeAllInactive').on('click', function() {
        $('#bulkActionModalLabel').text('Preview: Nonaktifkan Kode Tindakan');
        $('#bulkSetActiveValue').val(0);
        $('#bulkPreviewTable tbody').empty();
        $('#bulkStart').val('');
        $('#bulkEnd').val('');
        $('#bulkActionModal').modal('show');
    });

    // Make all active
    $('#btnMakeAllActive').on('click', function() {
        $('#bulkActionModalLabel').text('Preview: Aktifkan Kode Tindakan');
        $('#bulkSetActiveValue').val(1);
        $('#bulkPreviewTable tbody').empty();
        $('#bulkStart').val('');
        $('#bulkEnd').val('');
        $('#bulkActionModal').modal('show');
    });

    // Bulk preview
    $('#bulkPreviewBtn').on('click', function(e) {
        e.preventDefault();
        var start = $('#bulkStart').val();
        var end = $('#bulkEnd').val();
        if (!start || !end) {
            Swal.fire('Tanggal dibutuhkan', 'Silakan pilih tanggal mulai dan akhir untuk preview.', 'warning');
            return;
        }
        $('#bulkPreviewTable tbody').html('<tr><td colspan="4" class="text-center">Memuat...</td></tr>');
        $.get('/erm/kodetindakan/by-date', { start: start, end: end }, function(res) {
            if (!res.success) {
                Swal.fire('Gagal', res.message || 'Tidak dapat mengambil data', 'error');
                return;
            }
            var rows = '';
            res.data.forEach(function(item) {
                var checked = item.is_active ? '' : '';
                rows += '<tr data-id="'+item.id+'">'
                    + '<td><input type="checkbox" class="bulk-row-checkbox" value="'+item.id+'"></td>'
                    + '<td>'+ escapeHtml(item.nama) +'</td>'
                    + '<td>'+ item.created_at +'</td>'
                    + '<td>'+(item.is_active ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-secondary">Nonaktif</span>')+'</td>'
                    + '</tr>';
            });
            if (!res.data.length) rows = '<tr><td colspan="4" class="text-center">Tidak ada data</td></tr>';
            $('#bulkPreviewTable tbody').html(rows);
        }).fail(function() {
            Swal.fire('Gagal', 'Terjadi kesalahan saat mengambil preview', 'error');
        });
    });

    // select all in preview
    $(document).on('change', '#bulkSelectAll', function() {
        var checked = $(this).is(':checked');
        $('#bulkPreviewTable tbody').find('.bulk-row-checkbox').prop('checked', checked);
    });

    // Apply bulk set active
    $('#bulkApplyBtn').on('click', function() {
        var setActive = parseInt($('#bulkSetActiveValue').val(),10);
        var ids = [];
        $('#bulkPreviewTable tbody').find('.bulk-row-checkbox:checked').each(function() { ids.push($(this).val()); });
        var payload = {_token: '{{ csrf_token() }}', set_active: setActive};
        if (ids.length) {
            payload.ids = ids;
        } else {
            var start = $('#bulkStart').val();
            var end = $('#bulkEnd').val();
            if (!start || !end) {
                Swal.fire('Tidak ada data', 'Pilih baris atau tentukan rentang tanggal untuk melakukan aksi.', 'warning');
                return;
            }
            payload.start = start; payload.end = end;
        }
        Swal.fire({title: 'Menerapkan...', allowOutsideClick: false, didOpen: ()=>{Swal.showLoading();}});
        $.post('/erm/kodetindakan/action/bulk-set-active', payload, function(res) {
            $('#bulkActionModal').modal('hide');
            table.ajax.reload(null, false);
            Swal.fire('Selesai', 'Diperbarui: ' + (res.updated||0), 'success');
        }).fail(function(xhr) {
            Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Terjadi kesalahan', 'error');
        });
    });
});

</script>
@endpush
