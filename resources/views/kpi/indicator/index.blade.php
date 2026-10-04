@extends('layouts.hrd.app')

@section('title', 'KPI | Master Indicators')

@section('navbar')
    @include('layouts.kpi.navbar')
@endsection

@section('content')
<style>
    #positionIndicatorModal .category-pick { cursor: pointer; }
    #positionIndicatorModal .category-pick.active .text-muted { color: rgba(255, 255, 255, .8) !important; }
    #positionIndicatorModal .category-pick-list { max-height: 60vh; overflow-y: auto; }
</style>

<div class="container-fluid px-2">
    <div class="row mb-3">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">Master Indicators</h4>
                <div class="text-muted small">Pilih posisi, klik <strong>Indikator</strong>, pilih kategori, lalu isi indikator dan bobotnya.</div>
            </div>
            <div class="mt-2 mt-md-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="collapse" data-target="#categoryPanel" aria-expanded="false" aria-controls="categoryPanel">
                    <i class="fas fa-layer-group mr-1"></i>Kategori
                    <span id="categoryTotalBadge" class="badge badge-success ml-1">0.00%</span>
                </button>
                @if(Auth::check() && Auth::user()->hasAnyRole('Hrd', 'Admin'))
                    <button type="button" class="btn btn-outline-danger btn-sm ml-2" id="btnResetIndicators">
                        <i class="fas fa-trash-alt mr-1"></i>Hapus Semua Indikator
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Categories (collapsible) -->
    <div class="collapse mb-3" id="categoryPanel">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Indicator Categories</h5>
                    <small class="text-muted">Kategori penilaian, bobot, dan evaluator. Total bobot semua kategori harus 100%.</small>
                </div>
                <div>
                    <button type="button" class="btn btn-primary btn-sm" id="btnAddCategory">
                        <i class="fas fa-plus-circle mr-1"></i>Tambah Kategori
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm ml-2" id="btnImportCategory">
                        <i class="fas fa-file-import mr-1"></i>Import
                    </button>
                </div>
            </div>
            <div class="card-body p-2">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped w-100" id="categoryTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Category</th>
                                <th>Weight %</th>
                                <th>Indicators</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Positions -->
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Positions</h5>
                <small class="text-muted">Status bobot indikator per kategori untuk setiap posisi</small>
            </div>
            <select id="positionsDivisionFilter" class="form-control form-control-sm mt-2 mt-md-0" style="width:220px">
                <option value="">All Divisions</option>
                @foreach($divisions as $div)
                    <option value="{{ $div->id }}">{{ $div->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="card-body p-2">
            <div class="table-responsive">
                <table class="table table-bordered table-striped w-100" id="positionsTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Position</th>
                            <th>Division</th>
                            <th>Employee</th>
                            <th>Indicators</th>
                            <th>Categories</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: indicators of one position -->
<div class="modal fade" id="positionIndicatorModal" tabindex="-1" role="dialog" aria-labelledby="positionIndicatorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="positionIndicatorModalLabel">Indikator Posisi</h5>
                    <small class="text-muted" id="positionIndicatorModalDivision"></small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="small text-muted mb-2">1. Pilih kategori</div>
                        <div class="list-group category-pick-list" id="categoryPickList"></div>
                    </div>
                    <div class="col-md-8">
                        <div class="small text-muted mb-2">2. Isi indikator dan bobot (total per kategori 100%)</div>
                        <div id="categoryEditorEmpty" class="alert alert-light border text-center text-muted mb-0">Pilih kategori di sebelah kiri.</div>
                        <div id="categoryEditorSections" class="indicator-editor"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <small class="text-muted mr-auto">Indikator yang dihapus dan tidak dipakai posisi lain akan dibersihkan otomatis.</small>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="positionIndicatorSaveBtn">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: indicators of a shared category (same for every position) -->
<div class="modal fade" id="sharedIndicatorModal" tabindex="-1" role="dialog" aria-labelledby="sharedIndicatorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="sharedIndicatorModalLabel">Indikator Kategori</h5>
                    <small class="text-muted"><i class="fas fa-users mr-1"></i>Berlaku untuk semua posisi</small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="sharedIndicatorSection" class="indicator-editor"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="sharedIndicatorSaveBtn">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: category form -->
<div class="modal fade" id="categoryModal" tabindex="-1" role="dialog" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryModalLabel">Tambah Category</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="categoryForm">
                @csrf
                <input type="hidden" id="category_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="category_name">Category Name</label>
                        <input type="text" class="form-control" id="category_name" name="category_name" required>
                        <div class="invalid-feedback" data-field="category_name"></div>
                    </div>
                    <div class="form-group">
                        <label for="category_weight_percentage">Weight Percentage</label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control" id="category_weight_percentage" name="weight_percentage" required>
                        <div class="invalid-feedback" data-field="weight_percentage"></div>
                    </div>
                    <div class="form-group">
                        <label for="category_evaluator_type">Evaluator Type</label>
                        <select class="form-control" id="category_evaluator_type" name="evaluator_type" required>
                            <option value="direct_parent">Direct Parent</option>
                            <option value="specific_position">Specific Position</option>
                            <option value="bottom_up">Bottom Up</option>
                        </select>
                        <div class="invalid-feedback" data-field="evaluator_type"></div>
                    </div>
                    <div class="form-group d-none" id="evaluatorPositionGroup">
                        <label for="category_evaluator_position_id">Evaluator Position</label>
                        <select class="form-control select2" id="category_evaluator_position_id" name="evaluator_position_id">
                            <option value="">Pilih posisi</option>
                            @foreach($positions as $position)
                                <option value="{{ $position->id }}">{{ $position->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" data-field="evaluator_position_id"></div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="category_is_shared" name="is_shared">
                            <label class="custom-control-label" for="category_is_shared">Indikator sama untuk semua posisi</label>
                        </div>
                        <small class="form-text text-muted">Jika aktif, indikator kategori ini diisi sekali dari tabel kategori dan otomatis berlaku untuk semua posisi.</small>
                    </div>
                    <div class="form-group mb-0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="category_is_active" name="is_active" checked>
                            <label class="custom-control-label" for="category_is_active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary" id="saveCategoryBtn">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: import categories -->
<div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">Import Categories</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <form id="importForm">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="import_file">File (CSV or XLSX)</label>
                            <input type="file" class="form-control" id="import_file" name="file" accept=".csv,.xlsx,.xls">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Format hint</label>
                            <div class="alert alert-info p-2 mb-0" role="status">
                                <div class="font-weight-bold">Required columns:</div>
                                <div class="mt-1">
                                    <span class="badge badge-secondary mr-1">category_name</span><span class="badge badge-secondary mr-1">weight_percentage</span><span class="badge badge-secondary mr-1">evaluator_type</span>
                                </div>
                                <div class="mt-2 small">Optional: <span class="badge badge-light">evaluator_position_id (ID)</span>, <span class="badge badge-light">is_active</span></div>
                                <div class="mt-1 small text-muted">Headers are case-insensitive; extra columns are ignored.</div>
                                <div class="mt-2 small">
                                    <div class="font-weight-bold">Evaluator types:</div>
                                    <div class="mb-1"><span class="badge badge-light">direct_parent</span> <span class="badge badge-light">specific_position</span> <span class="badge badge-light">bottom_up</span></div>
                                    <div class="font-weight-bold">Available positions (ID : Name):</div>
                                    <div style="max-height:120px; overflow:auto;">
                                        <ul class="list-unstyled mb-0">
                                            @foreach($positions as $pos)
                                                <li><strong>{{ $pos->id }}</strong> &ndash; {{ $pos->name }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <div id="importPreviewArea" class="mt-3 d-none">
                    <h6>Preview</h6>
                    <div class="table-responsive" style="max-height:320px; overflow:auto">
                        <table class="table table-sm table-bordered" id="importPreviewTable">
                            <thead>
                                <tr id="importPreviewHeader"></tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="btnImportPreview">Preview</button>
                <button type="button" class="btn btn-success d-none" id="btnImportCommit">Import</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function showAjaxError(xhr, fallbackMessage) {
        var json = xhr.responseJSON || {};
        var message = json.message || fallbackMessage;
        if (json.errors) {
            var first = Object.keys(json.errors)[0];
            message = json.errors[first][0] || message;
        }
        Swal.fire({ icon: 'error', title: 'Error', text: message });
    }

    // ---------------------------------------------------------------
    // Positions table
    // ---------------------------------------------------------------
    var positionsTable = $('#positionsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('indicator.positions.data') }}",
            data: function (d) { d.division_id = $('#positionsDivisionFilter').val(); }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'name', name: 'name' },
            { data: 'division_name', name: 'division_name', orderable: false, searchable: false },
            { data: 'employee_count', name: 'employee_count', orderable: false, searchable: false },
            { data: 'indicators_count', name: 'indicators_count', orderable: false, searchable: false },
            { data: 'category_percentages', name: 'category_percentages', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        createdRow: function (row, data) {
            if (data && parseInt(data.has_issue) === 1) {
                $(row).addClass('table-warning');
            }
        }
    });

    $('#positionsDivisionFilter').on('change', function () { positionsTable.ajax.reload(); });

    // ---------------------------------------------------------------
    // Position indicator modal: pick a category, fill its indicators
    // ---------------------------------------------------------------
    function buildIndicatorRowHtml(row, listId) {
        row = row || {};
        var weight = (row.weight_percentage !== null && row.weight_percentage !== undefined && row.weight_percentage !== '')
            ? Number(row.weight_percentage).toFixed(2)
            : '';

        return '<tr class="position-indicator-row" data-indicator-id="' + (row.indicator_id || '') + '">'
            + '<td class="row-number align-middle text-center"></td>'
            + '<td>'
                + '<input type="text" class="form-control form-control-sm row-indicator-name mb-1" list="' + listId + '" placeholder="Nama indikator" value="' + escapeHtml(row.indicator_name || '') + '">'
                + '<textarea class="form-control form-control-sm row-indicator-notes" rows="1" placeholder="Catatan (opsional)">' + escapeHtml(row.notes || '') + '</textarea>'
            + '</td>'
            + '<td class="align-middle"><input type="number" min="0" max="100" step="0.01" class="form-control form-control-sm row-weight" placeholder="0.00" value="' + weight + '"></td>'
            + '<td class="text-center align-middle"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-indicator-row" title="Hapus"><i class="fas fa-times"></i></button></td>'
            + '</tr>';
    }

    function buildCategoryPickHtml(category) {
        var evaluator = (category.evaluator_type || '').replace(/_/g, ' ');
        return '<a href="#" class="list-group-item list-group-item-action category-pick" data-category-id="' + category.category_id + '">'
            + '<div class="d-flex justify-content-between align-items-start">'
            + '<div class="mr-2">'
                + '<div class="font-weight-bold">' + (category.is_shared ? '<i class="fas fa-users mr-1" title="Sama untuk semua posisi"></i>' : '') + escapeHtml(category.category_name) + '</div>'
                + '<small class="text-muted text-capitalize">Bobot ' + Number(category.category_weight_percentage || 0).toFixed(2) + '% &middot; ' + escapeHtml(evaluator) + '</small>'
            + '</div>'
            + '<span class="badge badge-pill category-pick-status"></span>'
            + '</div>'
            + '</a>';
    }

    function buildCategorySectionHtml(category) {
        var listId = 'indicatorSuggestions-' + category.category_id;
        var rows = '';
        $.each(category.rows || [], function (_, row) {
            rows += buildIndicatorRowHtml(row, listId);
        });
        var options = $.map(category.suggestions || [], function (name) {
            return '<option value="' + escapeHtml(name) + '">';
        }).join('');

        return '<div class="card mb-0 position-category-section d-none" data-category-id="' + category.category_id + '" data-list-id="' + listId + '">'
            + '<div class="card-header py-2">'
                + '<strong>' + escapeHtml(category.category_name) + '</strong>'
                + '<span class="small text-muted ml-2">Bobot kategori ' + Number(category.category_weight_percentage || 0).toFixed(2) + '%</span>'
            + '</div>'
            + '<div class="card-body p-2">'
                + '<datalist id="' + listId + '">' + options + '</datalist>'
                + '<table class="table table-sm table-bordered mb-2"><thead><tr><th style="width:50px" class="text-center">No</th><th>Indicator</th><th style="width:140px">Weight %</th><th style="width:56px"></th></tr></thead><tbody>' + rows + '</tbody></table>'
                + '<div class="d-flex justify-content-between align-items-center">'
                    + '<button type="button" class="btn btn-sm btn-outline-primary btn-add-indicator-row"><i class="fas fa-plus mr-1"></i>Tambah indikator</button>'
                    + '<span class="section-total small font-weight-bold"></span>'
                + '</div>'
            + '</div>'
            + '</div>';
    }

    // shared category inside a position: read-only, managed from the category table
    function buildSharedCategorySectionHtml(category) {
        var rows = '';
        $.each(category.rows || [], function (_, row) {
            rows += '<tr class="position-indicator-row">'
                + '<td class="row-number text-center"></td>'
                + '<td>' + escapeHtml(row.indicator_name || '')
                    + (row.notes ? '<div class="small text-muted">' + escapeHtml(row.notes) + '</div>' : '')
                + '</td>'
                + '<td class="text-right">' + Number(row.weight_percentage || 0).toFixed(2) + '%'
                    + '<input type="hidden" class="row-weight" value="' + Number(row.weight_percentage || 0) + '">'
                + '</td>'
                + '</tr>';
        });

        return '<div class="card mb-0 position-category-section d-none" data-category-id="' + category.category_id + '" data-shared="1">'
            + '<div class="card-header py-2">'
                + '<strong>' + escapeHtml(category.category_name) + '</strong>'
                + '<span class="small text-muted ml-2">Bobot kategori ' + Number(category.category_weight_percentage || 0).toFixed(2) + '%</span>'
            + '</div>'
            + '<div class="card-body p-2">'
                + '<div class="alert alert-info py-2 small mb-2"><i class="fas fa-users mr-1"></i>Indikator kategori ini sama untuk semua posisi. Ubah dari panel <strong>Kategori</strong> &rarr; tombol <i class="fas fa-list-ul"></i>.</div>'
                + '<table class="table table-sm table-bordered mb-2"><thead><tr><th style="width:50px" class="text-center">No</th><th>Indicator</th><th style="width:140px" class="text-right">Weight %</th></tr></thead><tbody>' + rows + '</tbody></table>'
                + '<div class="text-right"><span class="section-total small font-weight-bold"></span></div>'
            + '</div>'
            + '</div>';
    }

    function recalcCategorySection($section) {
        var $rows = $section.find('.position-indicator-row');
        var total = 0;

        $rows.each(function (index) {
            $(this).find('.row-number').text(index + 1);
            var value = parseFloat($(this).find('.row-weight').val());
            if (!isNaN(value)) {
                total += value;
            }
        });

        var count = $rows.length;
        var isEmpty = count === 0;
        var isValid = isEmpty || Math.abs(total - 100.0) <= 0.001;

        $section.find('.section-total')
            .text(isEmpty ? 'Belum ada indikator' : (count + ' indikator · Total: ' + total.toFixed(2) + '%'))
            .toggleClass('text-muted', isEmpty)
            .toggleClass('text-success', !isEmpty && isValid)
            .toggleClass('text-danger', !isValid);

        $('#categoryPickList .category-pick[data-category-id="' + $section.data('category-id') + '"] .category-pick-status')
            .text(isEmpty ? 'Kosong' : (count + ' · ' + total.toFixed(2) + '%'))
            .toggleClass('badge-light', isEmpty)
            .toggleClass('badge-success', !isEmpty && isValid)
            .toggleClass('badge-danger', !isValid);

        return isValid;
    }

    function recalcAllCategorySections() {
        var allValid = true;
        $('#categoryEditorSections .position-category-section').each(function () {
            // a wrong shared total is fixed in the category, it must not block saving the position
            if (!recalcCategorySection($(this)) && !$(this).data('shared')) {
                allValid = false;
            }
        });
        $('#positionIndicatorSaveBtn').prop('disabled', !allValid);
    }

    function recalcSharedSection() {
        var $section = $('#sharedIndicatorSection .position-category-section');
        $('#sharedIndicatorSaveBtn').prop('disabled', !$section.length || !recalcCategorySection($section));
    }

    function recalcEditorOf($el) {
        if ($el.closest('#sharedIndicatorModal').length) {
            recalcSharedSection();
        } else {
            recalcAllCategorySections();
        }
    }

    // read the editable rows of a section; marks empty fields and reports whether any row is incomplete
    function collectSectionRows($section) {
        var rows = [];
        var hasEmptyField = false;

        $section.find('.position-indicator-row').each(function () {
            var $row = $(this);
            var $name = $row.find('.row-indicator-name');
            var $weight = $row.find('.row-weight');
            var name = ($name.val() || '').trim();
            var weight = ($weight.val() || '').trim();

            $name.toggleClass('is-invalid', !name);
            $weight.toggleClass('is-invalid', weight === '');
            if (!name || weight === '') {
                hasEmptyField = true;
            }

            rows.push({
                indicator_id: $row.data('indicator-id') || '',
                indicator_name: name,
                notes: ($row.find('.row-indicator-notes').val() || '').trim(),
                weight_percentage: weight
            });
        });

        return { rows: rows, hasEmptyField: hasEmptyField };
    }

    function selectCategory(categoryId) {
        var $section = $('#categoryEditorSections .position-category-section[data-category-id="' + categoryId + '"]');
        if (!$section.length) {
            return;
        }

        $('#categoryPickList .category-pick').removeClass('active');
        $('#categoryPickList .category-pick[data-category-id="' + categoryId + '"]').addClass('active');
        $('#categoryEditorSections .position-category-section').addClass('d-none');
        $section.removeClass('d-none');
        $('#categoryEditorEmpty').addClass('d-none');
    }

    function renderPositionIndicatorModal(payload, initialCategoryId) {
        var position = payload.position || {};
        var categories = payload.categories || [];
        var picks = '';
        var sections = '';

        $.each(categories, function (_, category) {
            picks += buildCategoryPickHtml(category);
            sections += category.is_shared ? buildSharedCategorySectionHtml(category) : buildCategorySectionHtml(category);
        });

        $('#positionIndicatorModal').data('pos-id', position.id || null).data('dirty', false);
        $('#positionIndicatorModalLabel').text('Indikator: ' + (position.name || '-'));
        $('#positionIndicatorModalDivision').text(position.division_names ? 'Divisi: ' + position.division_names : '');
        $('#categoryPickList').html(picks || '<div class="alert alert-warning mb-0">Belum ada kategori aktif. Tambahkan kategori terlebih dahulu.</div>');
        $('#categoryEditorSections').html(sections);
        $('#categoryEditorEmpty').removeClass('d-none');

        recalcAllCategorySections();

        if (initialCategoryId) {
            selectCategory(initialCategoryId);
        } else if (categories.length) {
            selectCategory(categories[0].category_id);
        }

        $('#positionIndicatorModal').modal('show');
    }

    function openPositionIndicatorModal(positionId, categoryId) {
        $.get('/indicator/positions/' + positionId + '/editor', function (response) {
            renderPositionIndicatorModal((response && response.data) ? response.data : {}, categoryId);
        }).fail(function () {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal memuat indikator posisi.' });
        });
    }

    function openSharedIndicatorModal(categoryId) {
        $.get("{{ url('/indicator/categories') }}/" + categoryId + '/shared-indicators', function (response) {
            var category = (response && response.data) ? response.data : {};
            $('#sharedIndicatorModal').data('category-id', category.category_id).data('dirty', false);
            $('#sharedIndicatorModalLabel').text('Indikator: ' + (category.category_name || '-'));
            $('#sharedIndicatorSection').html(buildCategorySectionHtml(category));
            $('#sharedIndicatorSection .position-category-section').removeClass('d-none');
            recalcSharedSection();
            $('#sharedIndicatorModal').modal('show');
        }).fail(function (xhr) {
            showAjaxError(xhr, 'Gagal memuat indikator kategori.');
        });
    }

    $(document).on('click', '.btn-edit-position-mapping', function () {
        openPositionIndicatorModal($(this).data('id'));
    });

    $(document).on('click', '.category-badge', function () {
        openPositionIndicatorModal($(this).data('pos-id'), $(this).data('cat-id'));
    });

    $(document).on('click', '.btn-shared-indicators', function () {
        openSharedIndicatorModal($(this).data('id'));
    });

    $(document).on('click', '#categoryPickList .category-pick', function (e) {
        e.preventDefault();
        selectCategory($(this).data('category-id'));
    });

    $(document).on('click', '.indicator-editor .btn-add-indicator-row', function () {
        var $section = $(this).closest('.position-category-section');
        var $row = $(buildIndicatorRowHtml({}, $section.data('list-id')));
        $section.find('tbody').append($row);
        $(this).closest('.modal').data('dirty', true);
        recalcEditorOf($(this));
        $row.find('.row-indicator-name').trigger('focus');
    });

    $(document).on('click', '.indicator-editor .btn-remove-indicator-row', function () {
        var $modal = $(this).closest('.modal');
        $(this).closest('tr').remove();
        $modal.data('dirty', true);
        recalcEditorOf($modal);
    });

    $(document).on('input', '.indicator-editor .row-weight', function () {
        $(this).removeClass('is-invalid').closest('.modal').data('dirty', true);
        recalcEditorOf($(this));
    });

    $(document).on('input', '.indicator-editor .row-indicator-name, .indicator-editor .row-indicator-notes', function () {
        $(this).removeClass('is-invalid').closest('.modal').data('dirty', true);
    });

    // ask before throwing away unsaved changes
    $('#positionIndicatorModal, #sharedIndicatorModal').on('hide.bs.modal', function (e) {
        var $modal = $(this);
        if (!$modal.data('dirty')) {
            return;
        }

        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Perubahan belum disimpan',
            text: 'Tutup tanpa menyimpan?',
            showCancelButton: true,
            confirmButtonText: 'Tutup',
            cancelButtonText: 'Kembali'
        }).then(function (result) {
            if (result.value) {
                $modal.data('dirty', false).modal('hide');
            }
        });
    });

    $('#positionIndicatorSaveBtn').on('click', function () {
        var $btn = $(this);
        var posId = $('#positionIndicatorModal').data('pos-id');
        if (!posId) return;

        var categories = [];
        var firstInvalidCategoryId = null;

        $('#categoryEditorSections .position-category-section').not('[data-shared]').each(function () {
            var categoryId = $(this).data('category-id');
            var collected = collectSectionRows($(this));

            if (collected.hasEmptyField && firstInvalidCategoryId === null) {
                firstInvalidCategoryId = categoryId;
            }

            categories.push({ category_id: categoryId, rows: collected.rows });
        });

        if (firstInvalidCategoryId !== null) {
            selectCategory(firstInvalidCategoryId);
            Swal.fire({ icon: 'warning', title: 'Validation', text: 'Nama indikator dan bobot wajib diisi pada setiap baris.' });
            return;
        }

        if (!categories.length) {
            $('#positionIndicatorModal').data('dirty', false).modal('hide');
            return;
        }

        $btn.prop('disabled', true).text('Saving...');
        $.ajax({
            url: '/indicator/positions/' + posId + '/indicators',
            method: 'POST',
            data: { categories: categories },
            success: function (res) {
                $('#positionIndicatorModal').data('dirty', false).modal('hide');
                positionsTable.ajax.reload(null, false);
                categoryTable.ajax.reload(null, false);
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message || 'Indikator posisi berhasil disimpan.' });
            },
            error: function (xhr) {
                showAjaxError(xhr, 'Gagal menyimpan indikator posisi.');
            },
            complete: function () {
                $btn.text('Save');
                recalcAllCategorySections();
            }
        });
    });

    $('#sharedIndicatorSaveBtn').on('click', function () {
        var $btn = $(this);
        var categoryId = $('#sharedIndicatorModal').data('category-id');
        if (!categoryId) return;

        var collected = collectSectionRows($('#sharedIndicatorSection .position-category-section'));
        if (collected.hasEmptyField) {
            Swal.fire({ icon: 'warning', title: 'Validation', text: 'Nama indikator dan bobot wajib diisi pada setiap baris.' });
            return;
        }

        $btn.prop('disabled', true).text('Saving...');
        $.ajax({
            url: "{{ url('/indicator/categories') }}/" + categoryId + '/shared-indicators',
            method: 'POST',
            data: { rows: collected.rows },
            success: function (res) {
                $('#sharedIndicatorModal').data('dirty', false).modal('hide');
                positionsTable.ajax.reload(null, false);
                categoryTable.ajax.reload(null, false);
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message || 'Indikator kategori berhasil disimpan.' });
            },
            error: function (xhr) {
                showAjaxError(xhr, 'Gagal menyimpan indikator kategori.');
            },
            complete: function () {
                $btn.text('Save');
                recalcSharedSection();
            }
        });
    });

    // ---------------------------------------------------------------
    // Delete all indicators (old assessment scores are kept)
    // ---------------------------------------------------------------
    $('#btnResetIndicators').on('click', function () {
        $.get("{{ route('indicator.indicators.reset-preview') }}", function (res) {
            var d = (res && res.data) ? res.data : {};

            Swal.fire({
                icon: 'warning',
                title: 'Hapus semua indikator?',
                html: '<div class="text-left small">'
                    + '<ul class="pl-3 mb-2">'
                    + '<li><strong>' + d.mappings + '</strong> mapping indikator dilepas dari semua posisi</li>'
                    + '<li><strong>' + d.shared + '</strong> indikator kategori "semua posisi" dikosongkan</li>'
                    + '<li><strong>' + d.to_delete + '</strong> indikator dihapus permanen</li>'
                    + '<li><strong>' + d.to_keep + '</strong> indikator dinonaktifkan (tidak dihapus) karena dipakai penilaian lama</li>'
                    + '</ul>'
                    + '<div class="text-success mb-2"><i class="fas fa-shield-alt mr-1"></i>Riwayat nilai assessment lama tidak berubah. Kategori tidak dihapus.</div>'
                    + 'Ketik <strong>HAPUS</strong> untuk melanjutkan:'
                    + '</div>',
                input: 'text',
                inputPlaceholder: 'HAPUS',
                showCancelButton: true,
                confirmButtonText: 'Hapus Semua',
                confirmButtonColor: '#d33',
                cancelButtonText: 'Batal',
                preConfirm: function (value) {
                    if (value !== 'HAPUS') {
                        Swal.showValidationMessage('Ketik HAPUS (huruf besar) untuk konfirmasi.');
                        return false;
                    }
                    return value;
                }
            }).then(function (result) {
                if (!result.value) {
                    return;
                }

                $.ajax({
                    url: "{{ route('indicator.indicators.reset') }}",
                    type: 'POST',
                    data: { confirm: result.value },
                    success: function (response) {
                        positionsTable.ajax.reload(null, false);
                        categoryTable.ajax.reload(null, false);
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message });
                    },
                    error: function (xhr) {
                        showAjaxError(xhr, 'Gagal menghapus semua indikator.');
                    }
                });
            });
        }).fail(function (xhr) {
            showAjaxError(xhr, 'Gagal memuat ringkasan indikator.');
        });
    });

    // ---------------------------------------------------------------
    // Categories
    // ---------------------------------------------------------------
    var categoryTable = $('#categoryTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('indicator.categories.data') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            {
                data: 'category_name',
                name: 'category_name',
                render: function (data, type, row) {
                    var typeLabel = row.evaluator_type_label || '';
                    var posName = row.evaluator_position_name || '';
                    var cls = 'badge-secondary';
                    if (/direct parent/i.test(typeLabel)) cls = 'badge-primary';
                    else if (/specific/i.test(typeLabel)) cls = 'badge-success';
                    else if (/bottom/i.test(typeLabel)) cls = 'badge-warning';

                    var badgeText = typeLabel + (posName ? ' : ' + posName : '');
                    var sharedBadge = row.is_shared ? ' <span class="badge badge-info"><i class="fas fa-users mr-1"></i>Semua posisi</span>' : '';
                    return escapeHtml(data || '-') + '<div class="mt-1"><span class="badge ' + cls + '">' + escapeHtml(badgeText) + '</span>' + sharedBadge + '</div>';
                }
            },
            { data: 'weight_percentage', name: 'weight_percentage' },
            { data: 'indicators_count', name: 'indicators_count', searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        createdRow: function (row, data) {
            if (data && (data.is_active === 0 || data.is_active === '0' || data.is_active === false)) {
                $(row).addClass('table-danger');
            }
        }
    });

    categoryTable.on('draw', refreshCategoryTotal);

    function refreshCategoryTotal() {
        $.get("{{ route('indicator.categories.total') }}", function (res) {
            if (!res || typeof res.total === 'undefined') return;
            var total = Number(res.total);
            var ok = Math.abs(total - 100) <= 0.001;
            $('#categoryTotalBadge')
                .text(total.toFixed(2) + '%')
                .toggleClass('badge-success', ok)
                .toggleClass('badge-danger', !ok);
            if (!ok) {
                $('#categoryPanel').collapse('show');
            }
        });
    }

    function clearFormErrors(formSelector) {
        $(formSelector).find('.is-invalid').removeClass('is-invalid');
        $(formSelector).find('.invalid-feedback').text('');
    }

    function applyValidationErrors(formSelector, errors) {
        clearFormErrors(formSelector);
        $.each(errors || {}, function (field, messages) {
            $(formSelector).find('[name="' + field + '"]').addClass('is-invalid');
            $(formSelector).find('.invalid-feedback[data-field="' + field + '"]').text(messages[0]);
        });
    }

    function toggleEvaluatorPosition() {
        var isSpecific = $('#category_evaluator_type').val() === 'specific_position';
        $('#evaluatorPositionGroup').toggleClass('d-none', !isSpecific);
        if (!isSpecific) {
            $('#category_evaluator_position_id').val('').trigger('change');
        }
    }

    function resetCategoryForm() {
        $('#categoryForm')[0].reset();
        $('#category_id').val('');
        $('#category_is_active').prop('checked', true);
        $('#categoryModalLabel').text('Tambah Category');
        clearFormErrors('#categoryForm');
        $('#category_evaluator_position_id').val('').trigger('change');
        toggleEvaluatorPosition();
    }

    $('#category_evaluator_type').on('change', toggleEvaluatorPosition);

    if ($.isFunction($.fn.select2)) {
        $('#category_evaluator_position_id').select2({ width: '100%', dropdownParent: $('#categoryModal') });
    }

    $('#btnAddCategory').on('click', function () {
        resetCategoryForm();
        $('#categoryModal').modal('show');
    });

    $(document).on('click', '.btn-edit-category', function () {
        var id = $(this).data('id');
        resetCategoryForm();

        $.get("{{ url('/indicator/categories') }}/" + id, function (response) {
            var data = response.data;
            $('#category_id').val(data.id);
            $('#category_name').val(data.category_name);
            $('#category_weight_percentage').val(data.weight_percentage);
            $('#category_evaluator_type').val(data.evaluator_type);
            toggleEvaluatorPosition();
            $('#category_evaluator_position_id').val(data.evaluator_position_id).trigger('change');
            $('#category_is_active').prop('checked', !!data.is_active);
            $('#category_is_shared').prop('checked', !!data.is_shared);
            $('#categoryModalLabel').text('Edit Category');
            $('#categoryModal').modal('show');
        });
    });

    $('#categoryForm').on('submit', function (e) {
        e.preventDefault();

        var categoryId = $('#category_id').val();
        var isEdit = !!categoryId;
        var url = isEdit
            ? "{{ url('/indicator/categories') }}/" + categoryId
            : "{{ route('indicator.categories.store') }}";
        var payload = $(this).serialize() + (isEdit ? '&_method=PUT' : '');

        $.ajax({
            url: url,
            type: 'POST',
            data: payload,
            success: function (response) {
                $('#categoryModal').modal('hide');
                categoryTable.ajax.reload(null, false);
                positionsTable.ajax.reload(null, false);
                Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message });
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    applyValidationErrors('#categoryForm', xhr.responseJSON.errors);
                }
                showAjaxError(xhr, 'Gagal menyimpan category.');
            }
        });
    });

    $(document).on('click', '.btn-delete-category', function () {
        var id = $(this).data('id');
        var name = $(this).data('name');

        Swal.fire({
            icon: 'warning',
            title: 'Hapus category?',
            text: 'Category "' + name + '" akan dihapus bersama indikator terkait.',
            showCancelButton: true,
            confirmButtonText: 'Hapus',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.value) {
                return;
            }

            $.ajax({
                url: "{{ url('/indicator/categories') }}/" + id,
                type: 'POST',
                data: { _method: 'DELETE' },
                success: function (response) {
                    categoryTable.ajax.reload(null, false);
                    positionsTable.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message });
                },
                error: function (xhr) {
                    showAjaxError(xhr, 'Gagal menghapus category.');
                }
            });
        });
    });

    // ---------------------------------------------------------------
    // Import categories
    // ---------------------------------------------------------------
    $('#btnImportCategory').on('click', function () {
        $('#importPreviewArea').addClass('d-none');
        $('#btnImportCommit').addClass('d-none');
        $('#btnImportPreview').removeClass('d-none');
        $('#import_file').val('');
        $('#importModal').modal('show');
    });

    $('#btnImportPreview').on('click', function () {
        var file = $('#import_file')[0].files[0];
        if (!file) {
            Swal.fire({ icon: 'warning', title: 'File required', text: 'Please select a CSV or XLSX file.' });
            return;
        }

        var fd = new FormData();
        fd.append('file', file);
        fd.append('type', 'categories');

        var $btn = $(this);
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...');

        $.ajax({
            url: "{{ route('indicator.import.preview') }}",
            data: fd,
            type: 'POST',
            processData: false,
            contentType: false,
            success: function (res) {
                if (!res || !res.data) return;
                var data = res.data;
                var $thead = $('#importPreviewHeader').empty();
                var $tbody = $('#importPreviewTable tbody').empty();
                var keys = data[0] && data[0].raw ? Object.keys(data[0].raw) : [];

                $thead.append('<th>#</th>');
                $.each(keys, function (_, key) {
                    $thead.append('<th>' + escapeHtml(key.replace(/_/g, ' ')) + '</th>');
                });
                $thead.append('<th>Errors</th>');

                $.each(data, function (_, item) {
                    var errors = item.errors || [];
                    var $tr = $('<tr>').addClass(errors.length ? 'table-danger' : 'table-success');
                    $tr.append('<td>' + item.row_number + '</td>');
                    $.each(keys, function (_, key) {
                        $tr.append('<td>' + escapeHtml(item.raw && key in item.raw ? item.raw[key] : '') + '</td>');
                    });
                    $tr.append('<td>' + (errors.length ? escapeHtml(errors.join('; ')) : '-') + '</td>');
                    $tbody.append($tr);
                });

                $('#importPreviewArea').removeClass('d-none');
                $('#btnImportCommit').removeClass('d-none');
                $('#btnImportPreview').addClass('d-none');
                $('#importModal').data('preview', data);
            },
            error: function (xhr) {
                showAjaxError(xhr, 'Failed to parse file for preview.');
            },
            complete: function () {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    $('#btnImportCommit').on('click', function () {
        var preview = $('#importModal').data('preview');
        if (!preview) return;

        $.ajax({
            url: "{{ route('indicator.import.commit') }}",
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ type: 'categories', rows: preview }),
            success: function (res) {
                if (res.success) {
                    $('#importModal').modal('hide');
                    categoryTable.ajax.reload(null, false);
                    positionsTable.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: 'Imported', text: res.created + ' rows created' });
                } else {
                    Swal.fire({ icon: 'warning', title: 'Import completed with errors', text: 'Created: ' + (res.created || 0) });
                }
            },
            error: function (xhr) {
                showAjaxError(xhr, 'Import failed.');
            }
        });
    });
});
</script>
@endsection
