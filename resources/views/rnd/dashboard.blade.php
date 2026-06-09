@extends('layouts.admin.app')

@section('title', 'RND Master Data')

@section('navbar')
    @include('layouts.rnd.navbar')
@endsection

@section('styles')
<style>
    .rnd-master-card {
        border: 1px solid rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .rnd-master-header {
        padding: 10px 14px;
        color: #fff;
        border-bottom: 0;
    }

    .rnd-master-header .header-title {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
        min-height: 32px;
    }

    .rnd-master-header .icon-wrap {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.18);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.18);
        flex: 0 0 auto;
        line-height: 1;
        align-self: center;
    }

    .rnd-master-header .icon-wrap i {
        font-size: 14px;
        line-height: 1;
        display: block;
    }

    .rnd-master-header .title-text {
        color: inherit;
        font-weight: 700;
        font-size: 1.1rem;
        line-height: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        white-space: nowrap;
    }

    .rnd-master-header .badge {
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.24);
        font-size: 11px;
    }

    .rnd-master-header .btn-light {
        color: #0f172a;
        border-color: rgba(255, 255, 255, 0.22);
        background: #fff;
        padding: 0.3rem 0.6rem;
        font-size: 0.78rem;
    }

    .rnd-master-card .card-body {
        padding-top: 16px;
    }

    .rnd-card-toolbar {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .rnd-card-toolbar .dataTables_filter {
        margin: 0;
    }

    .rnd-card-toolbar .dataTables_filter label,
    .rnd-table-length-slot .dataTables_length label {
        margin-bottom: 0;
        font-weight: 500;
    }

    .rnd-card-toolbar .dataTables_filter label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 0;
    }

    .rnd-card-toolbar .dataTables_filter label::before {
        content: "\f002";
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
        font-size: 14px;
        line-height: 1;
        color: rgba(255, 255, 255, 0.92);
    }

    .rnd-card-toolbar .dataTables_filter input {
        margin-left: 0;
        width: 180px;
    }

    .rnd-table-length-slot {
        margin-top: 10px;
    }

    .rnd-table-length-slot .dataTables_length {
        margin: 0;
    }

    .rnd-table-length-slot .dataTables_length label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #64748b;
    }

    .rnd-table-length-slot .dataTables_length select {
        width: auto;
        min-width: 68px;
        height: 32px;
        padding: 0.2rem 1.9rem 0.2rem 0.6rem;
        border-radius: 8px;
        border: 1px solid #dbe4f0;
        background-color: #fff;
        font-size: 13px;
        color: #0f172a;
        box-shadow: none;
    }

    .select2-container--default .select2-selection--multiple {
        min-height: 38px;
        border-color: #ced4da;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background: #2563eb;
        border-color: #2563eb;
        color: #fff;
        padding: 2px 8px;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: rgba(255, 255, 255, 0.85);
        margin-right: 6px;
    }

    .rnd-type-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.3rem 0.55rem;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
        margin-right: 4px;
        margin-bottom: 4px;
    }

    .rnd-type-badge-kemasan {
        background: rgba(20, 184, 166, 0.14);
        color: #0f766e;
        border: 1px solid rgba(15, 118, 110, 0.18);
    }

    .rnd-type-badge-vendor {
        background: rgba(245, 158, 11, 0.16);
        color: #b45309;
        border: 1px solid rgba(180, 83, 9, 0.18);
    }

    .js-rnd-master-table .btn-group .btn {
        min-width: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .rnd-theme-brand { background: linear-gradient(135deg, #1d4ed8, #2563eb); }
    .rnd-theme-kemasan { background: linear-gradient(135deg, #0f766e, #14b8a6); }
    .rnd-theme-sediaan { background: linear-gradient(135deg, #7c3aed, #a855f7); }
    .rnd-theme-vendor { background: linear-gradient(135deg, #b45309, #f59e0b); }
    .rnd-theme-bahan-aktif { background: linear-gradient(135deg, #be123c, #f43f5e); }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">RND Master Data</h2>
            <p class="text-muted mb-0">Kelola seluruh master RND dengan DataTable, AJAX, dan notifikasi Swal tanpa refresh halaman.</p>
        </div>
    </div>

    <div class="row">
        @foreach($masters as $master)
            @php
                $sectionTitle = preg_replace('/^Master\s+/i', '', $master['label']);
                $themeClass = match ($master['key']) {
                    'brand' => 'rnd-theme-brand',
                    'kemasan' => 'rnd-theme-kemasan',
                    'sediaan' => 'rnd-theme-sediaan',
                    'vendor' => 'rnd-theme-vendor',
                    'bahan-aktif' => 'rnd-theme-bahan-aktif',
                    default => 'rnd-theme-brand',
                };
                $iconClass = match ($master['key']) {
                    'brand' => 'fas fa-award',
                    'kemasan' => 'fas fa-box-open',
                    'sediaan' => 'fas fa-flask',
                    'vendor' => 'fas fa-handshake',
                    'bahan-aktif' => 'fas fa-leaf',
                    default => 'fas fa-database',
                };
            @endphp
            <div class="@if($master['key'] === 'vendor') col-12 col-xl-8 col-lg-12 @elseif($master['key'] === 'bahan-aktif') col-12 col-xl-4 col-lg-6 @else col-12 col-xl-4 col-lg-6 @endif mb-4">
                <div class="card shadow-sm rnd-master-card">
                    <div class="card-header rnd-master-header {{ $themeClass }}">
                        <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap: 10px;">
                            <div class="header-title">
                                <span class="icon-wrap"><i class="{{ $iconClass }}"></i></span>
                                <span class="title-text">{{ $sectionTitle }}</span>
                            </div>
                            <div class="rnd-card-toolbar">
                                <div data-master-filter-slot="{{ $master['key'] }}"></div>
                                <button type="button" class="btn btn-light btn-sm js-create-master" data-master="{{ $master['key'] }}">Tambah Data</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped w-100 js-rnd-master-table" id="rndMasterTable-{{ $master['key'] }}" data-master="{{ $master['key'] }}">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        @foreach($master['columns'] as $column)
                                            @if(!($master['key'] === 'kemasan' && $column['data'] === 'ukuran'))
                                            <th>{{ $column['title'] }}</th>
                                            @endif
                                        @endforeach
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="rnd-table-length-slot" data-master-length-slot="{{ $master['key'] }}"></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="modal fade" id="rndMasterModal" tabindex="-1" role="dialog" aria-labelledby="rndMasterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rndMasterModalLabel">Tambah Data</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="rndMasterForm">
                <div class="modal-body">
                    <input type="hidden" id="masterRecordId" value="">
                    <div id="rndMasterFormFields"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
        var masters = @json($masters);
        var mastersByKey = {};
        masters.forEach(function(master) { mastersByKey[master.key] = master; });

        var activeMaster = masters.length ? masters[0].key : null;
        var tables = {};

        function escapeHtml(value) {
            return $('<div>').text(value == null ? '' : value).html();
        }

        function buildInput(field, value) {
            var required = field.required ? 'required' : '';
            var current = value == null ? '' : value;
            var html = '<div class="form-group">';
            html += '<label for="field_' + field.name + '">' + field.label + (field.required ? ' <span class="text-danger">*</span>' : '') + '</label>';

            if (field.type === 'textarea') {
                html += '<textarea class="form-control" id="field_' + field.name + '" name="' + field.name + '" rows="4" ' + required + '>' + escapeHtml(current) + '</textarea>';
            } else if (field.type === 'multiselect') {
                var selectedValues = Array.isArray(current) ? current : (current ? [current] : []);
                html += '<select class="form-control" id="field_' + field.name + '" name="' + field.name + '[]" multiple ' + required + '>';
                (field.options || []).forEach(function(option) {
                    var selected = selectedValues.indexOf(option) !== -1 ? 'selected' : '';
                    html += '<option value="' + escapeHtml(option) + '" ' + selected + '>' + option + '</option>';
                });
                html += '</select>';
                html += '<small class="form-text text-muted">Bisa pilih lebih dari satu.</small>';
            } else if (field.type === 'select') {
                html += '<select class="form-control" id="field_' + field.name + '" name="' + field.name + '" ' + required + '>';
                html += '<option value="">Pilih ' + field.label + '</option>';
                (field.options || []).forEach(function(option) {
                    var selected = option === current ? 'selected' : '';
                    html += '<option value="' + escapeHtml(option) + '" ' + selected + '>' + option + '</option>';
                });
                html += '</select>';
            } else {
                html += '<input type="text" class="form-control" id="field_' + field.name + '" name="' + field.name + '" value="' + escapeHtml(current) + '" ' + required + '>';
            }

            html += '</div>';
            return html;
        }

        function renderForm(record) {
            var master = mastersByKey[activeMaster];
            var formHtml = '';

            master.fields.forEach(function(field) {
                formHtml += buildInput(field, record ? record[field.name] : '');
            });

            $('#rndMasterFormFields').html(formHtml);

            $('#rndMasterFormFields').find('select[multiple]').each(function() {
                var $select = $(this);

                if ($select.data('select2')) {
                    $select.select2('destroy');
                }

                if ($.fn.select2) {
                    $select.select2({
                        width: '100%',
                        dropdownParent: $('#rndMasterModal'),
                        placeholder: 'Pilih satu atau lebih opsi'
                    });
                }
            });
        }

        function buildColumns(master) {
            var columns = [
                {
                    data: null,
                    title: 'No',
                    orderable: false,
                    searchable: false,
                    width: '60px',
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                }
            ];

            master.columns.forEach(function(column) {
                if (master.key === 'kemasan' && column.data === 'ukuran') {
                    return;
                }

                columns.push({
                    data: column.data,
                    title: column.title,
                    defaultContent: '-',
                    render: function(data, type, row) {
                        if (master.key === 'kemasan' && column.data === 'nama_kemasan') {
                            var namaKemasan = data == null || data === '' ? '-' : escapeHtml(data);
                            var ukuranKemasan = row.ukuran == null || row.ukuran === '' ? '' : ' <span class="text-muted">(' + escapeHtml(row.ukuran) + ')</span>';
                            return '<strong>' + namaKemasan + '</strong>' + ukuranKemasan;
                        }

                        if (master.key === 'kemasan' && column.data === 'tipe_kemasan') {
                            if (data == null || data === '') {
                                return '-';
                            }

                            return '<span class="rnd-type-badge rnd-type-badge-kemasan">' + escapeHtml(data) + '</span>';
                        }

                        if (master.key === 'vendor' && column.data === 'tipe_vendor') {
                            if (Array.isArray(data)) {
                                return data.length
                                    ? data.map(function(item) {
                                        return '<span class="rnd-type-badge rnd-type-badge-vendor">' + escapeHtml(item) + '</span>';
                                    }).join('')
                                    : '-';
                            }

                            return data ? '<span class="rnd-type-badge rnd-type-badge-vendor">' + escapeHtml(data) + '</span>' : '-';
                        }

                        if (/^nama_/i.test(column.data)) {
                            return data == null || data === '' ? '-' : '<strong>' + escapeHtml(data) + '</strong>';
                        }

                        if (Array.isArray(data)) {
                            return data.length ? escapeHtml(data.join(', ')) : '-';
                        }

                        return data == null || data === '' ? '-' : escapeHtml(data);
                    }
                });
            });

            columns.push({
                data: 'actions',
                title: 'Aksi',
                orderable: false,
                searchable: false,
                width: '140px'
            });

            return columns;
        }

        function initTable(masterKey) {
            var master = mastersByKey[masterKey];
            var columns = buildColumns(master);
            var selector = '#rndMasterTable-' + masterKey;

            if (tables[masterKey]) {
                tables[masterKey].destroy();
                $(selector).find('tbody').empty();
            }

            tables[masterKey] = $(selector).DataTable({
                processing: true,
                serverSide: true,
                ajax: '/rnd/masters/' + masterKey + '/data',
                columns: columns,
                order: [[1, 'asc']],
                dom: "<'d-none'f><'d-none'l>rtip"
            });

            var $wrapper = $(selector + '_wrapper');
            $wrapper.find('.dataTables_filter').appendTo('[data-master-filter-slot="' + masterKey + '"]');
            $wrapper.find('.dataTables_length').appendTo('[data-master-length-slot="' + masterKey + '"]');
        }

        function openCreateModal(masterKey) {
            activeMaster = masterKey;
            $('#masterRecordId').val('');
            $('#rndMasterModalLabel').text('Tambah ' + mastersByKey[masterKey].label.replace(/^Master\s+/i, ''));
            renderForm(null);
            $('#rndMasterModal').modal('show');
        }

        function openEditModal(masterKey, id) {
            activeMaster = masterKey;
            $.getJSON('/rnd/masters/' + masterKey + '/' + id, function(response) {
                $('#masterRecordId').val(id);
                $('#rndMasterModalLabel').text('Edit ' + mastersByKey[masterKey].label.replace(/^Master\s+/i, ''));
                renderForm(response.data || {});
                $('#rndMasterModal').modal('show');
            }).fail(function() {
                Swal.fire('Error', 'Gagal mengambil detail data.', 'error');
            });
        }

        function reloadTable(masterKey) {
            if (tables[masterKey]) {
                tables[masterKey].ajax.reload(null, false);
            }
        }

        $(document).on('click', '.js-create-master', function() {
            openCreateModal($(this).data('master'));
        });

        $(document).on('click', '.js-edit-master', function() {
            openEditModal($(this).data('master'), $(this).data('id'));
        });

        $(document).on('click', '.js-delete-master', function() {
            var id = $(this).data('id');
            var master = $(this).data('master');

            Swal.fire({
                title: 'Hapus data?',
                text: 'Data yang dihapus tidak bisa dikembalikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (!result.value) {
                    return;
                }

                $.ajax({
                    url: '/rnd/masters/' + master + '/' + id,
                    method: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Swal.fire('Sukses', response.message || 'Data berhasil dihapus.', 'success');
                        reloadTable(master);
                    },
                    error: function(xhr) {
                        var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Gagal menghapus data.';
                        Swal.fire('Error', message, 'error');
                    }
                });
            });
        });

        $('#rndMasterForm').on('submit', function(e) {
            e.preventDefault();

            var id = $('#masterRecordId').val();
            var formData = $(this).serializeArray();
            formData.push({ name: '_token', value: $('meta[name="csrf-token"]').attr('content') });

            if (id) {
                formData.push({ name: '_method', value: 'PUT' });
            }

            $.ajax({
                url: id ? '/rnd/masters/' + activeMaster + '/' + id : '/rnd/masters/' + activeMaster,
                method: 'POST',
                data: $.param(formData),
                success: function(response) {
                    $('#rndMasterModal').modal('hide');
                    Swal.fire('Sukses', response.message || 'Data berhasil disimpan.', 'success');
                    reloadTable(activeMaster);
                },
                error: function(xhr) {
                    var message = 'Terjadi kesalahan saat menyimpan data.';
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    Swal.fire('Validasi gagal', message, 'warning');
                }
            });
        });

        masters.forEach(function(master) {
            initTable(master.key);
        });
    });
</script>
@endsection