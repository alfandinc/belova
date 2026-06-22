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

    <!-- Modal: show indicators for position+category -->
    <div class="modal fade" id="positionCategoryModal" tabindex="-1" role="dialog" aria-labelledby="positionCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="positionCategoryModalLabel">Indicators</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div id="positionCategoryModalBody">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" id="positionCategoryModalTable">
                                <thead><tr><th>No</th><th>Indicator</th><th style="width:120px">Weight %</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="positionCategorySaveBtn" disabled>Save</button>
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
                        <div class="col-md-7">
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
                            <div class="form-group">
                                <label for="indicator_position_ids">Position(s) (optional)</label>
                                <select class="form-control select2" id="indicator_position_ids" name="position_ids[]" multiple>
                                    @foreach($positions as $position)
                                        <option value="{{ $position->id }}" data-parent="{{ $position->parent_id }}">{{ $position->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" data-field="position_ids"></div>
                                <div class="form-check form-check-inline align-middle ml-2">
                                    <input class="form-check-input" type="checkbox" id="indicator_map_all_right">
                                    <label class="form-check-label small" for="indicator_map_all_right">Map to all positions</label>
                                </div>
                            </div>

                            <input type="hidden" id="position_mappings" name="position_mappings">
                            <div class="form-group mb-0">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="indicator_is_active" name="is_active" checked>
                                    <label class="custom-control-label" for="indicator_is_active">Active</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-5 border-left">
                            <h6 class="mb-2">Position Mappings</h6>
                            <div class="text-muted small mb-2">Set weights for each selected position. These mappings will be created/updated when saving the indicator.</div>
                            <div class="form-inline mb-2">
                                <input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm mr-2" id="indicator_apply_all_weight_right" placeholder="Weight for all">
                                <button type="button" class="btn btn-sm btn-secondary" id="indicator_apply_all_btn_right">Apply to all</button>
                                
                            </div>
                            <div class="table-responsive" style="max-height:320px; overflow:auto">
                                <table class="table table-sm table-bordered" id="indicatorPositionMappingsRight">
                                    <thead><tr><th>Position</th><th style="width:150px">Weight %</th></tr></thead>
                                    <tbody></tbody>
                                </table>
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
            { data: 'category_percentages', name: 'category_percentages', orderable: false, searchable: false, render: function(data, type, row){ return data || ''; } }
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

    // click handler for category badges in positions table
    $(document).on('click', '.category-badge', function () {
        var $badge = $(this);
        var posId = $badge.data('pos-id') || $badge.attr('data-pos-id');
        var catId = $badge.data('cat-id') || $badge.attr('data-cat-id');
        var catName = $badge.data('cat-name') || $badge.attr('data-cat-name') || '';
        if (!posId || !catId) return;

        var $tbody = $('#positionCategoryModalTable tbody').empty();

        // attempt to get position name from DataTable row
        var $tr = $badge.closest('tr');
        var rowData = null;
        try { rowData = positionsTable.row($tr).data(); } catch(e) { rowData = null; }
        var posName = (rowData && rowData.name) ? rowData.name : $tr.find('td').eq(1).text() || ('#' + posId);
        $('#positionCategoryModalLabel').text('Indicators for Position: ' + posName + ' — Category: ' + catName);

        $.get('/indicator/positions/' + posId + '/mappings', function (res) {
            var list = (res && res.data) ? res.data.filter(function (it) { return String(it.category_id) === String(catId); }) : [];
            // store active pos/cat on modal
            $('#positionCategoryModal').data('pos-id', posId).data('cat-id', catId);
            if (!list.length) {
                $tbody.append('<tr><td colspan="3">No indicators mapped for this category and position.</td></tr>');
                $('#positionCategorySaveBtn').prop('disabled', true);
            } else {
                $.each(list, function (i, it) {
                    var value = (it.weight_percentage !== null && it.weight_percentage !== undefined) ? Number(it.weight_percentage).toFixed(2) : '';
                    var tr = '<tr>' +
                        '<td>' + (i+1) + '</td>' +
                        '<td>' + escapeHtml(it.indicator_name || '-') + '</td>' +
                        '<td class="text-right"><input type="number" min="0" max="100" step="0.01" class="form-control form-control-sm weight-input" data-indicator-id="' + it.indicator_id + '" value="' + value + '"></td>' +
                        '</tr>';
                    $tbody.append(tr);
                });

                // append totals row (will be updated by recalc)
                var totalRow = '<tr id="positionCategoryTotalsRow" class="font-weight-bold">'
                    + '<td></td>'
                    + '<td id="positionCategoryTotalCount">Total: 0</td>'
                    + '<td class="text-right" id="positionCategoryTotalWeighted">Total Weighted: 0.00%</td>'
                    + '</tr>';
                $tbody.append(totalRow);
                // initial recalc
                recalcPositionModalTotals();
            }
            $('#positionCategoryModal').modal('show');
        }).fail(function () {
            $tbody.append('<tr><td colspan="3">Failed to load data.</td></tr>');
            $('#positionCategoryModal').modal('show');
            $('#positionCategorySaveBtn').prop('disabled', true);
        });
    });

    // recalc totals in modal and set styles / save button
    function recalcPositionModalTotals() {
        var $rows = $('#positionCategoryModalTable tbody tr').not('#positionCategoryTotalsRow');
        var total = 0;
        var count = 0;
        $rows.each(function () {
            var $inp = $(this).find('.weight-input');
            if ($inp.length) {
                var v = parseFloat($inp.val());
                if (!isNaN(v)) {
                    total += v;
                }
                count++;
            }
        });
        var $totRow = $('#positionCategoryTotalsRow');
        if ($totRow.length) {
            $totRow.find('#positionCategoryTotalCount').text('Total: ' + count);
            $totRow.find('#positionCategoryTotalWeighted').text('Total Weighted: ' + Number(total).toFixed(2) + '%');
            // coloring: green when total == 100, red otherwise
            $totRow.removeClass('table-light table-danger table-success');
            if (Math.abs(total - 100.0) <= 0.001) {
                $totRow.addClass('table-success');
                $('#positionCategorySaveBtn').prop('disabled', false);
            } else {
                $totRow.addClass('table-danger');
                $('#positionCategorySaveBtn').prop('disabled', true);
            }
        }
    }

    // listen to input changes
    $(document).on('input', '#positionCategoryModalTable .weight-input', function () {
        recalcPositionModalTotals();
    });

    // when filters change, reload DataTables
    $('#positionsDivisionFilter').on('change', function () { positionsTable.ajax.reload(); });
    $('#indicatorCategoryFilter').on('change', function () { indicatorTable.ajax.reload(); });

    // save updated weights
    $(document).on('click', '#positionCategorySaveBtn', function () {
        var $btn = $(this);
        var $modal = $('#positionCategoryModal');
        var posId = $modal.data('pos-id');
        var catId = $modal.data('cat-id');
        if (!posId || !catId) return;
        var mappings = [];
        $('#positionCategoryModalTable .weight-input').each(function () {
            var indId = $(this).data('indicator-id');
            var v = parseFloat($(this).val());
            mappings.push({ indicator_id: indId, weight_percentage: isNaN(v) ? 0 : v });
        });

        $btn.prop('disabled', true).text('Saving...');
        $.ajax({
            url: '/indicator/positions/' + posId + '/mappings',
            method: 'POST',
            data: { mappings: mappings, category_id: catId },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#positionCategoryModal').modal('hide');
                positionsTable.ajax.reload(null, false);
                // optionally refresh other UI
            },
            error: function (xhr) {
                var msg = 'Failed to save mappings';
                try { msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : msg; } catch (e) {}
                alert(msg);
            },
            complete: function () {
                $btn.prop('disabled', false).text('Save');
            }
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
        // reset select2 for position mapping
        $('#indicator_position_ids').val(null).trigger('change');
        $('#indicator_apply_all_weight_right').val('');
        // clear mapping table (right)
        $('#indicatorPositionMappingsRight tbody').empty();
        $('#position_mappings').val('');
        // clear preview
        $('#positionPreviewTable tbody').empty();
        $('#positionPreviewSum').text('0');
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
            // refresh positions as well (keep select2 intact)
            var positionSelect = $('#indicator_position_ids');
            positionSelect.empty();
            $.each(response.positions || [], function (_, pos) {
                positionSelect.append('<option value="' + pos.id + '">' + pos.name + '</option>');
            });
            positionSelect.trigger('change');
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
        $('#indicator_position_ids').select2({ width: '100%', dropdownParent: $('#indicatorModal') });
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
        // collect position mappings into hidden input before serializing
        var mappings = collectIndicatorPositionMappings();
        if (mappings.length) {
            $('#position_mappings').val(JSON.stringify(mappings));
        } else {
            $('#position_mappings').val('');
        }
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
            // populate optional position mappings if available (multiple)
            if (data.position_indicators && data.position_indicators.length > 0) {
                var ids = [];
                var mapRows = [];
                $.each(data.position_indicators, function(_, m){
                    ids.push(String(m.position_id));
                    var name = (m.position && m.position.name) ? m.position.name : ('Position ' + m.position_id);
                    mapRows.push({position_id: m.position_id, position_name: name, weight_percentage: m.weight_percentage});
                });
                $('#indicator_position_ids').val(ids).trigger('change');
                renderIndicatorPositionMappings(mapRows);
            }

            $('#indicatorModal').modal('show');
        });
    });

    // load preview for a given position id
    function loadPositionPreview(positionId) {
        var tbody = $('#positionPreviewTable tbody');
        tbody.empty();
        $('#positionPreviewSum').text('0');

        if (!positionId) {
            return;
        }

        $.get('/indicator/positions/' + positionId + '/mappings', function (res) {
            if (!res || !res.data) return;
            var total = 0;
            $.each(res.data, function (_, item) {
                var row = '<tr>' +
                    '<td>' + (item.indicator_name || '-') + '<div class="small text-muted">' + (item.category_name || '') + '</div></td>' +
                    '<td class="text-right align-middle">' + (item.weight_percentage !== null ? item.weight_percentage : '-') + '</td>' +
                    '</tr>';
                tbody.append(row);
                total += parseFloat(item.weight_percentage || 0);
            });
            $('#positionPreviewSum').text(Number(total).toFixed(2));
            // highlight total if not 100
            if (Math.abs(total - 100) > 0.001) {
                $('#positionPreviewSum').css('color', total > 100 ? '#c82333' : '#856404');
            } else {
                $('#positionPreviewSum').css('color', '#28a745');
            }
        }).fail(function () {
            // noop
        });
    }

    // when position select changes, refresh preview
    $('#indicator_position_ids').on('change', function () {
        var vals = $(this).val() || [];
        // render mapping rows for selected positions
        renderIndicatorPositionMappings();
        // if single selection, show preview for that position
        if (vals.length === 1) {
            loadPositionPreview(vals[0]);
        } else {
            // clear preview when multiple or none
            $('#positionPreviewTable tbody').empty();
            $('#positionPreviewSum').text('0');
        }
    });

    // when user checks map all checkbox (right-side), select all positions
    $('#indicator_map_all_right').on('change', function(){
        if ($(this).is(':checked')) {
            // select all positions except top-level positions (parent_id null/empty)
            var opts = $('#indicator_position_ids option').filter(function(){
                var p = $(this).attr('data-parent');
                return p !== undefined && p !== null && String(p).trim() !== '';
            }).map(function(){ return $(this).val(); }).get();
            $('#indicator_position_ids').val(opts).trigger('change');
        } else {
            $('#indicator_position_ids').val(null).trigger('change');
        }
    });

    // Apply weight to all mapping rows (right-side)
    $('#indicator_apply_all_btn_right').on('click', function(){
        var v = $('#indicator_apply_all_weight_right').val();
        $('#indicatorPositionMappingsRight tbody').find('input.position-weight').val(v);
    });

    function renderIndicatorPositionMappings(mapRows) {
        // if mapRows provided, use it; otherwise build from selected options
        var rows = mapRows || [];
        if (!mapRows) {
            var sel = $('#indicator_position_ids').val() || [];
            $.each(sel, function(_, id){
                var opt = $('#indicator_position_ids option[value="' + id + '"]');
                var name = opt.text() || ('Position ' + id);
                rows.push({position_id: id, position_name: name, weight_percentage: ''});
            });
        }

        var $tb = $('#indicatorPositionMappingsRight tbody').empty();
        $.each(rows, function(_, r){
            var tr = '<tr data-pos="'+ r.position_id +'">'
                + '<td>' + escapeHtml(r.position_name) + '</td>'
                + '<td><input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm position-weight" value="' + (r.weight_percentage !== null ? r.weight_percentage : '') + '"></td>'
                + '</tr>';
            $tb.append(tr);
        });
    }

    function collectIndicatorPositionMappings() {
        var out = [];
        $('#indicatorPositionMappingsRight tbody tr').each(function(){
            var pid = $(this).data('pos');
            var w = $(this).find('input.position-weight').val();
            out.push({ position_id: pid, weight_percentage: w });
        });
        return out;
    }

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
