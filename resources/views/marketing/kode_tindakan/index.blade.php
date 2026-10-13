@extends('layouts.marketing.app')

@section('title', 'Tindakan - Marketing')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
@php $isAdmin = auth()->user()->hasRole('Admin'); @endphp
<style>
    .kt-toolbar { gap: 8px; }
    .kt-toolbar .kt-search { max-width: 320px; }
    .kt-status .btn { font-weight: 600; }
    #kodeTindakanTable td { vertical-align: middle; }
    #kodeTindakanTable .kt-nama { font-weight: 600; }
    #kodeTindakanTable .kt-obat { font-size: .8rem; margin: 0; padding-left: 1rem; }
    #kodeTindakanTable tr.kt-off td { color: #8a94a6; }
    #kodeTindakanTable tr.kt-selected td { background: #eef4ff; }
    .kt-bulk { background: #eef4ff; border: 1px solid #c9dafc; border-radius: .4rem; padding: .35rem .6rem; }
</style>
<div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center justify-content-between mt-3 mb-3" style="gap:8px">
        <div>
            <h4 class="page-title mb-0">Tindakan</h4>
            <small class="text-muted">Berisi obat/BHP yang dipakai, <strong>tanpa harga jual</strong>. Agar bisa dijual, masukkan ke <a href="{{ route('marketing.tindakan.index') }}">Paket Tindakan</a>.</small>
        </div>
        <div class="d-flex flex-wrap" style="gap:6px">
            @if($isAdmin)
            <button type="button" class="btn btn-light btn-sm" id="btnImportCsv" title="Buat tindakan dari CSV (kolom nama)"><i class="fas fa-file-import"></i> Import CSV</button>
            @endif
            <button type="button" class="btn btn-primary btn-sm" id="btnAddKodeTindakan"><i class="fas fa-plus"></i> Tambah Tindakan</button>
        </div>
    </div>

    @include('partials.action-cards', ['actionCounts' => $actionCounts])

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center kt-toolbar mb-2">
                <div class="input-group input-group-sm kt-search">
                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                    <input type="search" id="ktSearch" class="form-control" placeholder="Cari nama, kode, atau obat/BHP..." autocomplete="off">
                </div>
                <div class="btn-group btn-group-sm kt-status" role="group" id="ktStatus">
                    <button type="button" class="btn btn-outline-primary active" data-status="active">Aktif <span class="badge badge-light" data-count="active">{{ $statusCounts['active'] }}</span></button>
                    <button type="button" class="btn btn-outline-primary" data-status="inactive">Nonaktif <span class="badge badge-light" data-count="inactive">{{ $statusCounts['inactive'] }}</span></button>
                    <button type="button" class="btn btn-outline-primary" data-status="all">Semua <span class="badge badge-light" data-count="all">{{ $statusCounts['all'] }}</span></button>
                </div>
                <select id="filterObat" class="custom-select custom-select-sm" style="width:auto">
                    <option value="">Obat/BHP: semua</option>
                    <option value="has">Ada obat/BHP</option>
                    <option value="none">Tanpa obat/BHP</option>
                </select>
                <select id="filterPaket" class="custom-select custom-select-sm" style="width:auto">
                    <option value="">Paket: semua</option>
                    <option value="used">Dipakai di paket</option>
                    <option value="unused">Belum dipakai paket</option>
                </select>
                <button type="button" class="btn btn-link btn-sm d-none" id="ktReset">Reset filter</button>
            </div>

            @if($isAdmin)
            <div class="kt-bulk d-none align-items-center mb-2" id="ktBulk" style="gap:8px">
                <strong id="ktBulkInfo"></strong>
                <button type="button" class="btn btn-sm btn-success" data-bulk="1"><i class="fas fa-eye"></i> Aktifkan</button>
                <button type="button" class="btn btn-sm btn-warning" data-bulk="0"><i class="fas fa-eye-slash"></i> Nonaktifkan</button>
                <button type="button" class="btn btn-sm btn-link" id="ktBulkClear">Batal pilih</button>
            </div>
            @endif

            <table id="kodeTindakanTable" class="table table-sm table-hover w-100">
                <thead class="thead-light">
                    <tr>
                        <th style="width:24px">@if($isAdmin)<input type="checkbox" id="ktCheckAll" title="Pilih semua di halaman ini">@endif</th>
                        <th>Tindakan</th>
                        <th>Obat / BHP</th>
                        <th>Dipakai di paket</th>
                        <th class="text-center" style="width:80px">Aktif</th>
                        <th class="text-right" style="width:1%">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@if($isAdmin)
<!-- Import CSV Modal -->
<div class="modal fade" id="importCsvModal" tabindex="-1" role="dialog" aria-labelledby="importCsvModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="importCsvForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="importCsvModalLabel">Import Tindakan dari CSV</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-0">
                        <label for="csvFile">File CSV</label>
                        <input type="file" id="csvFile" name="csv" accept=".csv,text/csv" class="form-control" required />
                        <small class="form-text text-muted">Satu kolom berisi nama tindakan. Header opsional. Nama yang sudah ada dilewati. Obat/BHP diisi lewat Edit.</small>
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
@endif

<!-- Add / edit; only the X or the footer button (with confirmation) close it -->
<div class="modal fade" id="kodeTindakanModal" tabindex="-1" role="dialog" aria-labelledby="kodeTindakanModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <form class="modal-content" id="kodeTindakanForm" novalidate>
            <div class="modal-header">
                <h5 class="modal-title" id="kodeTindakanModalLabel">Tambah Tindakan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="kodeTindakanId" name="id">
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label for="nama">Nama <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama" name="nama" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="kode">Kode</label>
                        <input type="text" class="form-control" id="kode" name="kode" placeholder="Opsional">
                    </div>
                </div>
                <div class="form-group">
                    <label for="kategoriSelect">Kategori</label>
                    <select id="kategoriSelect" name="kategori_ids[]" class="form-control" multiple="multiple" style="width:100%"></select>
                </div>
                <div class="form-group">
                    <input type="hidden" name="is_active" value="0">
                    <div class="custom-control custom-switch">
                        <input class="custom-control-input" type="checkbox" value="1" id="is_active" name="is_active" checked>
                        <label class="custom-control-label" for="is_active">Aktif</label>
                    </div>
                </div>
                <hr>
                <label class="mb-0">Obat dan BHP</label>
                <small class="form-text text-muted mt-0 mb-1">Dipakai untuk mengurangi stok dan menghitung biaya bahan paket.</small>
                <table class="table table-sm table-bordered mb-1" id="obatTable">
                    <thead class="thead-light">
                        <tr>
                            <th>Obat / BHP</th>
                            <th style="width:110px">Jumlah</th>
                            <th style="width:110px">Satuan</th>
                            <th style="width:34px"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <button type="button" class="btn btn-outline-success btn-sm" id="addObatRow"><i class="fas fa-plus"></i> Tambah Obat</button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="kodeTindakanModalCancel">Tutup</button>
                <button type="submit" class="btn btn-primary" id="ktSave"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    var CSRF = '{{ csrf_token() }}';
    var IS_ADMIN = @json($isAdmin);
    var state = { status: 'active', needCounts: false, selected: {} };

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': CSRF } });

    function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }
    function ajaxErrorMessage(xhr, fallback) {
        var json = xhr && xhr.responseJSON;
        if (json && json.errors) {
            var first = Object.values(json.errors)[0];
            if (first && first.length) return first[0];
        }
        return (json && json.message) || fallback || 'Terjadi kesalahan';
    }
    function showError(message) { Swal.fire({ icon: 'error', title: 'Gagal', text: message }); }

    // ================= Table =================
    var table = $('#kodeTindakanTable').DataTable({
        processing: true,
        serverSide: true,
        deferRender: true,
        autoWidth: false,
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        order: [[1, 'asc']],
        dom: 'rt<"d-flex flex-wrap justify-content-between align-items-center mt-2"lip>',
        ajax: {
            url: '{{ route('marketing.kode_tindakan.data') }}',
            data: function (d) {
                if (state.status !== 'all') d.status = state.status;
                if ($('#filterObat').val()) d.obats_filter = $('#filterObat').val();
                if ($('#filterPaket').val()) d.paket_filter = $('#filterPaket').val();
                if (window.ActionCards && ActionCards.active) d.action = ActionCards.active;
                // Counts only on demand: they are already on the page and only change after an edit
                if (state.needCounts) { d.counts = 1; state.needCounts = false; }
            }
        },
        language: {
            processing: '<i class="fas fa-spinner fa-spin"></i> Memuat...',
            zeroRecords: 'Tidak ada tindakan yang cocok', emptyTable: 'Belum ada tindakan',
            info: '_START_–_END_ dari _TOTAL_ tindakan', infoEmpty: '0 tindakan', infoFiltered: '',
            lengthMenu: 'Tampil _MENU_', paginate: { previous: '‹', next: '›' }
        },
        columns: [
            {
                data: 'id', orderable: false, searchable: false,
                render: function (id) { return IS_ADMIN ? '<input type="checkbox" class="kt-check" value="' + id + '"' + (state.selected[id] ? ' checked' : '') + '>' : ''; }
            },
            {
                data: 'nama', name: 'nama', searchable: false,
                render: function (d, t, r) {
                    return '<div class="kt-nama">' + esc(d) + '</div>' + (r.kode ? '<div class="small text-muted">' + esc(r.kode) + '</div>' : '');
                }
            },
            {
                data: 'obat', orderable: false, searchable: false,
                render: function (d) {
                    if (!d || !d.length) return '<span class="small text-warning"><i class="fas fa-exclamation-triangle"></i> belum ada obat/BHP</span>';
                    return '<ul class="kt-obat">' + d.map(function (o) {
                        return '<li>' + esc(o.nama) + ' <span class="text-muted">· ' + esc(o.qty) + (o.satuan ? ' ' + esc(o.satuan) : '') + '</span></li>';
                    }).join('') + '</ul>';
                }
            },
            {
                data: 'paket_count', name: 'paket_count', searchable: false,
                render: function (d, t, r) {
                    if (!d) return '<span class="small text-muted">belum dipakai</span>';
                    var names = (r.paket || []).map(function (p) { return p.aktif ? esc(p.nama) : '<span class="text-muted">' + esc(p.nama) + ' (nonaktif)</span>'; });
                    return '<span class="badge badge-info">' + d + '</span> <span class="small">' + names.join(', ') + (d > names.length ? ', …' : '') + '</span>';
                }
            },
            {
                data: 'is_active', name: 'is_active', searchable: false, className: 'text-center',
                render: function (d, t, r) {
                    return '<div class="custom-control custom-switch d-inline-block" title="' + (d ? 'Aktif: klik untuk nonaktifkan' : 'Nonaktif: klik untuk aktifkan') + '">'
                        + '<input type="checkbox" class="custom-control-input kt-toggle" id="kt_on_' + r.id + '" data-id="' + r.id + '"' + (d ? ' checked' : '') + '>'
                        + '<label class="custom-control-label" for="kt_on_' + r.id + '"></label></div>';
                }
            },
            {
                data: null, orderable: false, searchable: false, className: 'text-right text-nowrap',
                render: function (d, t, r) {
                    return '<button type="button" class="btn btn-sm btn-outline-primary btn-edit" data-id="' + r.id + '"><i class="fas fa-edit"></i> Edit</button>'
                        + ' <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="' + r.id + '" data-nama="' + esc(r.nama) + '" title="Hapus (hanya tindakan yang belum pernah dipakai)"><i class="fas fa-trash"></i></button>';
                }
            }
        ],
        createdRow: function (tr, r) {
            $(tr).toggleClass('kt-off', !r.is_active).toggleClass('kt-selected', !!state.selected[r.id]);
        },
        drawCallback: function () { $('#ktCheckAll').prop('checked', false); }
    });

    table.on('xhr', function (e, s, json) {
        if (!json) return;
        if (json.action_counts) ActionCards.update(json.action_counts);
        if (json.status_counts) $.each(json.status_counts, function (k, v) { $('#ktStatus [data-count="' + k + '"]').text(v); });
    });
    function reloadWithCounts() { state.needCounts = true; table.ajax.reload(null, false); }

    // ================= Filters =================
    function filtersActive() { return !!($('#ktSearch').val() || $('#filterObat').val() || $('#filterPaket').val() || state.status !== 'active'); }
    function refresh() { $('#ktReset').toggleClass('d-none', !filtersActive()); table.ajax.reload(); }
    function setStatus(s) {
        state.status = s;
        $('#ktStatus .btn').removeClass('active').filter('[data-status="' + s + '"]').addClass('active');
    }

    var searchTimer;
    $('#ktSearch').on('input', function () {
        var v = this.value;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { $('#ktReset').toggleClass('d-none', !filtersActive()); table.search(v).draw(); }, 350);
    });
    $('#ktStatus').on('click', '.btn', function () { setStatus($(this).data('status')); refresh(); });
    $('#filterObat, #filterPaket').on('change', refresh);
    $('#ktReset').on('click', function () {
        $('#ktSearch, #filterObat, #filterPaket').val('');
        setStatus('active');
        table.search('');
        refresh();
    });

    // "Data yang perlu action" cards: show every status so the card's rows are not hidden by the other filters
    ActionCards.init(function () { table.ajax.reload(); }, function () {
        $('#filterObat, #filterPaket').val('');
        setStatus('all');
        $('#ktReset').removeClass('d-none');
    });

    // ================= Aktif / nonaktif =================
    $('#kodeTindakanTable').on('change', '.kt-toggle', function () {
        var $cb = $(this).prop('disabled', true);
        $.post('/marketing/kodetindakan/' + $cb.data('id') + '/toggle-active')
            .done(function (res) {
                reloadWithCounts();
                if (res.warning) Swal.fire({ icon: 'warning', title: 'Dinonaktifkan, tapi perhatikan', text: res.warning });
            })
            .fail(function (xhr) { $cb.prop('checked', !$cb.prop('checked')); showError(ajaxErrorMessage(xhr, 'Status gagal diubah')); })
            .always(function () { $cb.prop('disabled', false); });
    });

    // Bulk (Admin): tick rows, then Aktifkan / Nonaktifkan
    function refreshBulk() {
        var n = Object.keys(state.selected).length;
        $('#ktBulk').toggleClass('d-none', !n).toggleClass('d-flex', !!n);
        $('#ktBulkInfo').text(n + ' tindakan dipilih');
    }
    $('#kodeTindakanTable').on('change', '.kt-check', function () {
        if (this.checked) state.selected[this.value] = true; else delete state.selected[this.value];
        $(this).closest('tr').toggleClass('kt-selected', this.checked);
        refreshBulk();
    });
    $('#ktCheckAll').on('change', function () {
        var on = this.checked;
        $('#kodeTindakanTable .kt-check').each(function () { if (this.checked !== on) $(this).prop('checked', on).trigger('change'); });
    });
    $('#ktBulkClear').on('click', function () { state.selected = {}; refreshBulk(); table.draw(false); });
    $('#ktBulk').on('click', '[data-bulk]', function () {
        var ids = Object.keys(state.selected).map(Number);
        var on = $(this).data('bulk') === 1;
        Swal.fire({
            icon: 'question', title: (on ? 'Aktifkan ' : 'Nonaktifkan ') + ids.length + ' tindakan?',
            text: on ? 'Tindakan bisa dipilih lagi saat menyusun paket.' : 'Paket yang berisi tindakan ini tetap memakainya sampai dikeluarkan dari paket.',
            showCancelButton: true, confirmButtonText: 'Ya', cancelButtonText: 'Batal', reverseButtons: true
        }).then(function (r) {
            if (!r.value) return;
            $.post('/erm/kodetindakan/action/bulk-set-active', { ids: ids, set_active: on ? 1 : 0 })
                .done(function (res) {
                    state.selected = {};
                    refreshBulk();
                    reloadWithCounts();
                    Swal.fire({ icon: res.warning ? 'warning' : 'success', title: 'Selesai', text: (res.updated || 0) + ' tindakan diperbarui.' + (res.warning ? ' ' + res.warning : '') });
                })
                .fail(function (xhr) { showError(ajaxErrorMessage(xhr)); });
        });
    });

    // ================= Form =================
    function obatRow(idx, obat) {
        obat = obat || {};
        return $('<tr>'
            + '<td><select name="obats[' + idx + '][obat_id]" class="form-control obat-select" required style="width:100%"></select></td>'
            + '<td><input type="number" name="obats[' + idx + '][qty]" class="form-control form-control-sm" min="0.01" step="0.01" value="' + esc(obat.qty || 1) + '" required></td>'
            + '<td><input type="text" name="obats[' + idx + '][satuan_dosis]" class="form-control form-control-sm satuan-text" value="' + esc(obat.satuan_dosis || obat.satuan || '') + '" readonly></td>'
            + '<td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger p-0 remove-obat" title="Hapus"><i class="fas fa-times"></i></button></td>'
            + '</tr>');
    }

    // Keep obats[i] continuous after a row is removed
    function renumberObatRows() {
        $('#obatTable tbody tr').each(function (i, tr) {
            $(tr).find('select, input').each(function () {
                var name = $(this).attr('name');
                if (name) $(this).attr('name', name.replace(/obats\[\d+\]/, 'obats[' + i + ']'));
            });
        });
    }

    function initObatSelect2($row, selected) {
        var $select = $row.find('.obat-select');
        $select.select2({
            dropdownParent: $('#kodeTindakanModal'),
            placeholder: 'Cari obat / BHP',
            minimumInputLength: 2,
            width: '100%',
            ajax: {
                url: '/obat/search',
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) {
                    var list = Array.isArray(data.results) ? data.results : (Array.isArray(data) ? data : (data && Array.isArray(data.data) ? data.data : []));
                    // Strip packaging suffixes like " - 14 Pcs" from the label
                    return { results: list.map(function (item) {
                        var raw = item.text || item.nama || item.name || '';
                        item.text = raw.replace(/\s*-\s*\d+\s*(pcs)?$/i, '').trim() || raw;
                        return item;
                    }) };
                },
                cache: true
            }
        }).on('select2:select', function (e) {
            var data = e.params.data || {};
            $row.find('.satuan-text').val(data.satuan || data.satuan_dosis || '');
        });
        if (selected) {
            $select.append(new Option((selected.text || '').replace(/\s*-\s*\d+\s*pcs$/i, ''), selected.id, true, true)).trigger('change');
        }
    }

    function initKategoriSelect2(selected) {
        var $k = $('#kategoriSelect');
        if ($k.hasClass('select2-hidden-accessible')) $k.select2('destroy');
        $k.empty().select2({
            dropdownParent: $('#kodeTindakanModal'),
            placeholder: 'Cari atau pilih kategori',
            minimumInputLength: 1,
            width: '100%',
            allowClear: true,
            ajax: {
                url: '{{ route('marketing.kategori.search') }}',
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) {
                    var list = Array.isArray(data.results) ? data.results : (Array.isArray(data) ? data : (data && Array.isArray(data.data) ? data.data : []));
                    return { results: list.map(function (item) { return { id: item.id, text: item.nama || item.text || item.name }; }) };
                },
                cache: true
            }
        });
        (selected || []).forEach(function (s) { $k.append(new Option(s.nama, s.id, true, true)); });
        $k.trigger('change');
    }

    $('#addObatRow').on('click', function () {
        var $row = obatRow($('#obatTable tbody tr').length);
        $('#obatTable tbody').append($row);
        initObatSelect2($row);
        $row.find('.obat-select').select2('open');
    });
    $('#obatTable').on('click', '.remove-obat', function () { $(this).closest('tr').remove(); renumberObatRows(); });

    function openForm(data) {
        data = data || {};
        $('#kodeTindakanForm')[0].reset();
        $('#kodeTindakanId').val(data.id || '');
        $('#kode').val(data.kode || '');
        $('#nama').val(data.nama || '');
        $('#is_active').prop('checked', data.id ? !!data.is_active : true);
        $('#obatTable tbody').empty();
        (data.obats || []).forEach(function (obat, idx) {
            var $row = obatRow(idx, obat);
            $('#obatTable tbody').append($row);
            initObatSelect2($row, { id: obat.obat_id, text: obat.obat_nama });
        });
        initKategoriSelect2((data.kategoris || []).map(function (k) { return { id: k.id, nama: k.nama }; }));
        $('#kodeTindakanModalLabel').text(data.id ? 'Edit Tindakan: ' + data.nama : 'Tambah Tindakan');
        $('#kodeTindakanModal').modal('show');
    }

    $('#btnAddKodeTindakan').on('click', function () { openForm(); });
    $('#kodeTindakanTable').on('click', '.btn-edit', function () {
        $.getJSON('/marketing/kodetindakan/' + $(this).data('id'))
            .done(openForm)
            .fail(function (xhr) { showError(ajaxErrorMessage(xhr)); });
    });

    $('#kodeTindakanModalCancel').on('click', function () {
        Swal.fire({
            title: 'Tutup formulir?', text: 'Perubahan yang belum disimpan akan hilang.', icon: 'warning',
            showCancelButton: true, confirmButtonText: 'Ya, tutup', cancelButtonText: 'Kembali', reverseButtons: true
        }).then(function (r) { if (r.value) $('#kodeTindakanModal').modal('hide'); });
    });

    $('#kodeTindakanForm').on('submit', function (e) {
        e.preventDefault();
        if (!$.trim($('#nama').val())) { Swal.fire('Periksa lagi', 'Nama tindakan wajib diisi.', 'warning'); return; }
        var id = $('#kodeTindakanId').val();
        var $btn = $('#ktSave').prop('disabled', true);
        $.ajax({ url: id ? '/marketing/kodetindakan/' + id : '/marketing/kodetindakan', type: id ? 'PUT' : 'POST', data: $(this).serialize() })
            .done(function (res) {
                $('#kodeTindakanModal').modal('hide');
                reloadWithCounts();
                if (res && res.warning) Swal.fire({ icon: 'warning', title: 'Tersimpan, tapi perhatikan', text: res.warning });
                else Swal.fire({ icon: 'success', title: 'Tersimpan', timer: 1500, showConfirmButton: false });
            })
            .fail(function (xhr) { showError(ajaxErrorMessage(xhr)); })
            .always(function () { $btn.prop('disabled', false); });
    });

    // Delete: the server refuses a tindakan that is in a paket or in history
    $('#kodeTindakanTable').on('click', '.btn-delete', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Hapus tindakan ' + $(this).data('nama') + '?',
            text: 'Tindakan yang ada di paket atau riwayat pasien tidak bisa dihapus; nonaktifkan saja.',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal', reverseButtons: true, focusCancel: true
        }).then(function (result) {
            if (!result.value) return;
            $.ajax({ url: '/marketing/kodetindakan/' + id, type: 'DELETE' })
                .done(function () { reloadWithCounts(); Swal.fire({ icon: 'success', title: 'Terhapus', timer: 1500, showConfirmButton: false }); })
                .fail(function (xhr) { showError(ajaxErrorMessage(xhr)); });
        });
    });

    // ================= Import (Admin) =================
    $('#btnImportCsv').on('click', function () { $('#importCsvForm')[0].reset(); $('#importCsvModal').modal('show'); });
    $('#importCsvForm').on('submit', function (e) {
        e.preventDefault();
        var file = $('#csvFile')[0].files;
        if (!file || !file.length) { Swal.fire('Pilih file', 'Silakan pilih file CSV terlebih dahulu.', 'warning'); return; }
        var fd = new FormData();
        fd.append('csv', file[0]);
        fd.append('_token', CSRF);
        Swal.fire({ title: 'Mengimpor...', allowOutsideClick: false, onOpen: function () { Swal.showLoading(); } });
        $.ajax({ url: '/erm/marketing/kodetindakan/import', type: 'POST', data: fd, processData: false, contentType: false })
            .done(function (res) {
                $('#importCsvModal').modal('hide');
                reloadWithCounts();
                var list = function (items, fmt) { return items && items.length ? '<ul>' + items.map(fmt).join('') + '</ul>' : ''; };
                Swal.fire({ title: 'Import selesai', width: 700, html: '<div style="max-height:320px;overflow:auto;text-align:left">'
                    + '<p><strong>Dibuat:</strong> ' + (res.created || 0) + '</p>' + list(res.created_items, function (it) { return '<li>#' + it.row + ' ' + esc(it.nama) + '</li>'; })
                    + '<p><strong>Dilewati:</strong> ' + (res.skipped || 0) + '</p>' + list(res.skipped_items, function (it) { return '<li>#' + it.row + ' ' + esc(it.nama) + ' — ' + esc(it.reason) + '</li>'; })
                    + '</div>' });
            })
            .fail(function (xhr) { showError(ajaxErrorMessage(xhr, 'Import gagal')); });
    });
});
</script>
@endpush
