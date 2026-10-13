@extends('layouts.erm.app')
@section('title','ERM | Master Lab')
@section('navbar')
    @include('layouts.erm.navbar-lab')
@endsection

@section('content')
<style>
    .ml-toolbar { gap: 8px; }
    .ml-toolbar .ml-search { max-width: 320px; }
    .ml-tabs .nav-link { font-weight: 600; }
    .ml-tabs .badge { font-weight: 600; }
    .ml-table td { vertical-align: middle; }
    .ml-table .ml-nama { font-weight: 600; }
    .ml-table ul.ml-list { font-size: .8rem; margin: 0; padding-left: 1rem; }
    .ml-table tr.ml-off td { color: #8a94a6; }
    .ml-dokter { border: 1px solid #e3e8f0; border-radius: .5rem; padding: .35rem .6rem; background: #fff; gap: 6px; }
    .ml-dokter select { min-width: 220px; }
</style>
<div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center justify-content-between mt-3 mb-3" style="gap:8px">
        <div>
            <h4 class="page-title mb-0">Master Lab</h4>
            <small class="text-muted">Lab test, paket lab, dan kategori. Test yang <strong>tidak tersedia</strong> tetap terlihat di permintaan lab tapi ditandai.</small>
        </div>
        <div class="d-flex align-items-center ml-dokter" title="Dokter yang tercetak di Lembar Monitoring lab">
            <label class="mb-0 small font-weight-bold text-nowrap" for="lab_dokter_select"><i class="fas fa-user-md"></i> Dokter lab aktif</label>
            <select id="lab_dokter_select" class="custom-select custom-select-sm"></select>
            <button class="btn btn-sm btn-primary" id="btn-save-lab-dokter" type="button" disabled>Simpan</button>
        </div>
    </div>

    @include('partials.action-cards', ['actionCounts' => $actionCounts])

    <ul class="nav nav-pills ml-tabs mb-3" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-toggle="pill" href="#paneTest" role="tab"><i class="fas fa-vial"></i> Lab Test <span class="badge badge-light" data-tab-count="test">{{ $counts['test']['all'] }}</span></a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#panePaket" role="tab"><i class="fas fa-layer-group"></i> Paket Lab <span class="badge badge-light" data-tab-count="paket">{{ $counts['paket'] }}</span></a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#paneKategori" role="tab"><i class="fas fa-tags"></i> Kategori <span class="badge badge-light" data-tab-count="kategori">{{ $counts['kategori'] }}</span></a></li>
    </ul>

    <div class="tab-content">
        {{-- ================= Lab Test ================= --}}
        <div class="tab-pane fade show active" id="paneTest" role="tabpanel">
            <div class="card"><div class="card-body">
                <div class="d-flex flex-wrap align-items-center ml-toolbar mb-2">
                    <div class="input-group input-group-sm ml-search">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                        <input type="search" id="testSearch" class="form-control" placeholder="Cari test, kategori, atau obat..." autocomplete="off">
                    </div>
                    <div class="btn-group btn-group-sm" role="group" id="testAvailable">
                        <button type="button" class="btn btn-outline-primary active" data-available="">Semua <span class="badge badge-light" data-count="all">{{ $counts['test']['all'] }}</span></button>
                        <button type="button" class="btn btn-outline-primary" data-available="yes">Tersedia <span class="badge badge-light" data-count="yes">{{ $counts['test']['yes'] }}</span></button>
                        <button type="button" class="btn btn-outline-primary" data-available="no">Tidak tersedia <span class="badge badge-light" data-count="no">{{ $counts['test']['no'] }}</span></button>
                    </div>
                    <select id="testKategoriFilter" class="custom-select custom-select-sm" style="width:auto">
                        <option value="">Semua kategori</option>
                        @foreach($kategoris as $k)
                            <option value="{{ $k->id }}">{{ $k->nama }}</option>
                        @endforeach
                    </select>
                    <div class="ml-auto d-flex" style="gap:6px">
                        <a class="btn btn-sm btn-light" href="{{ route('erm.labtests.export') }}"><i class="fas fa-file-excel"></i> Excel</a>
                        <button class="btn btn-sm btn-primary" id="btn-add-test"><i class="fas fa-plus"></i> Tambah Lab Test</button>
                    </div>
                </div>
                <table class="table table-sm table-hover w-100 ml-table" id="labtests-table">
                    <thead class="thead-light">
                        <tr>
                            <th>Test</th>
                            <th class="text-right">Harga</th>
                            <th>Obat / BHP</th>
                            <th>Di paket</th>
                            <th class="text-center" style="width:90px">Tersedia</th>
                            <th class="text-right" style="width:1%">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div></div>
        </div>

        {{-- ================= Paket Lab ================= --}}
        <div class="tab-pane fade" id="panePaket" role="tabpanel">
            <div class="card"><div class="card-body">
                <div class="d-flex flex-wrap align-items-center ml-toolbar mb-2">
                    <div class="input-group input-group-sm ml-search">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                        <input type="search" id="paketSearch" class="form-control" placeholder="Cari paket atau test di dalamnya..." autocomplete="off">
                    </div>
                    <div class="ml-auto"><button class="btn btn-sm btn-primary" id="btn-add-paket"><i class="fas fa-plus"></i> Tambah Paket Lab</button></div>
                </div>
                <table class="table table-sm table-hover w-100 ml-table" id="labpakets-table">
                    <thead class="thead-light">
                        <tr>
                            <th>Paket</th>
                            <th>Isi test</th>
                            <th class="text-right">Harga paket</th>
                            <th class="text-right" style="width:1%">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div></div>
        </div>

        {{-- ================= Kategori ================= --}}
        <div class="tab-pane fade" id="paneKategori" role="tabpanel">
            <div class="card"><div class="card-body">
                <div class="d-flex flex-wrap align-items-center ml-toolbar mb-2">
                    <div class="input-group input-group-sm ml-search">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                        <input type="search" id="kategoriSearch" class="form-control" placeholder="Cari kategori..." autocomplete="off">
                    </div>
                    <div class="ml-auto"><button class="btn btn-sm btn-primary" id="btn-add-kategori"><i class="fas fa-plus"></i> Tambah Kategori</button></div>
                </div>
                <table class="table table-sm table-hover w-100 ml-table" id="labkategories-table">
                    <thead class="thead-light">
                        <tr>
                            <th>Kategori</th>
                            <th class="text-right">Jumlah test</th>
                            <th class="text-right" style="width:1%">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div></div>
        </div>
    </div>
</div>

<!-- Modal Lab Test -->
<div class="modal fade" id="modalLabTest" tabindex="-1" role="dialog" aria-labelledby="labTestModalTitle" aria-hidden="true" data-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
    <form class="modal-content" id="form-lab-test" autocomplete="off">
      <div class="modal-header">
        <h5 class="modal-title" id="labTestModalTitle">Tambah Lab Test</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
          <input type="hidden" id="lab_test_id">
          <div class="form-row">
              <div class="form-group col-md-6">
                  <label for="lab_nama">Nama test <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="lab_nama" maxlength="255" required>
                  <div class="invalid-feedback" data-error="nama"></div>
              </div>
              <div class="form-group col-md-6">
                  <label for="lab_kategori_id">Kategori <span class="text-danger">*</span></label>
                  <select id="lab_kategori_id" class="custom-select" required></select>
                  <div class="invalid-feedback" data-error="lab_kategori_id"></div>
              </div>
          </div>
          <div class="form-row">
              <div class="form-group col-md-4">
                  <label for="lab_harga">Harga (Rp)</label>
                  <input type="number" min="0" step="1" class="form-control" id="lab_harga">
                  <div class="invalid-feedback" data-error="harga"></div>
              </div>
          </div>
          <hr>
          <label class="mb-0">Obat / BHP yang dipakai</label>
          <small class="form-text text-muted mt-0 mb-1">Dosis per test, mis. 0.5. Satuan mengikuti data obat.</small>
          <table class="table table-sm table-bordered mb-1" id="lab-obat-table">
              <thead class="thead-light">
                  <tr>
                      <th>Obat</th>
                      <th style="width:120px">Dosis</th>
                      <th style="width:90px">Satuan</th>
                      <th style="width:34px"></th>
                  </tr>
              </thead>
              <tbody id="lab-obat-list"></tbody>
          </table>
          <div class="invalid-feedback d-block" data-error="obat"></div>
          <button type="button" class="btn btn-sm btn-outline-success" id="btn-add-obat"><i class="fas fa-plus"></i> Tambah Obat</button>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary" id="btn-save-labtest"><i class="fas fa-save"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Kategori -->
<div class="modal fade" id="modalKategori" tabindex="-1" role="dialog" aria-labelledby="kategoriModalTitle" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form class="modal-content" id="form-kategori" autocomplete="off">
      <div class="modal-header">
        <h5 class="modal-title" id="kategoriModalTitle">Tambah Kategori</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
          <input type="hidden" id="kategori_id">
          <div class="form-group mb-0">
              <label for="kategori_nama">Nama kategori <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="kategori_nama" maxlength="255" required>
              <div class="invalid-feedback" data-error="nama"></div>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary" id="btn-save-kategori"><i class="fas fa-save"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Paket Lab -->
<div class="modal fade" id="modalLabPaket" tabindex="-1" role="dialog" aria-labelledby="labPaketModalTitle" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <form class="modal-content" id="form-lab-paket" autocomplete="off">
            <div class="modal-header">
                <h5 class="modal-title" id="labPaketModalTitle">Tambah Paket Lab</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="lab_paket_id">
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label for="paket_nama">Nama paket <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="paket_nama" maxlength="255" required>
                        <div class="invalid-feedback" data-error="nama"></div>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="paket_harga">Harga paket (Rp) <span class="text-danger">*</span></label>
                        <input type="number" min="0" step="1" class="form-control" id="paket_harga" required>
                        <div class="invalid-feedback" data-error="harga_paket"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="paket_deskripsi">Deskripsi</label>
                    <textarea class="form-control" id="paket_deskripsi" rows="2"></textarea>
                    <div class="invalid-feedback" data-error="deskripsi"></div>
                </div>
                <div class="form-group mb-2">
                    <label for="paket_lab_test_search">Tambah test ke paket</label>
                    <select id="paket_lab_test_search" class="form-control" style="width:100%"></select>
                </div>
                <table class="table table-sm table-bordered mb-1" id="paket-lab-test-table">
                    <thead class="thead-light">
                        <tr>
                            <th>Test</th>
                            <th>Kategori</th>
                            <th class="text-right">Harga satuan</th>
                            <th style="width:34px"></th>
                        </tr>
                    </thead>
                    <tbody id="paket-lab-test-list"></tbody>
                    <tfoot>
                        <tr class="small">
                            <td colspan="2" class="text-right text-muted">Total harga satuan</td>
                            <td class="text-right font-weight-bold" id="paket-total-satuan">Rp 0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                <div class="small" id="paket-hemat"></div>
                <div class="invalid-feedback d-block" data-error="lab_test_ids"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" id="btn-save-labpaket"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    var CSRF = $('meta[name="csrf-token"]').attr('content');
    var state = { available: '', needCounts: false };
    var paketLabTests = [];

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' } });

    function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }
    function rupiah(v) { return 'Rp ' + Number(v || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 }); }
    function errMessage(xhr, fallback) {
        var json = (xhr && xhr.responseJSON) || {};
        if (json.errors) { var first = Object.values(json.errors)[0]; if (first && first.length) return first[0]; }
        return json.message || fallback || 'Terjadi kesalahan';
    }
    function showError(msg) { Swal.fire({ icon: 'error', title: 'Gagal', text: msg }); }
    function toast(msg) { Swal.fire({ icon: 'success', title: msg, timer: 1500, showConfirmButton: false }); }
    function clearErrors($form) { $form.find('.is-invalid').removeClass('is-invalid'); $form.find('[data-error]').text(''); }
    // 422 errors next to their field: data-error="<field>" (obat.0.dosis -> obat)
    function showErrors($form, xhr, fieldMap) {
        var errors = (xhr.responseJSON && xhr.responseJSON.errors) || {};
        if (!Object.keys(errors).length) { showError(errMessage(xhr)); return; }
        $.each(errors, function (key, msgs) {
            var field = key.split('.')[0];
            $form.find('[data-error="' + field + '"]').text(msgs[0]);
            if (fieldMap && fieldMap[field]) $(fieldMap[field]).addClass('is-invalid');
        });
    }
    function confirmDelete(title, text) {
        return Swal.fire({ icon: 'warning', title: title, text: text, showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal', reverseButtons: true, focusCancel: true });
    }
    var LANG = function (unit) {
        return {
            processing: '<i class="fas fa-spinner fa-spin"></i> Memuat...',
            zeroRecords: 'Tidak ada ' + unit + ' yang cocok', emptyTable: 'Belum ada ' + unit,
            info: '_START_–_END_ dari _TOTAL_ ' + unit, infoEmpty: '0 ' + unit, infoFiltered: '',
            lengthMenu: 'Tampil _MENU_', paginate: { previous: '‹', next: '›' }
        };
    };
    var DOM = 'rt<"d-flex flex-wrap justify-content-between align-items-center mt-2"lip>';
    // "Data yang perlu action" card for this tab (keys are prefixed test_ / paket_ / kategori_)
    function actionFor(tab) { return window.ActionCards && ActionCards.active && ActionCards.active.indexOf(tab + '_') === 0 ? ActionCards.active : null; }
    function debounceSearch($input, table) {
        var timer;
        $input.on('input', function () {
            var v = this.value;
            clearTimeout(timer);
            timer = setTimeout(function () { table.search(v).draw(); }, 350);
        });
    }

    // ================= Lab Test =================
    var testTable = $('#labtests-table').DataTable({
        processing: true, serverSide: true, deferRender: true, autoWidth: false, pageLength: 25,
        order: [[0, 'asc']], dom: DOM, language: LANG('test'),
        ajax: {
            url: '{{ route('erm.labtests.data') }}',
            data: function (d) {
                d.available = state.available;
                d.kategori_id = $('#testKategoriFilter').val();
                d.action = actionFor('test');
                // Tab, status and card counts: on demand, after a change on any tab
                if (state.needCounts) { d.counts = 1; state.needCounts = false; }
            }
        },
        columns: [
            {
                data: 'nama', name: 'nama', searchable: false,
                render: function (d, t, r) { return '<div class="ml-nama">' + esc(d) + '</div><div class="small text-muted">' + esc(r.kategori || '-') + '</div>'; }
            },
            { data: 'harga', name: 'harga', searchable: false, className: 'text-right text-nowrap', render: function (d) { return Number(d) > 0 ? rupiah(d) : '<span class="badge badge-dark">belum ada harga</span>'; } },
            {
                data: 'obat', orderable: false, searchable: false,
                render: function (d) {
                    if (!d || !d.length) return '<span class="small text-muted">-</span>';
                    return '<ul class="ml-list">' + d.map(function (o) { return '<li>' + esc(o.nama) + ' <span class="text-muted">· ' + esc(o.dosis) + (o.satuan ? ' ' + esc(o.satuan) : '') + '</span></li>'; }).join('') + '</ul>';
                }
            },
            { data: 'paket', orderable: false, searchable: false, render: function (d) { return d && d.length ? '<span class="small">' + d.map(esc).join(', ') + '</span>' : '<span class="small text-muted">-</span>'; } },
            {
                data: 'available', name: 'available', searchable: false, className: 'text-center',
                render: function (d, t, r) {
                    var on = d !== 'no';
                    return '<div class="custom-control custom-switch d-inline-block" title="' + (on ? 'Tersedia: klik untuk tandai tidak tersedia' : 'Tidak tersedia: klik untuk tandai tersedia') + '">'
                        + '<input type="checkbox" class="custom-control-input test-toggle" id="lt_on_' + r.id + '" data-id="' + r.id + '"' + (on ? ' checked' : '') + '>'
                        + '<label class="custom-control-label" for="lt_on_' + r.id + '"></label></div>';
                }
            },
            {
                data: null, orderable: false, searchable: false, className: 'text-right text-nowrap',
                render: function (d, t, r) {
                    var used = r.dipakai > 0 || (r.paket || []).length > 0;
                    return '<button type="button" class="btn btn-sm btn-outline-primary edit-test" data-id="' + r.id + '"><i class="fas fa-edit"></i> Edit</button> '
                        + '<button type="button" class="btn btn-sm btn-outline-danger delete-test" data-id="' + r.id + '" data-nama="' + esc(r.nama) + '"'
                        + ' title="' + (used ? 'Sudah dipakai (' + r.dipakai + ' permintaan): tandai tidak tersedia saja' : 'Hapus') + '"><i class="fas fa-trash"></i></button>';
                }
            }
        ],
        createdRow: function (tr, r) { $(tr).toggleClass('ml-off', r.available === 'no'); }
    });
    testTable.on('xhr', function (e, s, json) {
        if (!json) return;
        if (json.action_counts) ActionCards.update(json.action_counts);
        if (!json.counts) return;
        $.each(json.counts.test, function (k, v) { $('#testAvailable [data-count="' + k + '"]').text(v); });
        $('[data-tab-count="test"]').text(json.counts.test.all);
    });
    // After a change anywhere: reload the tests together with every count
    function reloadTests() { state.needCounts = true; testTable.ajax.reload(null, false); }

    debounceSearch($('#testSearch'), testTable);
    $('#testAvailable').on('click', '.btn', function () {
        state.available = $(this).data('available');
        $('#testAvailable .btn').removeClass('active');
        $(this).addClass('active');
        testTable.ajax.reload();
    });
    $('#testKategoriFilter').on('change', function () { testTable.ajax.reload(); });

    $('#labtests-table').on('change', '.test-toggle', function () {
        var $cb = $(this).prop('disabled', true);
        $.post('/erm/lab-tests/' + $cb.data('id') + '/toggle-availability')
            .done(reloadTests)
            .fail(function (xhr) { $cb.prop('checked', !$cb.prop('checked')); showError(errMessage(xhr, 'Status gagal diubah')); })
            .always(function () { $cb.prop('disabled', false); });
    });

    // Kategori options for the form and the filter (refreshed after a kategori change)
    var kategoriOptions = @json($kategoris);
    function renderKategoriOptions() {
        var opts = kategoriOptions.map(function (k) { return '<option value="' + k.id + '">' + esc(k.nama) + '</option>'; }).join('');
        $('#lab_kategori_id').html('<option value="">Pilih kategori</option>' + opts);
        var current = $('#testKategoriFilter').val();
        $('#testKategoriFilter').html('<option value="">Semua kategori</option>' + opts).val(current);
    }
    function refreshKategoriOptions() {
        $.getJSON('{{ route('erm.labkategories.data') }}', { start: 0, length: -1, draw: 1 }).done(function (res) {
            kategoriOptions = (res.data || []).map(function (k) { return { id: k.id, nama: k.nama }; }).sort(function (a, b) { return a.nama.localeCompare(b.nama); });
            renderKategoriOptions();
        });
    }
    renderKategoriOptions();

    // Obat rows
    function initObatSelect($row, selected) {
        var $select = $row.find('.obat-select');
        $select.select2({
            dropdownParent: $('#modalLabTest'),
            placeholder: 'Cari obat...',
            minimumInputLength: 2,
            width: '100%',
            ajax: {
                url: '{{ route('obat.search') }}',
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) { return { results: data.results || [] }; }
            }
        }).on('select2:select', function (e) {
            var d = e.params.data || {};
            $row.find('.obat-satuan').text(d.satuan ? String(d.satuan).toLowerCase() : '');
        });
        if (selected) $select.append(new Option(selected.nama, selected.obat_id, true, true)).trigger('change');
    }
    function addObatRow(data) {
        data = data || {};
        var $row = $('<tr class="obat-row">'
            + '<td><select class="form-control obat-select"></select></td>'
            + '<td><input type="number" step="0.01" min="0" class="form-control form-control-sm obat-dosis" placeholder="Dosis" value="' + esc(data.dosis != null ? data.dosis : '') + '"></td>'
            + '<td class="obat-satuan small align-middle">' + esc(data.satuan ? String(data.satuan).toLowerCase() : '') + '</td>'
            + '<td class="text-center align-middle"><button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-obat" title="Hapus"><i class="fas fa-times"></i></button></td>'
            + '</tr>');
        $('#lab-obat-list').append($row);
        initObatSelect($row, data.obat_id ? data : null);
        return $row;
    }
    $('#btn-add-obat').on('click', function () { addObatRow().find('.obat-select').select2('open'); });
    $('#lab-obat-list').on('click', '.btn-remove-obat', function () { $(this).closest('tr').remove(); });

    function openTestForm(data) {
        data = data || {};
        clearErrors($('#form-lab-test'));
        $('#lab_test_id').val(data.id || '');
        $('#lab_nama').val(data.nama || '');
        $('#lab_harga').val(data.harga != null ? Math.round(data.harga) : '');
        renderKategoriOptions();
        $('#lab_kategori_id').val(data.lab_kategori_id || $('#testKategoriFilter').val() || '');
        $('#lab-obat-list').empty();
        (data.obats || []).forEach(function (o) { addObatRow({ obat_id: o.id, nama: o.nama, dosis: o.pivot ? Number(o.pivot.dosis) : '', satuan: o.satuan }); });
        $('#labTestModalTitle').text(data.id ? 'Edit Lab Test: ' + data.nama : 'Tambah Lab Test');
        $('#modalLabTest').modal('show');
    }
    $('#btn-add-test').on('click', function () { openTestForm(); });
    $('#labtests-table').on('click', '.edit-test', function () {
        $.getJSON('/erm/lab-tests/' + $(this).data('id')).done(openTestForm).fail(function (xhr) { showError(errMessage(xhr, 'Data test gagal dimuat')); });
    });
    $('#modalLabTest').on('shown.bs.modal', function () { $('#lab_nama').trigger('focus'); });

    $('#form-lab-test').on('submit', function (e) {
        e.preventDefault();
        var $form = $(this);
        clearErrors($form);
        var id = $('#lab_test_id').val();
        var obat = [];
        $('#lab-obat-list .obat-row').each(function () {
            var obatId = $(this).find('.obat-select').val();
            if (obatId) obat.push({ obat_id: obatId, dosis: $(this).find('.obat-dosis').val() || 0 });
        });
        var $btn = $('#btn-save-labtest').prop('disabled', true);
        $.ajax({
            url: id ? '/erm/lab-tests/' + id : '/erm/lab-tests', type: id ? 'PUT' : 'POST',
            data: { nama: $('#lab_nama').val(), lab_kategori_id: $('#lab_kategori_id').val(), harga: $('#lab_harga').val(), obat: obat }
        }).done(function (res) {
            $('#modalLabTest').modal('hide');
            reloadTests();
            kategoriTable.ajax.reload(null, false);
            paketTable.ajax.reload(null, false);
            toast(res.message || 'Tersimpan');
        }).fail(function (xhr) {
            showErrors($form, xhr, { nama: '#lab_nama', lab_kategori_id: '#lab_kategori_id', harga: '#lab_harga' });
        }).always(function () { $btn.prop('disabled', false); });
    });

    $('#labtests-table').on('click', '.delete-test', function () {
        var id = $(this).data('id');
        confirmDelete('Hapus test ' + $(this).data('nama') + '?', 'Test yang sudah pernah diminta atau ada di paket tidak bisa dihapus; tandai tidak tersedia saja.').then(function (r) {
            if (!r.value) return;
            $.ajax({ url: '/erm/lab-tests/' + id, type: 'DELETE' })
                .done(function (res) { reloadTests(); kategoriTable.ajax.reload(null, false); toast(res.message || 'Dihapus'); })
                .fail(function (xhr) { showError(errMessage(xhr)); });
        });
    });

    // ================= Paket Lab =================
    var paketTable = $('#labpakets-table').DataTable({
        processing: true, serverSide: true, deferRender: true, autoWidth: false, pageLength: 25,
        order: [[0, 'asc']], dom: DOM, language: LANG('paket'),
        ajax: { url: '{{ route('erm.labpakets.data') }}', data: function (d) { d.action = actionFor('paket'); } },
        columns: [
            { data: 'nama', name: 'nama', searchable: false, render: function (d, t, r) { return '<div class="ml-nama">' + esc(d) + '</div>' + (r.deskripsi ? '<div class="small text-muted">' + esc(r.deskripsi) + '</div>' : ''); } },
            {
                data: 'jumlah_test', name: 'jumlah_test', searchable: false,
                render: function (d, t, r) {
                    var names = (r.tests || []).map(function (x) { return x.available ? esc(x.nama) : '<span class="text-muted" title="Tidak tersedia">' + esc(x.nama) + ' (tidak tersedia)</span>'; });
                    return '<span class="badge badge-info">' + d + '</span> <span class="small">' + names.join(', ') + '</span>';
                }
            },
            {
                data: 'harga_paket', name: 'harga_paket', searchable: false, className: 'text-right text-nowrap',
                render: function (d, t, r) {
                    var html = '<strong>' + rupiah(d) + '</strong>';
                    if (r.total_satuan > 0) {
                        var selisih = r.total_satuan - d;
                        html += '<div class="small ' + (selisih >= 0 ? 'text-muted' : 'text-danger') + '">satuan ' + rupiah(r.total_satuan) + (selisih > 0 ? ' · hemat ' + rupiah(selisih) : (selisih < 0 ? ' · lebih mahal ' + rupiah(-selisih) : '')) + '</div>';
                    }
                    return html;
                }
            },
            {
                data: null, orderable: false, searchable: false, className: 'text-right text-nowrap',
                render: function (d, t, r) {
                    return '<button type="button" class="btn btn-sm btn-outline-primary edit-paket" data-id="' + r.id + '"><i class="fas fa-edit"></i> Edit</button> '
                        + '<button type="button" class="btn btn-sm btn-outline-danger delete-paket" data-id="' + r.id + '" data-nama="' + esc(r.nama) + '" title="Hapus"><i class="fas fa-trash"></i></button>';
                }
            }
        ]
    });
    paketTable.on('xhr', function (e, s, json) { if (json) $('[data-tab-count="paket"]').text(json.recordsTotal); });
    debounceSearch($('#paketSearch'), paketTable);

    $('#paket_lab_test_search').select2({
        dropdownParent: $('#modalLabPaket'),
        placeholder: 'Cari test lalu pilih untuk menambahkan',
        width: '100%',
        ajax: {
            url: '{{ route('erm.labtests.list') }}',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) {
                return { results: (data || []).map(function (item) {
                    return { id: item.id, text: item.nama + ' · ' + (item.kategori || '-') + ' · ' + rupiah(item.harga) + (item.available === 'no' ? ' (tidak tersedia)' : ''),
                        nama: item.nama, kategori: item.kategori || '-', harga: item.harga || 0 };
                }) };
            }
        }
    }).on('select2:select', function (e) {
        var item = e.params.data || {};
        if (!paketLabTests.some(function (row) { return Number(row.id) === Number(item.id); })) {
            paketLabTests.push({ id: Number(item.id), nama: item.nama, kategori: item.kategori, harga: Number(item.harga) || 0 });
            renderPaketLabTestRows();
            $('#form-lab-paket [data-error="lab_test_ids"]').text('');
        }
        $(this).val(null).trigger('change');
    });

    function renderPaketLabTestRows() {
        var $tbody = $('#paket-lab-test-list').empty();
        if (!paketLabTests.length) {
            $tbody.append('<tr><td colspan="4" class="text-center text-muted small">Belum ada test di dalam paket.</td></tr>');
        }
        paketLabTests.forEach(function (item) {
            $tbody.append('<tr><td>' + esc(item.nama) + '</td><td class="small">' + esc(item.kategori || '-') + '</td><td class="text-right">' + rupiah(item.harga) + '</td>'
                + '<td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-paket-test" data-id="' + item.id + '" title="Keluarkan"><i class="fas fa-times"></i></button></td></tr>');
        });
        refreshPaketHemat();
    }
    // Paket price against the tests one by one
    function refreshPaketHemat() {
        var total = paketLabTests.reduce(function (s, x) { return s + (Number(x.harga) || 0); }, 0);
        var harga = Number($('#paket_harga').val()) || 0;
        $('#paket-total-satuan').text(rupiah(total));
        var html = '';
        if (total > 0 && harga > 0) {
            var selisih = total - harga;
            html = selisih > 0 ? '<span class="text-success">Pasien hemat ' + rupiah(selisih) + ' (' + Math.round(selisih / total * 100) + '%) dibanding beli satuan.</span>'
                : (selisih < 0 ? '<span class="text-danger">Harga paket lebih mahal ' + rupiah(-selisih) + ' dari total harga satuan.</span>' : '<span class="text-muted">Sama dengan total harga satuan.</span>');
        }
        $('#paket-hemat').html(html);
    }
    $('#paket_harga').on('input', refreshPaketHemat);
    $('#paket-lab-test-list').on('click', '.btn-remove-paket-test', function () {
        var id = Number($(this).data('id'));
        paketLabTests = paketLabTests.filter(function (item) { return Number(item.id) !== id; });
        renderPaketLabTestRows();
    });

    function openPaketForm(data) {
        data = data || {};
        clearErrors($('#form-lab-paket'));
        $('#lab_paket_id').val(data.id || '');
        $('#paket_nama').val(data.nama || '');
        $('#paket_harga').val(data.harga_paket != null ? Math.round(data.harga_paket) : '');
        $('#paket_deskripsi').val(data.deskripsi || '');
        paketLabTests = (data.lab_tests || []).map(function (item) {
            return { id: Number(item.id), nama: item.nama, kategori: item.lab_kategori ? item.lab_kategori.nama : '-', harga: Number(item.harga) || 0 };
        });
        $('#paket_lab_test_search').val(null).trigger('change');
        renderPaketLabTestRows();
        $('#labPaketModalTitle').text(data.id ? 'Edit Paket Lab: ' + data.nama : 'Tambah Paket Lab');
        $('#modalLabPaket').modal('show');
    }
    $('#btn-add-paket').on('click', function () { openPaketForm(); });
    $('#labpakets-table').on('click', '.edit-paket', function () {
        $.getJSON('/erm/lab-pakets/' + $(this).data('id')).done(openPaketForm).fail(function (xhr) { showError(errMessage(xhr, 'Data paket gagal dimuat')); });
    });

    $('#form-lab-paket').on('submit', function (e) {
        e.preventDefault();
        var $form = $(this);
        clearErrors($form);
        var id = $('#lab_paket_id').val();
        var $btn = $('#btn-save-labpaket').prop('disabled', true);
        $.ajax({
            url: id ? '/erm/lab-pakets/' + id : '/erm/lab-pakets', type: id ? 'PUT' : 'POST',
            data: { nama: $('#paket_nama').val(), harga_paket: $('#paket_harga').val(), deskripsi: $('#paket_deskripsi').val(), lab_test_ids: paketLabTests.map(function (x) { return x.id; }) }
        }).done(function (res) {
            $('#modalLabPaket').modal('hide');
            paketTable.ajax.reload(null, false);
            reloadTests();
            toast(res.message || 'Tersimpan');
        }).fail(function (xhr) {
            showErrors($form, xhr, { nama: '#paket_nama', harga_paket: '#paket_harga', deskripsi: '#paket_deskripsi' });
        }).always(function () { $btn.prop('disabled', false); });
    });

    $('#labpakets-table').on('click', '.delete-paket', function () {
        var id = $(this).data('id');
        confirmDelete('Hapus paket ' + $(this).data('nama') + '?', 'Paket yang sudah pernah diminta atau ditagih tidak bisa dihapus.').then(function (r) {
            if (!r.value) return;
            $.ajax({ url: '/erm/lab-pakets/' + id, type: 'DELETE' })
                .done(function (res) { paketTable.ajax.reload(null, false); reloadTests(); toast(res.message || 'Dihapus'); })
                .fail(function (xhr) { showError(errMessage(xhr)); });
        });
    });

    // ================= Kategori =================
    var kategoriTable = $('#labkategories-table').DataTable({
        processing: true, serverSide: true, deferRender: true, autoWidth: false, pageLength: 25,
        order: [[0, 'asc']], dom: DOM, language: LANG('kategori'),
        ajax: { url: '{{ route('erm.labkategories.data') }}', data: function (d) { d.action = actionFor('kategori'); } },
        columns: [
            { data: 'nama', name: 'nama', searchable: false, render: function (d) { return '<span class="ml-nama">' + esc(d) + '</span>'; } },
            {
                data: 'lab_tests_count', name: 'lab_tests_count', searchable: false, className: 'text-right',
                render: function (d, t, r) { return d > 0 ? '<a href="#" class="kategori-tests" data-id="' + r.id + '" title="Lihat test di kategori ini">' + d + ' test</a>' : '<span class="text-muted">0</span>'; }
            },
            {
                data: null, orderable: false, searchable: false, className: 'text-right text-nowrap',
                render: function (d, t, r) {
                    return '<button type="button" class="btn btn-sm btn-outline-primary edit-kategori" data-id="' + r.id + '" data-nama="' + esc(r.nama) + '"><i class="fas fa-edit"></i> Edit</button> '
                        + '<button type="button" class="btn btn-sm btn-outline-danger delete-kategori" data-id="' + r.id + '" data-nama="' + esc(r.nama) + '"'
                        + (r.lab_tests_count > 0 ? ' disabled title="Masih dipakai ' + r.lab_tests_count + ' test"' : ' title="Hapus"') + '><i class="fas fa-trash"></i></button>';
                }
            }
        ]
    });
    kategoriTable.on('xhr', function (e, s, json) { if (json) $('[data-tab-count="kategori"]').text(json.recordsTotal); });
    debounceSearch($('#kategoriSearch'), kategoriTable);

    // Jumlah test: open the Lab Test tab filtered on that kategori
    $('#labkategories-table').on('click', '.kategori-tests', function (e) {
        e.preventDefault();
        $('#testKategoriFilter').val($(this).data('id'));
        $('.ml-tabs a[href="#paneTest"]').tab('show');
        testTable.ajax.reload();
    });

    function openKategoriForm(id, nama) {
        clearErrors($('#form-kategori'));
        $('#kategori_id').val(id || '');
        $('#kategori_nama').val(nama || '');
        $('#kategoriModalTitle').text(id ? 'Edit Kategori' : 'Tambah Kategori');
        $('#modalKategori').modal('show');
    }
    $('#btn-add-kategori').on('click', function () { openKategoriForm(); });
    $('#labkategories-table').on('click', '.edit-kategori', function () { openKategoriForm($(this).data('id'), $(this).data('nama')); });
    $('#modalKategori').on('shown.bs.modal', function () { $('#kategori_nama').trigger('focus'); });

    $('#form-kategori').on('submit', function (e) {
        e.preventDefault();
        var $form = $(this);
        clearErrors($form);
        var id = $('#kategori_id').val();
        var $btn = $('#btn-save-kategori').prop('disabled', true);
        $.ajax({ url: id ? '/erm/lab-kategories/' + id : '/erm/lab-kategories', type: id ? 'PUT' : 'POST', data: { nama: $('#kategori_nama').val() } })
            .done(function (res) {
                $('#modalKategori').modal('hide');
                kategoriTable.ajax.reload(null, false);
                reloadTests();
                refreshKategoriOptions();
                toast(res.message || 'Tersimpan');
            })
            .fail(function (xhr) { showErrors($form, xhr, { nama: '#kategori_nama' }); })
            .always(function () { $btn.prop('disabled', false); });
    });

    $('#labkategories-table').on('click', '.delete-kategori', function () {
        var id = $(this).data('id');
        confirmDelete('Hapus kategori ' + $(this).data('nama') + '?', '').then(function (r) {
            if (!r.value) return;
            $.ajax({ url: '/erm/lab-kategories/' + id, type: 'DELETE' })
                .done(function (res) { kategoriTable.ajax.reload(null, false); refreshKategoriOptions(); reloadTests(); toast(res.message || 'Dihapus'); })
                .fail(function (xhr) { showError(errMessage(xhr)); });
        });
    });

    // "Data yang perlu action": a card opens its tab with the other filters of that tab cleared
    var TAB_PANE = { test: '#paneTest', paket: '#panePaket', kategori: '#paneKategori' };
    ActionCards.init(function () {
        testTable.ajax.reload();
        paketTable.ajax.reload();
        kategoriTable.ajax.reload();
    }, function (key) {
        var tab = key.split('_')[0];
        if (tab === 'test') {
            $('#testSearch').val('');
            testTable.search('');
            $('#testKategoriFilter').val('');
            state.available = '';
            $('#testAvailable .btn').removeClass('active').filter('[data-available=""]').addClass('active');
        } else if (tab === 'paket') {
            $('#paketSearch').val('');
            paketTable.search('');
        } else {
            $('#kategoriSearch').val('');
            kategoriTable.search('');
        }
        $('.ml-tabs a[href="' + TAB_PANE[tab] + '"]').tab('show');
    });

    // Tables in hidden tabs are sized when shown
    $('.ml-tabs a[data-toggle="pill"]').on('shown.bs.tab', function () { $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust(); });

    // ================= Dokter lab aktif =================
    var savedDokter = '';
    $.getJSON('/erm/lab-config/dokters').done(function (resp) {
        var $sel = $('#lab_dokter_select').html('<option value="">- Tidak ada -</option>');
        (resp.data || []).forEach(function (d) { $sel.append('<option value="' + d.id + '">' + esc(d.name) + '</option>'); });
        $.getJSON('/erm/lab-config').done(function (r2) {
            savedDokter = r2.data && r2.data.dokter_id ? String(r2.data.dokter_id) : '';
            $sel.val(savedDokter);
        });
    });
    // Simpan only when the choice differs from what is stored
    $('#lab_dokter_select').on('change', function () { $('#btn-save-lab-dokter').prop('disabled', ($(this).val() || '') === savedDokter); });
    $('#btn-save-lab-dokter').on('click', function () {
        var $btn = $(this).prop('disabled', true);
        var dokterId = $('#lab_dokter_select').val() || null;
        $.post('/erm/lab-config/save', { dokter_id: dokterId })
            .done(function () { savedDokter = dokterId ? String(dokterId) : ''; toast('Dokter lab disimpan'); })
            .fail(function (xhr) { $btn.prop('disabled', false); showError(errMessage(xhr, 'Gagal menyimpan')); });
    });
});
</script>
@endsection
