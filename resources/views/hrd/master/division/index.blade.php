@extends('layouts.hrd.app')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('title', 'Master Data Divisi & Jabatan')

@section('content')
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <h4 class="page-title">Master Data</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">HRD</a></li>
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Master Data</a></li>
                            <li class="breadcrumb-item active">Divisi & Jabatan</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->

    @php
        $activeTab = request('tab', 'divisi');
    @endphp

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Data Divisi</h4>
                    <button type="button" class="btn btn-primary btn-sm" id="btnAddDivision">
                        <i class="fa fa-plus"></i> Tambah Divisi
                    </button>
                </div><!--end card-header-->
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="divisionTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Divisi</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded by DataTable -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
                <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Data Posisi</h4>
                    <div class="d-flex align-items-center">
                        <select id="filter_division" class="form-control form-control-sm me-2">
                            <option value="">Semua Divisi</option>
                            @foreach($divisions as $division)
                                <option value="{{ $division->id }}">{{ $division->name }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-primary btn-sm" id="btnAddPosition">
                            <i class="fa fa-plus"></i> Tambah Posisi
                        </button>
                    </div>
                </div><!--end card-header-->
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="positionTable" class="table table-striped table-bordered" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Posisi</th>
                                    <th>Level</th>
                                    <th>Divisi</th>
                                    <th>Posisi Induk</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded by DataTable -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div><!-- container -->

<!-- Add/Edit Division Modal -->
<div class="modal fade" id="divisionModal" tabindex="-1" role="dialog" aria-labelledby="divisionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="divisionModalLabel">Tambah Divisi</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="divisionForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="division_id" name="division_id">
                    
                    <div class="form-group">
                        <label for="name">Nama Divisi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback" id="name-error"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Deskripsi</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        <div class="invalid-feedback" id="description-error"></div>
                    </div>

                    <div class="form-group">
                        <label for="division_is_active">Status <span class="text-danger">*</span></label>
                        <select class="form-control" id="division_is_active" name="is_active" required>
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                        <div class="invalid-feedback" id="division_is_active-error"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary" id="saveDivision">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add/Edit Position Modal -->
<div class="modal fade" id="positionModal" tabindex="-1" role="dialog" aria-labelledby="positionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
                <div class="modal-header">
                	<h5 class="modal-title" id="positionModalLabel">Tambah Posisi</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="positionForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="position_id" name="position_id">

                    <div class="position-modal-section">
                        <div class="position-modal-section__title">Informasi Posisi</div>

                        <div class="form-group">
                            <label for="name">Nama Posisi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback" id="name-error"></div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="position_level">Jenjang / Level <span class="text-danger">*</span></label>
                                <select class="form-control" id="position_level" name="level" required>
                                    @foreach($levelOptions as $levelOption)
                                        <option value="{{ $levelOption }}">{{ $levelOption }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="position_level-error"></div>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="position_is_active">Status <span class="text-danger">*</span></label>
                                <select class="form-control" id="position_is_active" name="is_active" required>
                                    <option value="1">Aktif</option>
                                    <option value="0">Nonaktif</option>
                                </select>
                                <div class="invalid-feedback" id="position_is_active-error"></div>
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label for="description">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            <div class="invalid-feedback" id="description-error"></div>
                        </div>
                    </div>

                    <div class="position-modal-section">
                        <div class="position-modal-section__title">Penempatan Organisasi</div>
                        <div class="table-responsive">
                            <table class="table table-bordered position-unit-table mb-2">
                                <thead>
                                    <tr>
                                        <th>Unit / Divisi</th>
                                        <th>Atasan Langsung</th>
                                        <th class="position-unit-table__action">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="positionOrganizationUnits"></tbody>
                            </table>
                        </div>
                        <div class="invalid-feedback d-block" id="positionOrganizationUnits-error"></div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddOrganizationUnit">+ Tambah Unit</button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary" id="savePosition">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
/* Icon-only action buttons styling */
.table .btn-icon-only { padding: .25rem .4rem; }
.table .btn-icon-only i { margin: 0; }
.table small.text-muted { display: block; margin-top: 2px; }
.position-modal-section {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1rem;
}
.position-modal-section__title {
    font-weight: 600;
    margin-bottom: 1rem;
    color: #1f2937;
}
.position-unit-table th,
.position-unit-table td {
    vertical-align: middle;
}
.position-unit-table__action {
    width: 70px;
    text-align: center;
}
</style>
<script>
    $(document).ready(function() {
        var divisionOptionsHtml = @json($divisionOptions);
        var parentPositionOptionsHtml = @json($parentPositionOptions);
        var positionLevelOrder = ['Direktur', 'Head Manager', 'Manager', 'Penanggung Jawab', 'Koordinator', 'Staff'];

        function normalizePositionFieldKey(key) {
            if (key.indexOf('organization_units') === 0) {
                return 'positionOrganizationUnits';
            }

            if (key.indexOf('parent_position_ids') === 0) {
                return 'position_parent_ids';
            }

            if (key === 'level') {
                return 'position_level';
            }

            if (key === 'is_active') {
                return 'position_is_active';
            }

            return key;
        }

        function buildSelectOptions(options, placeholder) {
            var html = '<option value="">' + placeholder + '</option>';

            if (!options.length) {
                return html;
            }

            positionLevelOrder.forEach(function(level) {
                var levelOptions = options.filter(function(option) {
                    return option.level === level;
                });

                if (!levelOptions.length) {
                    return;
                }

                html += '<optgroup label="' + level + '">';
                levelOptions.forEach(function(option) {
                    html += '<option value="' + option.id + '">' + option.name + '</option>';
                });
                html += '</optgroup>';
            });

            var uncategorized = options.filter(function(option) {
                return positionLevelOrder.indexOf(option.level) === -1;
            });

            if (uncategorized.length) {
                html += '<optgroup label="Lainnya">';
                uncategorized.forEach(function(option) {
                    html += '<option value="' + option.id + '">' + option.name + '</option>';
                });
                html += '</optgroup>';
            }

            return html;
        }

        function getCurrentPositionLevel() {
            return $('#positionForm').find('#position_level').val() || null;
        }

        function getLevelIndex(level) {
            var index = positionLevelOrder.indexOf(level);
            return index === -1 ? null : index;
        }

        function canBeParentForLevel(parentLevel, currentLevel) {
            if (!currentLevel) {
                return true;
            }

            var currentLevelIndex = getLevelIndex(currentLevel);
            var parentLevelIndex = getLevelIndex(parentLevel);

            if (currentLevelIndex === null || parentLevelIndex === null) {
                return true;
            }

            return parentLevelIndex < currentLevelIndex;
        }

        function buildParentSelectOptions(optionGroups, placeholder) {
            var html = '<option value="">' + placeholder + '</option>';

            optionGroups.forEach(function(group) {
                if (!group.options.length) {
                    return;
                }

                html += '<optgroup label="' + group.label + '">';

                positionLevelOrder.forEach(function(level) {
                    var levelOptions = group.options.filter(function(option) {
                        return option.level === level;
                    });

                    levelOptions.forEach(function(option) {
                        var divisionSuffix = option.division_names ? ' - ' + option.division_names : '';
                        html += '<option value="' + option.id + '">' + option.name + divisionSuffix + '</option>';
                    });
                });

                var uncategorized = group.options.filter(function(option) {
                    return positionLevelOrder.indexOf(option.level) === -1;
                });

                uncategorized.forEach(function(option) {
                    var divisionSuffix = option.division_names ? ' - ' + option.division_names : '';
                    html += '<option value="' + option.id + '">' + option.name + divisionSuffix + '</option>';
                });

                html += '</optgroup>';
            });

            return html;
        }

        function getFilteredParentOptions(divisionId, currentPositionId, selectedParentId) {
            var sameDivisionOptions = [];
            var crossDivisionOptions = [];
            var currentLevel = getCurrentPositionLevel();

            parentPositionOptionsHtml.forEach(function(option) {
                var divisionIds = (option.division_ids || []).map(String);
                var matchesDivision = !divisionId || divisionIds.indexOf(String(divisionId)) !== -1;
                var isSamePosition = currentPositionId && String(option.id) === String(currentPositionId);
                var isSelectedParent = selectedParentId && String(option.id) === String(selectedParentId);
                var levelAllowed = canBeParentForLevel(option.level, currentLevel);

                if (isSamePosition) {
                    return;
                }

                if (!levelAllowed && !isSelectedParent) {
                    return;
                }

                if (matchesDivision) {
                    sameDivisionOptions.push(option);
                    return;
                }

                if (levelAllowed || isSelectedParent) {
                    crossDivisionOptions.push(option);
                }
            });

            var sortOptions = function(options) {
                return options.sort(function(left, right) {
                var leftLevelIndex = positionLevelOrder.indexOf(left.level);
                var rightLevelIndex = positionLevelOrder.indexOf(right.level);

                leftLevelIndex = leftLevelIndex === -1 ? positionLevelOrder.length : leftLevelIndex;
                rightLevelIndex = rightLevelIndex === -1 ? positionLevelOrder.length : rightLevelIndex;

                if (leftLevelIndex !== rightLevelIndex) {
                    return leftLevelIndex - rightLevelIndex;
                }

                return left.name.localeCompare(right.name);
            });
            };

            return [
                { label: 'Divisi Yang Sama', options: sortOptions(sameDivisionOptions) },
                { label: 'Atasan Lintas Divisi', options: sortOptions(crossDivisionOptions) }
            ];
        }

        function refreshParentOptionsForRow($row, selectedParentId) {
            var divisionId = $row.find('.organization-division').val();
            var currentPositionId = $('#position_id').val();
            var $parentSelect = $row.find('.organization-parent');
            var optionGroups = getFilteredParentOptions(divisionId, currentPositionId, selectedParentId || $parentSelect.val());
            var options = optionGroups.reduce(function(result, group) {
                return result.concat(group.options);
            }, []);

            $parentSelect.html(buildParentSelectOptions(optionGroups, divisionId ? 'Tanpa atasan langsung' : 'Pilih divisi terlebih dahulu'));

            if (selectedParentId && options.some(function(option) { return String(option.id) === String(selectedParentId); })) {
                $parentSelect.val(String(selectedParentId));
            } else {
                $parentSelect.val('');
            }
        }

        function createOrganizationUnitRow(unit) {
            var index = $('#positionOrganizationUnits').children('tr').length;
            var divisionId = unit && unit.division_id ? String(unit.division_id) : '';
            var parentPositionId = unit && unit.parent_position_id ? String(unit.parent_position_id) : '';
            var rowHtml = '' +
                '<tr>' +
                    '<td>' +
                        '<select class="form-control organization-division" name="organization_units[' + index + '][division_id]" required>' +
                            buildSelectOptions(divisionOptionsHtml, 'Pilih divisi') +
                        '</select>' +
                    '</td>' +
                    '<td>' +
                        '<select class="form-control organization-parent" name="organization_units[' + index + '][parent_position_id]">' +
                            buildSelectOptions([], 'Pilih divisi terlebih dahulu') +
                        '</select>' +
                    '</td>' +
                    '<td class="text-center">' +
                        '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-organization-unit">&times;</button>' +
                    '</td>' +
                '</tr>';

            var $row = $(rowHtml);
            $row.find('.organization-division').val(divisionId);
            refreshParentOptionsForRow($row, parentPositionId);

            return $row;
        }

        function reindexOrganizationUnitRows() {
            $('#positionOrganizationUnits').children('tr').each(function(index) {
                $(this).find('.organization-division').attr('name', 'organization_units[' + index + '][division_id]');
                $(this).find('.organization-parent').attr('name', 'organization_units[' + index + '][parent_position_id]');
            });
        }

        function resetOrganizationUnitRows(units) {
            var $tbody = $('#positionOrganizationUnits');
            $tbody.empty();

            if (!units || !units.length) {
                $tbody.append(createOrganizationUnitRow());
                return;
            }

            units.forEach(function(unit) {
                $tbody.append(createOrganizationUnitRow(unit));
            });

            reindexOrganizationUnitRows();
        }

        $('#btnAddOrganizationUnit').on('click', function() {
            $('#positionOrganizationUnits').append(createOrganizationUnitRow());
            reindexOrganizationUnitRows();
        });

        $(document).on('click', '.btn-remove-organization-unit', function() {
            if ($('#positionOrganizationUnits').children('tr').length === 1) {
                $(this).closest('tr').find('select').val('');
                refreshParentOptionsForRow($(this).closest('tr'));
                return;
            }

            $(this).closest('tr').remove();
            reindexOrganizationUnitRows();
        });

        $(document).on('change', '.organization-division', function() {
            var $row = $(this).closest('tr');
            refreshParentOptionsForRow($row);
        });

        $('#positionForm').find('#position_level').on('change', function() {
            $('#positionOrganizationUnits').children('tr').each(function() {
                refreshParentOptionsForRow($(this));
            });
        });

        // Initialize DataTable for Divisi
        var divisionTable = $('#divisionTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('hrd.master.division.data') }}",
                error: function(xhr, error, thrown) {
                    console.log('DataTables error:', error, thrown);
                    Swal.fire('Error', 'Terjadi kesalahan saat memuat data. Silakan coba lagi.', 'error');
                }
            },
            columns: [
                {data: 'id', name: 'id'},
                {data: 'name', name: 'name', render: function(data, type, row) {
                    var desc = row.description ? '<br><small class="text-muted">' + row.description + '</small>' : '';
                    return data + desc;
                }},
                {data: 'status_badge', name: 'is_active', defaultContent: '-', orderable: false, searchable: false},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        // (Optional) select2 for division dropdown was removed here to avoid
        // incompatibility errors on pages where Select2 compat modules are not loaded.

        // Initialize DataTable for Jabatan
        var positionTable = $('#positionTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('hrd.master.position.data') }}",
                data: function(d) {
                    d.division_id = $('#filter_division').val();
                },
                error: function(xhr, error, thrown) {
                    console.log('DataTables error:', error, thrown);
                    Swal.fire('Error', 'Terjadi kesalahan saat memuat data. Silakan coba lagi.', 'error');
                }
            },
            columns: [
                {data: 'id', name: 'id'},
                {data: 'name', name: 'name', render: function(data, type, row) {
                    var desc = row.description ? '<br><small class="text-muted">' + row.description + '</small>' : '';
                    return data + desc;
                }},
                {data: 'level_badge', name: 'level', defaultContent: '-', orderable: false, searchable: false},
                {data: 'division_name', name: 'division_name', defaultContent: '-'},
                {data: 'parent_name', name: 'parent_name', defaultContent: '-'},
                {data: 'status_badge', name: 'is_active', defaultContent: '-', orderable: false, searchable: false},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        // Convert action buttons to icon-only by keeping their first <i> element
        function makeActionButtonsIconOnly(tableSelector) {
            $(tableSelector + ' tbody').find('tr').each(function() {
                $(this).find('td').last().find('.btn').each(function() {
                    var $btn = $(this);
                    var $icon = $btn.find('i').first();
                    if ($icon.length) {
                        $btn.addClass('btn-icon-only');
                        $btn.html($icon);
                    } else {
                        $btn.html('<i class="fa fa-ellipsis-h"></i>');
                        $btn.addClass('btn-icon-only');
                    }
                });
            });
        }

        // Adjust DataTable columns after initialization and on resize, and fix buttons
        function adjustAndFix() {
            divisionTable.columns.adjust();
            positionTable.columns.adjust();
            makeActionButtonsIconOnly('#divisionTable');
            makeActionButtonsIconOnly('#positionTable');
        }

        setTimeout(adjustAndFix, 50);

        $(window).on('resize', function() {
            adjustAndFix();
        });

        // Initialize Select2 for parent position select inside the modal
        resetOrganizationUnitRows();

        // Ensure buttons are icon-only after each draw
        divisionTable.on('draw', function() { makeActionButtonsIconOnly('#divisionTable'); });
        positionTable.on('draw', function() { makeActionButtonsIconOnly('#positionTable'); });

        // Reload positions table when division filter changes
        $('#filter_division').on('change', function() {
            positionTable.ajax.reload();
        });

        // Open modal for adding new division
        $('#btnAddDivision').on('click', function() {
            $('#divisionModalLabel').text('Tambah Divisi');
            $('#divisionForm')[0].reset();
            $('#division_id').val('');
            $('#division_is_active').val('1');
            $('#divisionForm .invalid-feedback').text('');
            $('#divisionModal').modal('show');
        });

        // Handle form submission (Divisi)
        $('#divisionForm').on('submit', function(e) {
            e.preventDefault();
            var id = $('#division_id').val();
            var url = id ? "{{ route('hrd.master.division.update', ':id') }}".replace(':id', id) : "{{ route('hrd.master.division.store') }}";
            var method = id ? 'PUT' : 'POST';

            var formData = $(this).serialize();
            formData += '&_token=' + $('meta[name="csrf-token"]').attr('content');
            
            $.ajax({
                url: url,
                method: method,
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function() {
                    // Clear previous validation errors for division form only
                    $('#divisionForm .invalid-feedback').text('');
                    $('#divisionForm .is-invalid').removeClass('is-invalid');
                    $('#saveDivision').attr('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...');
                },
                success: function(response) {
                    Swal.fire({
                        title: 'Sukses!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                    $('#divisionModal').modal('hide');
                    divisionTable.ajax.reload();
                },
                error: function(xhr) {
                    $('#saveDivision').attr('disabled', false).html('Simpan');
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            var field = $('#divisionForm').find('#' + key);
                            field.addClass('is-invalid');
                            $('#divisionForm').find('#' + key + '-error').text(value[0]);
                        });
                    } else if (xhr.status === 500) {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Terjadi kesalahan pada server',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                complete: function() {
                    $('#saveDivision').attr('disabled', false).html('Simpan');
                }
            });
        });

        // Edit Division
        $(document).on('click', '.edit-division', function() {
            var id = $(this).data('id');
            $('#divisionForm .invalid-feedback').text('');
            $('#divisionForm .is-invalid').removeClass('is-invalid');
            
            $.ajax({
                url: "{{ route('hrd.master.division.show', ':id') }}".replace(':id', id),
                method: 'GET',
                success: function(response) {
                    $('#divisionModalLabel').text('Edit Divisi');
                    $('#division_id').val(response.id);
                    $('#divisionForm').find('#name').val(response.name);
                    $('#divisionForm').find('#description').val(response.description);
                    $('#divisionForm').find('#division_is_active').val(response.is_active ? '1' : '0');
                    $('#divisionModal').modal('show');
                }
            });
        });

        // Delete Division
        $(document).on('click', '.delete-division', function() {
            var id = $(this).data('id');
            
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: "{{ route('hrd.master.division.destroy', ':id') }}".replace(':id', id),
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            Swal.fire(
                                'Terhapus!',
                                response.message,
                                'success'
                            );
                            divisionTable.ajax.reload();
                        },
                        error: function(xhr) {
                            if (xhr.status === 422) {
                                Swal.fire(
                                    'Gagal!',
                                    xhr.responseJSON.message,
                                    'error'
                                );
                            } else {
                                Swal.fire(
                                    'Error!',
                                    'Terjadi kesalahan pada server',
                                    'error'
                                );
                            }
                        }
                    });
                }
            });
        });

        // Open modal for adding new position
        $('#btnAddPosition').on('click', function() {
            var $modal = $('#positionModal');
            if ($modal.length === 0) {
                console.error('positionModal element not found in DOM');
                return;
            }

                    $('#positionModalLabel').text('Tambah Posisi');
            var formEl = $('#positionForm')[0];
            if (formEl) {
                formEl.reset();
            }
            $('#position_id').val('');
            resetOrganizationUnitRows();
            $('#position_level').val('Staff');
            $('#position_is_active').val('1');
            $('#positionForm .invalid-feedback').text('');
            $('#positionForm .is-invalid').removeClass('is-invalid');
            $modal.modal('show');
        });

        // Handle position form submission
        $('#positionForm').on('submit', function(e) {
            e.preventDefault();
            var id = $('#position_id').val();
            var url = id ? "{{ route('hrd.master.position.update', ':id') }}".replace(':id', id) : "{{ route('hrd.master.position.store') }}";
            var method = id ? 'PUT' : 'POST';

            var formData = $(this).serialize();
            formData += '&_token=' + $('meta[name="csrf-token"]').attr('content');
            
            $.ajax({
                url: url,
                method: method,
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function() {
                    // Clear previous validation errors for position form only
                    $('#positionForm .invalid-feedback').text('');
                    $('#positionForm .is-invalid').removeClass('is-invalid');
                    $('#savePosition').attr('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...');
                },
                success: function(response) {
                    Swal.fire({
                        title: 'Sukses!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                    $('#positionModal').modal('hide');
                    positionTable.ajax.reload();
                },
                error: function(xhr) {
                    $('#savePosition').attr('disabled', false).html('Simpan');
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            var fieldId = normalizePositionFieldKey(key);
                            var field = $('#positionForm').find('#' + fieldId);
                            field.addClass('is-invalid');
                            $('#positionForm').find('#' + fieldId + '-error').text(value[0]);
                        });
                    } else if (xhr.status === 500) {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Terjadi kesalahan pada server',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                complete: function() {
                    $('#savePosition').attr('disabled', false).html('Simpan');
                }
            });
        });

        // Edit Position
        $(document).on('click', '.edit-position', function() {
            var id = $(this).data('id');
            $('#positionForm .invalid-feedback').text('');
            $('#positionForm .is-invalid').removeClass('is-invalid');
            
            $.ajax({
                url: "{{ route('hrd.master.position.show', ':id') }}".replace(':id', id),
                method: 'GET',
                success: function(response) {
                    $('#positionModalLabel').text('Edit Posisi');
                    $('#position_id').val(response.id);
                    $('#positionForm').find('#name').val(response.name);
                    $('#positionForm').find('#position_level').val(response.level || 'Staff');
                    resetOrganizationUnitRows(response.organization_units || []);
                    $('#positionForm').find('#description').val(response.description);
                    $('#positionForm').find('#position_is_active').val(response.is_active ? '1' : '0');
                    $('#positionModal').modal('show');
                }
            });
        });

        // Delete Position
        $(document).on('click', '.delete-position', function() {
            var id = $(this).data('id');
            
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: "{{ route('hrd.master.position.destroy', ':id') }}".replace(':id', id),
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            Swal.fire(
                                'Terhapus!',
                                response.message,
                                'success'
                            );
                            positionTable.ajax.reload();
                        },
                        error: function(xhr) {
                            if (xhr.status === 422) {
                                Swal.fire(
                                    'Gagal!',
                                    xhr.responseJSON.message,
                                    'error'
                                );
                            } else {
                                Swal.fire(
                                    'Error!',
                                    'Terjadi kesalahan pada server',
                                    'error'
                                );
                            }
                        }
                    });
                }
            });
        });
    });
</script>
@endsection
