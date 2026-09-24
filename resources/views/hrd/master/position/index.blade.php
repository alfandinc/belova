@extends('layouts.hrd.app')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('title', 'Master Data Jabatan')

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
                            <li class="breadcrumb-item active">Jabatan</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Data Jabatan</h4>
                    <button type="button" class="btn btn-primary btn-sm" id="btnAddPosition">
                        <i class="fa fa-plus"></i> Tambah Jabatan
                    </button>
                </div><!--end card-header-->
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="positionTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Jabatan</th>
                                    <th>Level</th>
                                    <th>Divisi</th>
                                    <th>Atasan</th>
                                    <th>Status</th>
                                    <th>Deskripsi</th>
                                    <th>Jumlah Karyawan</th>
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
        </div> <!-- end col -->
    </div> <!-- end row -->
</div><!-- container -->

<!-- Add/Edit Position Modal -->
<div class="modal fade" id="positionModal" tabindex="-1" role="dialog" aria-labelledby="positionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="positionModalLabel">Tambah Jabatan</h5>
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

        function getFilteredParentOptions(divisionId, currentPositionId, selectedParentId) {
            return parentPositionOptionsHtml.filter(function(option) {
                var matchesDivision = !divisionId || (option.division_ids || []).map(String).indexOf(String(divisionId)) !== -1;
                var isSamePosition = currentPositionId && String(option.id) === String(currentPositionId);
                var isSelectedParent = selectedParentId && String(option.id) === String(selectedParentId);

                return (matchesDivision || isSelectedParent) && !isSamePosition;
            }).sort(function(left, right) {
                var leftLevelIndex = positionLevelOrder.indexOf(left.level);
                var rightLevelIndex = positionLevelOrder.indexOf(right.level);

                leftLevelIndex = leftLevelIndex === -1 ? positionLevelOrder.length : leftLevelIndex;
                rightLevelIndex = rightLevelIndex === -1 ? positionLevelOrder.length : rightLevelIndex;

                if (leftLevelIndex !== rightLevelIndex) {
                    return leftLevelIndex - rightLevelIndex;
                }

                return left.name.localeCompare(right.name);
            });
        }

        function refreshParentOptionsForRow($row, selectedParentId) {
            var divisionId = $row.find('.organization-division').val();
            var currentPositionId = $('#position_id').val();
            var $parentSelect = $row.find('.organization-parent');
            var options = getFilteredParentOptions(divisionId, currentPositionId, selectedParentId || $parentSelect.val());

            $parentSelect.html(buildSelectOptions(options, divisionId ? 'Tanpa atasan langsung' : 'Pilih divisi terlebih dahulu'));

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
        
        // Initialize DataTable
        var table = $('#positionTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('hrd.master.position.data') }}",
                error: function(xhr, error, thrown) {
                    console.log('DataTables error:', error, thrown);
                    Swal.fire('Error', 'Terjadi kesalahan saat memuat data. Silakan coba lagi.', 'error');
                }
            },
            columns: [
                {data: 'id', name: 'id'},
                {data: 'name', name: 'name'},
                {data: 'level_badge', name: 'level', defaultContent: '-', orderable: false, searchable: false},
                {data: 'division_name', name: 'division_name', defaultContent: '-'},
                {data: 'parent_name', name: 'parent_name', defaultContent: '-'},
                {data: 'status_badge', name: 'is_active', defaultContent: '-', orderable: false, searchable: false},
                {data: 'description', name: 'description'},
                {data: 'employee_count', name: 'employee_count', defaultContent: '0'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ],
            columnDefs: [
                { targets: [2, 5], render: function(data) { return data; } }
            ]
        });

        // Open modal for adding new position
        $('#btnAddPosition').on('click', function() {
            $('#positionModalLabel').text('Tambah Jabatan');
            var formEl = $('#positionForm')[0];
            if (formEl) {
                formEl.reset();
            }
            $('#position_id').val('');
            resetOrganizationUnitRows();
            $('#position_level').val('Staff');
            $('#position_is_active').val('1');
            $('.invalid-feedback').text('');
            $('#positionForm .is-invalid').removeClass('is-invalid');
            $('#positionModal').modal('show');
        });

        // Handle form submission
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
                    // Clear previous validation errors
                    $('.invalid-feedback').text('');
                    $('.is-invalid').removeClass('is-invalid');
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
                    table.ajax.reload();
                },
                error: function(xhr) {
                    $('#savePosition').attr('disabled', false).html('Simpan');
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            var fieldId = normalizePositionFieldKey(key);
                            $('#' + fieldId).addClass('is-invalid');
                            $('#' + fieldId + '-error').text(value[0]);
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
            $('.invalid-feedback').text('');
            $('.is-invalid').removeClass('is-invalid');
            
            $.ajax({
                url: "{{ route('hrd.master.position.show', ':id') }}".replace(':id', id),
                method: 'GET',
                success: function(response) {
                    $('#positionModalLabel').text('Edit Jabatan');
                    $('#position_id').val(response.id);
                    $('#name').val(response.name);
                    $('#position_level').val(response.level || 'Staff');
                    resetOrganizationUnitRows(response.organization_units || []);
                    $('#description').val(response.description);
                    $('#position_is_active').val(response.is_active ? '1' : '0');
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
                            table.ajax.reload();
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
