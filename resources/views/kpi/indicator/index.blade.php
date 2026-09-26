@extends('layouts.hrd.app')

@section('title', 'KPI | Master Indicators')

@section('navbar')
    @include('layouts.kpi.navbar')
@endsection

@section('content')
<div class="container-fluid px-2">
    <div class="row mb-3">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">Master Indicators</h4>
                <div class="text-muted small">Kelola kategori indikator dan indikator KPI melalui AJAX.</div>
            </div>
        </div>
    </div>

    <!-- Modal: edit indicators for a position -->
    <div class="modal fade" id="positionCategoryModal" tabindex="-1" role="dialog" aria-labelledby="positionCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="positionCategoryModalLabel">Edit Position Indicators</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div id="positionCategoryModalSummary" class="alert alert-info d-none mb-3"></div>
                    <div id="positionCategoryModalBody"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="positionCategorySaveBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 d-flex align-items-center">
                            <span>Indicator Categories</span>
                            <small id="categoryTotal" class="ml-3 mb-0 small font-weight-bold text-success">Total Weight: <span id="categoryTotalValue">0.00</span>%</small>
                        </h5>
                        <small class="text-muted d-block">Kategori penilaian dan bobot evaluator</small>
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

        <div class="col-lg-6 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Indicators</h5>
                        <small class="text-muted">Daftar indikator yang terhubung ke kategori</small>
                    </div>
                    <div class="d-flex align-items-center">
                        <select id="indicatorCategoryFilter" class="form-control form-control-sm mr-2" style="width:220px">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-primary btn-sm" id="btnAddIndicator">
                            <i class="fas fa-plus-circle mr-1"></i>Tambah Indikator
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm ml-2" id="btnImportIndicator">
                            <i class="fas fa-file-import mr-1"></i>Import
                        </button>
                    </div>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped w-100" id="indicatorTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Category</th>
                                    <th>Indicator</th>
                                    <th>Position Mapped</th>
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

    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Positions</h5>
                        <small class="text-muted">List of positions and indicators mapped to each position</small>
                    </div>
                    <div class="d-flex align-items-center">
                        <select id="positionsDivisionFilter" class="form-control form-control-sm mr-2" style="width:220px">
                            <option value="">All Divisions</option>
                            @foreach($divisions as $div)
                                <option value="{{ $div->id }}">{{ $div->name }}</option>
                            @endforeach
                        </select>
                    </div>
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
                                    <th>Indicators Count</th>
                                    <th>Indicators</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">Import Data</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <form id="importForm">
                    @csrf
                    <input type="hidden" id="import_type" name="type" value="categories">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="import_file">File (CSV or XLSX)</label>
                            <input type="file" class="form-control" id="import_file" name="file" accept=".csv,.xlsx,.xls">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="import_hint">Format hint</label>
                            <div id="import_hint" class="alert alert-info p-2 mb-0" role="status">
                                <div class="font-weight-bold">Required columns:</div>
                                <div id="import_hint_columns" class="mt-1"></div>
                                    <div class="mt-1 small text-muted">Headers are case-insensitive; extra columns are ignored.</div>
                                    <div id="import_hint_dynamic" class="mt-2 small">
                                        <!-- dynamic content populated by JS depending on import type -->
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
                                    <tr id="importPreviewHeader"><!-- populated dynamically --></tr>
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

<div class="modal fade" id="indicatorModal" tabindex="-1" role="dialog" aria-labelledby="indicatorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="indicatorModalLabel">Tambah Indicator</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="indicatorForm">
                @csrf
                <input type="hidden" id="indicator_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="indicator_category_id">Category</label>
                                <select class="form-control" id="indicator_category_id" name="category_id" required>
                                    <option value="">Pilih kategori</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" data-field="category_id"></div>
                            </div>
                            <div class="form-group">
                                <label for="indicator_name">Indicator Name</label>
                                <input type="text" class="form-control" id="indicator_name" name="indicator_name" required>
                                <div class="invalid-feedback" data-field="indicator_name"></div>
                            </div>
                            <div class="form-group">
                                <label for="indicator_notes">Notes</label>
                                <textarea class="form-control" id="indicator_notes" name="notes" rows="3"></textarea>
                                <div class="invalid-feedback" data-field="notes"></div>
                            </div>
                            <div class="form-group mb-0">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="indicator_is_active" name="is_active" checked>
                                    <label class="custom-control-label" for="indicator_is_active">Active</label>
                                </div>
                            </div>
                            <div class="alert alert-info mb-0">
                                Mapping indikator ke posisi sekarang dilakukan dari tabel <strong>Positions</strong> di bawah. Pilih posisi, lalu klik badge kategori untuk mengatur indikator dan bobotnya per posisi.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary" id="saveIndicatorBtn">Simpan</button>
                </div>
            </form>
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

    var categoryTable = $('#categoryTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('indicator.categories.data') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            {
                data: 'category_name',
                name: 'category_name',
                render: function(data, type, row) {
                    var typeLabel = row.evaluator_type_label || '';
                    var posName = row.evaluator_position_name || '';
                    var cls = 'badge-secondary';
                    if (/direct parent/i.test(typeLabel)) cls = 'badge-primary';
                    else if (/specific/i.test(typeLabel)) cls = 'badge-success';
                    else if (/bottom/i.test(typeLabel)) cls = 'badge-warning';

                    var badgeText = typeLabel + (posName ? ' : ' + posName : '');
                    var label = '<div class="mt-1"><small class="text-muted"><span class="' + cls + ' badge">' + badgeText + '</span></small></div>';
                    return (data || '-') + label;
                }
            },
            { data: 'weight_percentage', name: 'weight_percentage' },
            { data: 'indicators_count', name: 'indicators_count', searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
        ,
        createdRow: function(row, data, dataIndex) {
            // color inactive rows red
            try {
                if (data && (data.is_active === 0 || data.is_active === '0' || data.is_active === false)) {
                    $(row).addClass('table-danger');
                }
            } catch (e) {
                // noop
            }
        }
    });

    // refresh category total on table draw and on load
    categoryTable.on('draw', function() { refreshCategoryTotal(); });
    refreshCategoryTotal();

        var indicatorTable = $('#indicatorTable').DataTable({
        processing: true,
        serverSide: true,
            pageLength: 5,
            lengthMenu: [[5], [5]],
        ajax: {
            url: "{{ route('indicator.indicators.data') }}",
            data: function (d) { d.category_id = $('#indicatorCategoryFilter').val(); }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'category_name', name: 'category.category_name', orderable: false },
            { data: null, name: 'indicator_name', orderable: false, render: function (data, type, row) {
                var name = escapeHtml(row.indicator_name || '');
                var notes = row.notes ? '<div class="small text-muted mt-1">' + escapeHtml(row.notes) + '</div>' : '';
                return '<div>' + name + notes + '</div>';
            } },
            { data: 'position_mapped', name: 'position_mapped', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        createdRow: function (row, data) {
            try {
                if (data && (data.is_active === 0 || data.is_active === '0' || data.is_active === false)) {
                    $(row).addClass('table-danger');
                }
            } catch (e) {
                // noop
            }
        }
    });

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
            { data: 'category_percentages', name: 'category_percentages', orderable: false, searchable: false, render: function(data, type, row){ return data || ''; } },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
        ,
        createdRow: function(row, data) {
            try {
                if (data && data.has_issue && parseInt(data.has_issue) === 1) {
                    $(row).addClass('table-warning');
                }
            } catch (e) {}
        }
    });

    var recentlySavedIndicatorIds = [];
    var recentlySavedPositionId = null;
    var positionEditorCategoriesById = {};

    function buildInlineSaveStatus(indicatorId, positionId) {
        if (Number(positionId) === Number(recentlySavedPositionId) && $.inArray(Number(indicatorId), recentlySavedIndicatorIds) !== -1) {
            return '<div class="small text-success mt-2 inline-save-status"><i class="fas fa-check-circle"></i> Tersimpan</div>';
        }

        return '<div class="small text-muted mt-2 inline-save-status"><i class="far fa-circle"></i> Belum diubah</div>';
    }

    function setInlineRowStatus($row, state) {
        var $status = $row.find('.inline-save-status');
        if (!$status.length) {
            return;
        }

        if (state === 'dirty') {
            $status.removeClass('text-success text-muted').addClass('text-warning')
                .html('<i class="fas fa-pencil-alt"></i> Belum disimpan');
            return;
        }

        if (state === 'saved') {
            $status.removeClass('text-warning text-muted').addClass('text-success')
                .html('<i class="fas fa-check-circle"></i> Tersimpan');
            return;
        }

        $status.removeClass('text-success text-warning').addClass('text-muted')
            .html('<i class="far fa-circle"></i> Belum diubah');
    }

    function buildIndicatorRowHtml(indicator, rowNumber, positionId, options) {
        options = options || {};

        var isMapped = options.hasOwnProperty('is_mapped') ? !!options.is_mapped : !!indicator.is_mapped;
        var checked = isMapped ? 'checked' : '';
        var disabled = isMapped ? '' : 'disabled';
        var value = options.hasOwnProperty('weight_percentage')
            ? options.weight_percentage
            : (indicator.weight_percentage !== null && indicator.weight_percentage !== undefined ? Number(indicator.weight_percentage).toFixed(2) : '');
        var actionButton = isMapped
            ? '<button type="button" class="btn btn-sm btn-outline-warning btn-unmap-indicator">Lepas</button>'
            : '<button type="button" class="btn btn-sm btn-outline-secondary btn-unmap-indicator" disabled>Lepas</button>';
        var notesValue = escapeHtml(options.hasOwnProperty('notes') ? options.notes : (indicator.notes || ''));
        var nameValue = escapeHtml(options.hasOwnProperty('indicator_name') ? options.indicator_name : (indicator.indicator_name || ''));
        var originalMappedAttr = options.originally_mapped ? 'checked' : '';
        var originalWeight = options.hasOwnProperty('original_weight') ? options.original_weight : (value || '');
        var indicatorStatus = indicator.is_active ? '' : '<div class="small text-muted mt-1">Inactive indicator</div>';

        return '<tr>'
            + '<td>' + rowNumber + '</td>'
            + '<td class="text-center align-middle"><input type="checkbox" class="map-indicator-checkbox" ' + checked + ' ' + originalMappedAttr + '></td>'
            + '<td>'
                + '<input type="text" class="form-control form-control-sm inline-indicator-name mb-2" data-indicator-id="' + indicator.indicator_id + '" data-original-value="' + escapeHtml(indicator.indicator_name || '') + '" value="' + nameValue + '">'
                + '<textarea class="form-control form-control-sm inline-indicator-notes" data-indicator-id="' + indicator.indicator_id + '" data-original-value="' + escapeHtml(indicator.notes || '') + '" rows="2" placeholder="Catatan indikator (opsional)">' + notesValue + '</textarea>'
                + indicatorStatus
            + '</td>'
            + '<td><input type="number" min="0" max="100" step="0.01" class="form-control form-control-sm weight-input" data-indicator-id="' + indicator.indicator_id + '" data-original-value="' + escapeHtml(String(originalWeight)) + '" value="' + escapeHtml(String(value || '')) + '" ' + disabled + '></td>'
            + '<td class="text-center align-middle">' + actionButton + buildInlineSaveStatus(indicator.indicator_id, positionId) + '</td>'
            + '</tr>';
    }

    function renumberPositionSectionRows($section) {
        $section.find('tbody tr').not('.category-total-row, .empty-category-row').each(function (index) {
            $(this).find('td:first').text(index + 1);
        });
    }

    function getAvailableIndicatorsForSection($section, keyword) {
        var categoryId = String($section.data('category-id') || '');
        var category = positionEditorCategoriesById[categoryId];
        var lowerKeyword = (keyword || '').trim().toLowerCase();
        var renderedIds = {};

        $section.find('tbody tr').not('.category-total-row, .empty-category-row').each(function () {
            var id = $(this).find('.weight-input').data('indicator-id');
            if (id) {
                renderedIds[String(id)] = true;
            }
        });

        if (!category || !category.indicators) {
            return [];
        }

        return $.grep(category.indicators, function (indicator) {
            var indicatorId = String(indicator.indicator_id || '');
            if (!indicatorId || renderedIds[indicatorId]) {
                return false;
            }

            if (!lowerKeyword) {
                return true;
            }

            var haystack = ((indicator.indicator_name || '') + ' ' + (indicator.notes || '')).toLowerCase();
            return haystack.indexOf(lowerKeyword) !== -1;
        });
    }

    function renderExistingIndicatorSearchResults($section, keyword) {
        var $results = $section.find('.existing-indicator-results');
        var term = (keyword || '').trim();

        if (!term) {
            $results.addClass('d-none').empty();
            return;
        }

        var matches = getAvailableIndicatorsForSection($section, term).slice(0, 10);

        if (!matches.length) {
            $results.removeClass('d-none').html('<div class="list-group-item small text-muted">Tidak ada indikator existing yang cocok.</div>');
            return;
        }

        var html = '';
        $.each(matches, function (_, indicator) {
            html += '<button type="button" class="list-group-item list-group-item-action btn-pick-existing-indicator" data-indicator-id="' + indicator.indicator_id + '">'
                + '<div class="font-weight-bold">' + escapeHtml(indicator.indicator_name || '') + '</div>'
                + (indicator.notes ? '<div class="small text-muted mt-1">' + escapeHtml(indicator.notes) + '</div>' : '')
                + '</button>';
        });

        $results.removeClass('d-none').html(html);
    }

    function addExistingIndicatorToSection($section, indicatorId) {
        var categoryId = String($section.data('category-id') || '');
        var category = positionEditorCategoriesById[categoryId];
        var positionId = $('#positionCategoryModal').data('pos-id') || null;

        if (!category || !category.indicators) {
            return;
        }

        var indicator = null;
        $.each(category.indicators, function (_, item) {
            if (String(item.indicator_id) === String(indicatorId)) {
                indicator = item;
                return false;
            }
        });

        if (!indicator) {
            return;
        }

        $section.find('.empty-category-row').remove();

        var rowCount = $section.find('tbody tr').not('.category-total-row, .empty-category-row').length + 1;
        var rowHtml = buildIndicatorRowHtml(indicator, rowCount, positionId, {
            is_mapped: true,
            originally_mapped: false,
            original_weight: '',
            weight_percentage: '',
            indicator_name: indicator.indicator_name || '',
            notes: indicator.notes || ''
        });

        var $row = $(rowHtml);
        $section.find('.category-total-row').before($row);
        syncInlineRowStatus($row);
        renumberPositionSectionRows($section);
        recalcPositionCategorySection($section);
        recalcAllPositionCategorySections();

        $section.find('.existing-indicator-search').val('');
        $section.find('.existing-indicator-results').addClass('d-none').empty();
    }

    function syncInlineRowStatus($row) {
        var $input = $row.find('.weight-input');
        var indicatorId = $input.data('indicator-id');
        if (!indicatorId) {
            return;
        }

        var isMapped = $row.find('.map-indicator-checkbox').is(':checked');
        var value = parseFloat($input.val());
        var indicatorName = ($row.find('.inline-indicator-name').val() || '').trim();
        var indicatorNotes = ($row.find('.inline-indicator-notes').val() || '').trim();
        var originalName = ($row.find('.inline-indicator-name').data('original-value') || '').toString().trim();
        var originalNotes = ($row.find('.inline-indicator-notes').data('original-value') || '').toString().trim();
        var originalWeight = ($input.data('original-value') || '').toString().trim();
        var currentWeight = isMapped && !isNaN(value) ? String(value) : '';
        var originallyMapped = $row.find('.map-indicator-checkbox').attr('checked') ? '1' : '0';
        var currentMapped = isMapped ? '1' : '0';

        if (indicatorName !== originalName || indicatorNotes !== originalNotes || currentWeight !== originalWeight || currentMapped !== originallyMapped) {
            setInlineRowStatus($row, 'dirty');
            return;
        }

        setInlineRowStatus($row, 'default');
    }

    function capturePositionEditorDraft() {
        var draft = { categories: {} };

        $('#positionCategoryModalBody .position-category-section').each(function () {
            var $section = $(this);
            var categoryId = String($section.data('category-id') || '');
            if (!categoryId) {
                return;
            }

            var sectionDraft = {
                newIndicatorName: $section.find('.new-indicator-name').val() || '',
                indicators: {}
            };

            $section.find('tbody tr').not('.category-total-row').each(function () {
                var $row = $(this);
                var indicatorId = $row.find('.weight-input').data('indicator-id');
                if (!indicatorId) {
                    return;
                }

                sectionDraft.indicators[String(indicatorId)] = {
                    indicator_name: $row.find('.inline-indicator-name').val() || '',
                    notes: $row.find('.inline-indicator-notes').val() || '',
                    is_mapped: $row.find('.map-indicator-checkbox').is(':checked'),
                    weight_percentage: $row.find('.weight-input').val() || ''
                };
            });

            draft.categories[categoryId] = sectionDraft;
        });

        return draft;
    }

    function applyPositionEditorDraft(draft) {
        if (!draft || !draft.categories) {
            return;
        }

        $('#positionCategoryModalBody .position-category-section').each(function () {
            var $section = $(this);
            var categoryId = String($section.data('category-id') || '');
            var sectionDraft = draft.categories[categoryId];
            if (!sectionDraft) {
                return;
            }

            $section.find('.new-indicator-name').val(sectionDraft.newIndicatorName || '');

            $section.find('tbody tr').not('.category-total-row').each(function () {
                var $row = $(this);
                var indicatorId = String($row.find('.weight-input').data('indicator-id') || '');
                var indicatorDraft = sectionDraft.indicators[indicatorId];
                if (!indicatorDraft) {
                    return;
                }

                $row.find('.inline-indicator-name').val(indicatorDraft.indicator_name || '');
                $row.find('.inline-indicator-notes').val(indicatorDraft.notes || '');
                $row.find('.map-indicator-checkbox').prop('checked', !!indicatorDraft.is_mapped);
                $row.find('.weight-input')
                    .val(indicatorDraft.weight_percentage || '')
                    .prop('disabled', !indicatorDraft.is_mapped);
                $row.find('.btn-unmap-indicator')
                    .prop('disabled', !indicatorDraft.is_mapped)
                    .toggleClass('btn-outline-warning', !!indicatorDraft.is_mapped)
                    .toggleClass('btn-outline-secondary', !indicatorDraft.is_mapped);

                syncInlineRowStatus($row);
            });
        });

        recalcAllPositionCategorySections();
    }

    function renderPositionCategorySection(category) {
        var rows = '';
        var positionId = $('#positionCategoryModal').data('pos-id') || null;
        var mappedIndicators = $.grep(category.indicators || [], function (indicator) {
            return !!indicator.is_mapped;
        });

        if (!mappedIndicators.length) {
            rows = '<tr><td colspan="5" class="text-muted text-center">Belum ada indikator yang dimapping untuk kategori ini.</td></tr>';
        } else {
            $.each(mappedIndicators, function (index, indicator) {
                rows += buildIndicatorRowHtml(indicator, index + 1, positionId, {
                    is_mapped: true,
                    originally_mapped: true
                });
            });
        }

        rows += '<tr class="font-weight-bold category-total-row">'
            + '<td></td>'
            + '<td></td>'
            + '<td class="mapped-count-cell">Mapped: 0</td>'
            + '<td class="text-right total-weight-cell">Total Weighted: 0.00%</td>'
            + '<td></td>'
            + '</tr>';

        return '<div class="card mb-3 position-category-section" data-category-id="' + category.category_id + '">'
            + '<div class="card-header d-flex justify-content-between align-items-center">'
            + '<div><strong>' + escapeHtml(category.category_name) + '</strong><div class="small text-muted">Category Weight: ' + Number(category.category_weight_percentage || 0).toFixed(2) + '%</div></div>'
            + '<span class="badge badge-light text-uppercase">' + escapeHtml((category.evaluator_type || '').replace(/_/g, ' ')) + '</span>'
            + '</div>'
            + '<div class="card-body">'
            + '<div class="form-row align-items-end mb-3">'
            + '<div class="col-md-8 mb-2 mb-md-0"><label class="small text-muted">Tambah indikator baru</label><input type="text" class="form-control form-control-sm new-indicator-name" placeholder="Nama indikator baru untuk kategori ini"></div>'
            + '<div class="col-md-4"><button type="button" class="btn btn-sm btn-outline-primary btn-add-inline-indicator" data-category-id="' + category.category_id + '">Tambah Indikator</button></div>'
            + '</div>'
            + '<div class="form-row mb-3">'
            + '<div class="col-md-12 position-relative">'
            + '<label class="small text-muted">Cari indikator existing</label>'
            + '<input type="text" class="form-control form-control-sm existing-indicator-search" placeholder="Cari indikator yang sudah ada di kategori ini">'
            + '<div class="list-group existing-indicator-results d-none position-absolute w-100" style="z-index: 5; max-height: 220px; overflow-y: auto;"></div>'
            + '</div>'
            + '</div>'
            + '<div class="table-responsive"><table class="table table-sm table-bordered mb-0"><thead><tr><th style="width:60px">No</th><th style="width:70px">Map</th><th>Indicator</th><th style="width:170px">Weight %</th><th style="width:110px">Aksi</th></tr></thead><tbody>' + rows + '</tbody></table></div>'
            + '</div>'
            + '</div>';
    }

    function recalcPositionCategorySection($section) {
        var total = 0;
        var count = 0;

        $section.find('tbody tr').not('.category-total-row').each(function () {
            var $row = $(this);
            var isMapped = $row.find('.map-indicator-checkbox').is(':checked');
            var $input = $row.find('.weight-input');
            if (!isMapped) {
                return;
            }

            var value = parseFloat($input.val());
            if (!isNaN(value)) {
                total += value;
            }
            count++;
        });

        var $totalRow = $section.find('.category-total-row');
        $totalRow.find('.mapped-count-cell').text('Mapped: ' + count);
        $totalRow.find('.total-weight-cell').text('Total Weighted: ' + Number(total).toFixed(2) + '%');
        $totalRow.removeClass('table-success table-danger');
        $totalRow.addClass(count === 0 || Math.abs(total - 100.0) <= 0.001 ? 'table-success' : 'table-danger');
    }

    function recalcAllPositionCategorySections() {
        var hasInvalid = false;

        $('#positionCategoryModalBody .position-category-section').each(function () {
            recalcPositionCategorySection($(this));
            if ($(this).find('.category-total-row').hasClass('table-danger')) {
                hasInvalid = true;
            }
        });

        $('#positionCategorySaveBtn').prop('disabled', hasInvalid);
    }

    function renderPositionEditorModal(payload, draft) {
        var position = payload.position || {};
        var categories = payload.categories || [];
        var html = '';

        positionEditorCategoriesById = {};
        $.each(categories, function (_, category) {
            positionEditorCategoriesById[String(category.category_id)] = category;
        });

        $('#positionCategoryModal').data('pos-id', position.id || null);
        $('#positionCategoryModal').data('pos-name', position.name || '');
        $('#positionCategoryModalLabel').text('Edit Indicators: ' + (position.name || '-'));
        $('#positionCategoryModalSummary')
            .toggleClass('d-none', false)
            .html('<strong>Posisi:</strong> ' + escapeHtml(position.name || '-') + '<br><strong>Divisi:</strong> ' + escapeHtml(position.division_names || '-'));

        if (!categories.length) {
            html = '<div class="alert alert-warning mb-0">Belum ada kategori aktif yang bisa dipetakan.</div>';
        } else {
            $.each(categories, function (_, category) {
                html += renderPositionCategorySection(category);
            });
        }

        $('#positionCategoryModalBody').html(html);
        applyPositionEditorDraft(draft);
        recalcAllPositionCategorySections();
        $('#positionCategoryModal').modal('show');
    }

    function loadPositionEditorModal(positionId, draft) {
        $.get('/indicator/positions/' + positionId + '/editor', function (response) {
            renderPositionEditorModal((response && response.data) ? response.data : {}, draft);
        }).fail(function () {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal memuat editor indikator posisi.' });
        });
    }

    $(document).on('click', '.btn-edit-position-mapping', function () {
        var positionId = $(this).data('id');
        if (!positionId) {
            return;
        }

        loadPositionEditorModal(positionId);
    });

    $(document).on('click', '.category-badge', function () {
        var positionId = $(this).data('pos-id') || $(this).attr('data-pos-id');
        if (!positionId) {
            return;
        }

        loadPositionEditorModal(positionId);
    });

    $(document).on('input', '#positionCategoryModalBody .weight-input', function () {
        setInlineRowStatus($(this).closest('tr'), 'dirty');
        recalcPositionCategorySection($(this).closest('.position-category-section'));
        recalcAllPositionCategorySections();
    });

    $(document).on('input', '#positionCategoryModalBody .inline-indicator-name, #positionCategoryModalBody .inline-indicator-notes', function () {
        setInlineRowStatus($(this).closest('tr'), 'dirty');
    });

    $(document).on('input', '#positionCategoryModalBody .existing-indicator-search', function () {
        renderExistingIndicatorSearchResults($(this).closest('.position-category-section'), $(this).val());
    });

    $(document).on('focus', '#positionCategoryModalBody .existing-indicator-search', function () {
        renderExistingIndicatorSearchResults($(this).closest('.position-category-section'), $(this).val());
    });

    $(document).on('click', '#positionCategoryModalBody .btn-pick-existing-indicator', function () {
        addExistingIndicatorToSection($(this).closest('.position-category-section'), $(this).data('indicator-id'));
    });

    $(document).on('click', function (event) {
        if (!$(event.target).closest('.position-category-section .position-relative').length) {
            $('#positionCategoryModalBody .existing-indicator-results').addClass('d-none');
        }
    });

    $(document).on('change', '#positionCategoryModalBody .map-indicator-checkbox', function () {
        var $row = $(this).closest('tr');
        var $input = $row.find('.weight-input');
        var $button = $row.find('.btn-unmap-indicator');
        var checked = $(this).is(':checked');
        $input.prop('disabled', !checked);
        $button.prop('disabled', !checked)
            .toggleClass('btn-outline-warning', checked)
            .toggleClass('btn-outline-secondary', !checked);
        if (!checked) {
            $input.val('');
        }
        setInlineRowStatus($row, 'dirty');
        recalcPositionCategorySection($(this).closest('.position-category-section'));
        recalcAllPositionCategorySections();
    });

    $(document).on('click', '#positionCategoryModalBody .btn-unmap-indicator', function () {
        var $row = $(this).closest('tr');
        $row.find('.map-indicator-checkbox').prop('checked', false).trigger('change');
    });

    $(document).on('click', '.btn-add-inline-indicator', function () {
        var $section = $(this).closest('.position-category-section');
        var categoryId = $(this).data('category-id');
        var positionId = $('#positionCategoryModal').data('pos-id');
        var indicatorName = ($section.find('.new-indicator-name').val() || '').trim();
        var $button = $(this);
        var draftState = capturePositionEditorDraft();

        if (!categoryId || !positionId) {
            return;
        }

        if (!indicatorName) {
            Swal.fire({ icon: 'warning', title: 'Validation', text: 'Nama indikator baru wajib diisi.' });
            return;
        }

        if (draftState.categories && draftState.categories[String(categoryId)]) {
            draftState.categories[String(categoryId)].newIndicatorName = '';
        }

        $button.prop('disabled', true).text('Menambahkan...');
        $.ajax({
            url: "{{ route('indicator.indicators.store') }}",
            type: 'POST',
            data: {
                category_id: categoryId,
                indicator_name: indicatorName,
                notes: '',
                is_active: 1,
                position_mappings: [{
                    position_id: positionId,
                    weight_percentage: null
                }]
            },
            success: function () {
                loadPositionEditorModal(positionId, draftState);
                Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Indikator baru ditambahkan dan langsung dimapping. Isi bobotnya lalu simpan.' });
            },
            error: function (xhr) {
                showAjaxError(xhr, 'Gagal menambahkan indikator baru.');
            },
            complete: function () {
                $button.prop('disabled', false).text('Tambah Indikator');
            }
        });
    });

    // when filters change, reload DataTables
    $('#positionsDivisionFilter').on('change', function () { positionsTable.ajax.reload(); });
    $('#indicatorCategoryFilter').on('change', function () { indicatorTable.ajax.reload(); });

    // save updated weights
    $(document).on('click', '#positionCategorySaveBtn', function () {
        var $btn = $(this);
        var $modal = $('#positionCategoryModal');
        var posId = $modal.data('pos-id');
        if (!posId) return;
        var categories = [];
        var changedIndicatorIds = [];

        $('#positionCategoryModalBody .position-category-section').each(function () {
            var $section = $(this);
            var categoryId = $section.data('category-id');
            var mappings = [];
            var indicators = [];

            $section.find('tbody tr').not('.category-total-row').each(function () {
                var $row = $(this);
                var $input = $row.find('.weight-input');
                var indicatorId = $input.data('indicator-id');
                if (!indicatorId) {
                    return;
                }
                var isMapped = $row.find('.map-indicator-checkbox').is(':checked');
                var value = parseFloat($input.val());
                var indicatorName = ($row.find('.inline-indicator-name').val() || '').trim();
                var indicatorNotes = ($row.find('.inline-indicator-notes').val() || '').trim();
                var originalName = ($row.find('.inline-indicator-name').data('original-value') || '').toString().trim();
                var originalNotes = ($row.find('.inline-indicator-notes').data('original-value') || '').toString().trim();
                var originalWeight = ($input.data('original-value') || '').toString().trim();
                var currentWeight = isMapped && !isNaN(value) ? String(value) : '';
                var originallyMapped = $row.find('.map-indicator-checkbox').attr('checked') ? '1' : '0';
                var currentMapped = isMapped ? '1' : '0';

                if (indicatorName !== originalName || indicatorNotes !== originalNotes || currentWeight !== originalWeight || currentMapped !== originallyMapped) {
                    changedIndicatorIds.push(Number(indicatorId));
                }

                indicators.push({
                    indicator_id: indicatorId,
                    indicator_name: indicatorName,
                    notes: indicatorNotes,
                    category_id: categoryId
                });

                mappings.push({
                    indicator_id: indicatorId,
                    is_mapped: isMapped ? 1 : 0,
                    weight_percentage: isMapped && !isNaN(value) ? value : null
                });
            });

            categories.push({
                category_id: categoryId,
                indicators: indicators,
                mappings: mappings
            });
        });

        $btn.prop('disabled', true).text('Saving...');
        var indicatorRequests = [];

        $.each(categories, function (_, categoryPayload) {
            $.each(categoryPayload.indicators || [], function (_, indicatorPayload) {
                indicatorRequests.push($.ajax({
                    url: '/indicator/indicators/' + indicatorPayload.indicator_id,
                    method: 'POST',
                    data: {
                        _method: 'PUT',
                        category_id: indicatorPayload.category_id,
                        indicator_name: indicatorPayload.indicator_name,
                        notes: indicatorPayload.notes,
                        is_active: 1
                    },
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
                }));
            });
        });

        $.when.apply($, indicatorRequests)
            .done(function () {
                $.ajax({
                    url: '/indicator/positions/' + posId + '/mappings/bulk',
                    method: 'POST',
                    data: { categories: categories },
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (res) {
                        recentlySavedIndicatorIds = changedIndicatorIds;
                        recentlySavedPositionId = posId;
                        positionsTable.ajax.reload(null, false);
                        indicatorTable.ajax.reload(null, false);
                        loadPositionEditorModal(posId);
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message || 'Mapping indikator posisi berhasil disimpan.' });
                    },
                    error: function (xhr) {
                        showAjaxError(xhr, 'Gagal menyimpan mapping indikator posisi.');
                    },
                    complete: function () {
                        $btn.prop('disabled', false).text('Save');
                    }
                });
            })
            .fail(function (xhr) {
                $btn.prop('disabled', false).text('Save');
                showAjaxError(xhr, 'Gagal menyimpan perubahan indikator.');
            });
    });

    function clearFormErrors(formSelector) {
        $(formSelector).find('.is-invalid').removeClass('is-invalid');
        $(formSelector).find('.invalid-feedback').text('');
    }

    function applyValidationErrors(formSelector, errors) {
        clearFormErrors(formSelector);
        $.each(errors || {}, function (field, messages) {
            var input = $(formSelector).find('[name="' + field + '"]');
            input.addClass('is-invalid');
            $(formSelector).find('.invalid-feedback[data-field="' + field + '"]').text(messages[0]);
        });
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function toggleEvaluatorPosition() {
        var isSpecific = $('#category_evaluator_type').val() === 'specific_position';
        $('#evaluatorPositionGroup').toggleClass('d-none', !isSpecific);
        if (!isSpecific) {
            $('#category_evaluator_position_id').val('').trigger('change');
        }
    }

    // reload all KPI-related DataTables
    function refreshAllTables() {
        try { categoryTable.ajax.reload(null, false); } catch (e) {}
        try { indicatorTable.ajax.reload(null, false); } catch (e) {}
        try { positionsTable.ajax.reload(null, false); } catch (e) {}
    }

    function resetCategoryForm() {
        $('#categoryForm')[0].reset();
        $('#category_id').val('');
        $('#category_is_active').prop('checked', true);
        $('#categoryModalLabel').text('Tambah Category');
        clearFormErrors('#categoryForm');
        toggleEvaluatorPosition();
        // reset select2 for evaluator position
        $('#category_evaluator_position_id').val('').trigger('change');
    }

    function resetIndicatorForm() {
        $('#indicatorForm')[0].reset();
        $('#indicator_id').val('');
        $('#indicator_is_active').prop('checked', true);
        $('#indicatorModalLabel').text('Tambah Indicator');
        clearFormErrors('#indicatorForm');
    }

    function showAjaxError(xhr, fallbackMessage) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
            return;
        }

        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : fallbackMessage
        });
    }

    function refreshMeta(selectedCategoryId) {
        $.get("{{ route('indicator.meta') }}", function (response) {
            var categorySelect = $('#indicator_category_id');
            categorySelect.empty().append('<option value="">Pilih kategori</option>');

            $.each(response.categories || [], function (_, category) {
                categorySelect.append('<option value="' + category.id + '">' + category.category_name + '</option>');
            });

            if (selectedCategoryId) {
                categorySelect.val(String(selectedCategoryId));
            }
            var evalPosSelect = $('#category_evaluator_position_id');
            evalPosSelect.empty().append('<option value="">Pilih posisi</option>');
            $.each(response.positions || [], function (_, pos) {
                evalPosSelect.append('<option value="' + pos.id + '">' + pos.name + '</option>');
            });
            evalPosSelect.trigger('change');
        });
    }

    function refreshCategoryTotal() {
        $.get("{{ url('/indicator/categories/total') }}", function(res) {
            if (!res || typeof res.total === 'undefined') return;
            var val = Number(res.total).toFixed(2);
            var $el = $('#categoryTotalValue');
            $el.text(val);
            if (Math.abs(Number(val) - 100) > 0.001) {
                $el.closest('#categoryTotal').addClass('text-danger').removeClass('text-success');
            } else {
                $el.closest('#categoryTotal').addClass('text-success').removeClass('text-danger');
            }
        }).fail(function() {
            // noop
        });
    }

    $('#category_evaluator_type').on('change', toggleEvaluatorPosition);

    // initialize select2 for position selects if Select2 available
    if ($.isFunction($.fn.select2)) {
        // attach dropdown to modals to avoid clipping/overflow issues
        $('#category_evaluator_position_id').select2({ width: '100%', dropdownParent: $('#categoryModal') });
    }

    $('#btnAddCategory').on('click', function () {
        // ensure other modals are closed first to avoid backdrop stacking
        $('#indicatorModal, #importModal').modal('hide');
        resetCategoryForm();
        $('#categoryModal').modal('show');
    });

    $('#btnAddIndicator').on('click', function () {
        // ensure other modals are closed first to avoid backdrop stacking
        $('#categoryModal, #importModal').modal('hide');
        resetIndicatorForm();
        $('#indicatorModal').modal('show');
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
                refreshAllTables();
                refreshMeta();
                Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message });
            },
            error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON) {
                        console.warn('Validation failed', xhr.responseJSON);
                        if (xhr.responseJSON.errors) {
                            applyValidationErrors('#categoryForm', xhr.responseJSON.errors);
                            // show aggregated first message
                            var msgs = [];
                            Object.keys(xhr.responseJSON.errors).forEach(function(k){ msgs = msgs.concat(xhr.responseJSON.errors[k]); });
                            Swal.fire({ icon: 'warning', title: 'Validation', text: msgs[0] || 'Validation failed' });
                            return;
                        }
                    }

                    showAjaxError(xhr, 'Gagal menyimpan category.');
            }
        });
    });

    $('#indicatorForm').on('submit', function (e) {
        e.preventDefault();

        var indicatorId = $('#indicator_id').val();
        var isEdit = !!indicatorId;
        var url = isEdit
            ? "{{ url('/indicator/indicators') }}/" + indicatorId
            : "{{ route('indicator.indicators.store') }}";
        var payload = $(this).serialize() + (isEdit ? '&_method=PUT' : '');

        $.ajax({
            url: url,
            type: 'POST',
            data: payload,
            success: function (response) {
                $('#indicatorModal').modal('hide');
                refreshAllTables();
                Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message });
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    applyValidationErrors('#indicatorForm', xhr.responseJSON.errors);
                    return;
                }

                showAjaxError(xhr, 'Gagal menyimpan indicator.');
            }
        });
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
            $('#category_evaluator_position_id').val(data.evaluator_position_id).trigger('change');
            $('#category_is_active').prop('checked', !!data.is_active);
            $('#categoryModalLabel').text('Edit Category');
            toggleEvaluatorPosition();
            $('#categoryModal').modal('show');
        });
    });

    $(document).on('click', '.btn-edit-indicator', function () {
        var id = $(this).data('id');
        resetIndicatorForm();

        $.get("{{ url('/indicator/indicators') }}/" + id, function (response) {
            var data = response.data;
            $('#indicator_id').val(data.id);
            $('#indicator_category_id').val(String(data.category_id));
            $('#indicator_name').val(data.indicator_name);
            $('#indicator_notes').val(data.notes || '');
            $('#indicator_is_active').prop('checked', !!data.is_active);
            $('#indicatorModalLabel').text('Edit Indicator');
            $('#indicatorModal').modal('show');
        });
    });

    // Import buttons
    $('#btnImportCategory').on('click', function() {
        $('#import_type').val('categories');
        $('#importModalLabel').text('Import Categories');
        $('#importPreviewArea').addClass('d-none');
        $('#btnImportCommit').addClass('d-none');
        $('#btnImportPreview').removeClass('d-none');
        $('#import_file').val('');
        // hide other modals first
        $('#categoryModal, #indicatorModal').modal('hide');
        // show column hints for categories (render as badges). evaluator position must be ID
        $('#import_hint_columns').html(
            ['category_name','weight_percentage','evaluator_type']
            .map(function(c){ return '<span class="badge badge-secondary mr-1">'+c+'</span>'; }).join('')
            + '<div class="mt-2 small">Optional: <span class="badge badge-light">evaluator_position_id (ID)</span>, <span class="badge badge-light">is_active</span></div>'
        );

        // dynamic area: show evaluator types and positions list
        var dyn = '<div class="font-weight-bold">Evaluator types:</div>'
            + '<div class="mb-1"><span class="badge badge-light">direct_parent</span> <span class="badge badge-light">specific_position</span> <span class="badge badge-light">bottom_up</span></div>'
            + '<div class="font-weight-bold">Available positions (ID : Name):</div>'
            + '<div style="max-height:120px; overflow:auto;"><ul class="list-unstyled mb-0 small">';
        @foreach($positions as $pos)
            dyn += '<li><strong>{{ $pos->id }}</strong> &nbsp;&ndash;&nbsp; {{ addslashes($pos->name) }}</li>';
        @endforeach
        dyn += '</ul></div>';
        $('#import_hint_dynamic').html(dyn);
        $('#importModal').modal('show');
    });

    $('#btnImportIndicator').on('click', function() {
        $('#import_type').val('indicators');
        $('#importModalLabel').text('Import Indicators');
        $('#importPreviewArea').addClass('d-none');
        $('#btnImportCommit').addClass('d-none');
        $('#btnImportPreview').removeClass('d-none');
        $('#import_file').val('');
        // hide other modals first
        $('#categoryModal, #indicatorModal').modal('hide');
        // show column hints for indicators (render as badges). category/position must be IDs
        $('#import_hint_columns').html(
            ['category_id','indicator_name']
            .map(function(c){ return '<span class="badge badge-secondary mr-1">'+c+'</span>'; }).join('')
            + '<div class="mt-2 small">Optional: <span class="badge badge-light">notes</span>, <span class="badge badge-light">position_id (ID)</span>, <span class="badge badge-light">weight_percentage</span>, <span class="badge badge-light">is_active</span></div>'
        );

        // dynamic area: show available categories and positions; evaluator types not needed here
        var dyn2 = '<div class="font-weight-bold">Available categories (ID : Name):</div>'
            + '<div style="max-height:120px; overflow:auto;"><ul class="list-unstyled mb-0 small">';
        @foreach($categories as $cat)
            dyn2 += '<li><strong>{{ $cat->id }}</strong> &nbsp;&ndash;&nbsp; {{ addslashes($cat->category_name) }}</li>';
        @endforeach
        dyn2 += '</ul></div>';
        dyn2 += '<div class="font-weight-bold mt-2">Available positions (ID : Name):</div>'
            + '<div style="max-height:120px; overflow:auto;"><ul class="list-unstyled mb-0 small">';
        @foreach($positions as $pos)
            dyn2 += '<li><strong>{{ $pos->id }}</strong> &nbsp;&ndash;&nbsp; {{ addslashes($pos->name) }}</li>';
        @endforeach
        dyn2 += '</ul></div>';
        $('#import_hint_dynamic').html(dyn2);
        $('#importModal').modal('show');
    });

    $('#btnImportPreview').on('click', function() {
        var file = $('#import_file')[0].files[0];
        if (!file) {
            Swal.fire({icon:'warning', title:'File required', text:'Please select a CSV or XLSX file.'});
            return;
        }
        var type = $('#import_type').val();
        var fd = new FormData();
        fd.append('file', file);
        fd.append('type', type);

        // show loading state on preview button
        var $btn = $('#btnImportPreview');
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...');

        $.ajax({
            url: '{{ url("/indicator/import/preview") }}',
            data: fd,
            type: 'POST',
            processData: false,
            contentType: false,
            success: function(res) {
                $btn.prop('disabled', false).html(originalHtml);
                if (!res || !res.data) return;
                var data = res.data;
                var $thead = $('#importPreviewHeader').empty();
                var $tbody = $('#importPreviewTable tbody').empty();

                // build header: #, then columns from first row raw keys, then Errors
                var firstRaw = (data[0] && data[0].raw) ? data[0].raw : null;
                $thead.append('<th>#</th>');
                var keys = [];
                if (firstRaw) {
                    for (var k in firstRaw) {
                        if (Object.prototype.hasOwnProperty.call(firstRaw, k)) {
                            keys.push(k);
                            $thead.append('<th>' + k.replace(/_/g, ' ') + '</th>');
                        }
                    }
                } else {
                    $thead.append('<th>Data</th>');
                }
                $thead.append('<th>Errors</th>');

                function escapeHtml(text) {
                    if (text === null || text === undefined) return '';
                    return String(text)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

                // populate rows
                $.each(data, function(_, item) {
                    var errors = item.errors || [];
                    var trClass = (errors.length === 0) ? 'table-success' : 'table-danger';
                    var $tr = $('<tr>').addClass(trClass);
                    $tr.append('<td>' + item.row_number + '</td>');

                    if (keys.length) {
                        for (var i = 0; i < keys.length; i++) {
                            var key = keys[i];
                            var val = item.raw && (key in item.raw) ? item.raw[key] : '';
                            $tr.append('<td><pre style="white-space:pre-wrap; margin:0;">' + escapeHtml(val) + '</pre></td>');
                        }
                    } else {
                        $tr.append('<td><pre style="white-space:pre-wrap; margin:0;">' + escapeHtml(JSON.stringify(item.raw)) + '</pre></td>');
                    }

                    $tr.append('<td>' + (errors.length ? escapeHtml(errors.join('; ')) : '-') + '</td>');
                    $tbody.append($tr);
                });

                $('#importPreviewArea').removeClass('d-none');
                $('#btnImportCommit').removeClass('d-none');
                $('#btnImportPreview').addClass('d-none');
                // store preview payload for commit
                $('#importModal').data('preview', res.data);
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(originalHtml);
                showAjaxError(xhr, 'Failed to parse file for preview.');
            }
        });
    });

    $('#btnImportCommit').on('click', function() {
        var preview = $('#importModal').data('preview');
        if (!preview) return;
        var type = $('#import_type').val();
        $.ajax({
            url: '{{ url("/indicator/import/commit") }}',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ type: type, rows: preview }),
            success: function(res) {
                if (res.success) {
                    $('#importModal').modal('hide');
                    refreshAllTables();
                    Swal.fire({icon:'success', title:'Imported', text: res.created + ' rows created'});
                } else {
                    Swal.fire({icon:'warning', title:'Import completed with errors', text: 'Created: '+ (res.created||0)});
                }
            },
            error: function(xhr) {
                showAjaxError(xhr, 'Import failed.');
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
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: "{{ url('/indicator/categories') }}/" + id,
                type: 'POST',
                data: { _method: 'DELETE' },
                success: function (response) {
                    refreshAllTables();
                    refreshMeta();
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message });
                },
                error: function (xhr) {
                    showAjaxError(xhr, 'Gagal menghapus category.');
                }
            });
        });
    });

    $(document).on('click', '.btn-delete-indicator', function () {
        var id = $(this).data('id');
        var name = $(this).data('name');

        Swal.fire({
            icon: 'warning',
            title: 'Hapus indicator?',
            text: 'Indicator "' + name + '" akan dihapus.',
            showCancelButton: true,
            confirmButtonText: 'Hapus',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: "{{ url('/indicator/indicators') }}/" + id,
                type: 'POST',
                data: { _method: 'DELETE' },
                success: function (response) {
                    refreshAllTables();
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message });
                },
                error: function (xhr) {
                    showAjaxError(xhr, 'Gagal menghapus indicator.');
                }
            });
        });
    });
});
</script>
@endsection
