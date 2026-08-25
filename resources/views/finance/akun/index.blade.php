@extends('layouts.finance.app')
@section('title', 'Finance | Master Akun')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center" style="gap:1rem;">
            <div>
                <h3 class="mb-0 font-weight-bold">Chart of Accounts</h3>
                <div class="text-muted small">Kelola struktur akun berjenjang, status aktif, dan pemakaian jurnal dengan pola kerja yang dekat dengan software akuntansi operasional.</div>
            </div>
            <div class="d-flex flex-wrap justify-content-end" style="gap:.75rem;">
                <div class="card shadow-sm border-0 mb-0" style="min-width:150px;">
                    <div class="card-body py-2 px-3">
                        <div class="text-muted small">Total Akun</div>
                        <div class="h5 mb-0 font-weight-bold">{{ number_format($summary['total'], 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="card shadow-sm border-0 mb-0" style="min-width:150px;">
                    <div class="card-body py-2 px-3">
                        <div class="text-muted small">Akun Aktif</div>
                        <div class="h5 mb-0 font-weight-bold text-success">{{ number_format($summary['active'], 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="card shadow-sm border-0 mb-0" style="min-width:150px;">
                    <div class="card-body py-2 px-3">
                        <div class="text-muted small">Akun Induk</div>
                        <div class="h5 mb-0 font-weight-bold text-primary">{{ number_format($summary['headers'], 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="card shadow-sm border-0 mb-0" style="min-width:150px;">
                    <div class="card-body py-2 px-3">
                        <div class="text-muted small">Akun Detail</div>
                        <div class="h5 mb-0 font-weight-bold text-dark">{{ number_format($summary['detail'], 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-3">
            <div class="row align-items-end">
                <div class="col-md-4 mb-2">
                    <label class="small text-muted mb-1">Tipe Akun</label>
                    <select id="filter-tipe-akun" class="form-control form-control-sm">
                        <option value="">Semua Tipe</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="small text-muted mb-1">Status</label>
                    <select id="filter-status-akun" class="form-control form-control-sm">
                        <option value="">Semua Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="small text-muted mb-1">Level</label>
                    <input type="number" min="0" id="filter-level-akun" class="form-control form-control-sm" placeholder="Semua">
                </div>
                <div class="col-md-3 mb-2 text-md-right">
                    <button type="button" id="reset-filter-akun" class="btn btn-light btn-sm border mt-4 mt-md-0">
                        <i class="fas fa-sync-alt mr-1"></i> Reset Filter
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:.75rem;">
                <div>
                    <div class="font-weight-bold">Daftar akun</div>
                    <div class="text-muted small">Akun induk dan detail dapat dikelola dari layar yang sama untuk menjaga struktur COA tetap rapi.</div>
                </div>
                <div class="d-flex flex-wrap align-items-center" style="gap:.75rem;">
                    <button type="button" class="btn btn-primary btn-sm" id="btn-create-akun">
                        <i class="fas fa-plus mr-1"></i> Tambah Akun
                    </button>
                    <div class="badge badge-light border px-3 py-2 text-muted">CRUD aktif</div>
                </div>
            </div>
            <div class="table-responsive">
                <table id="datatable-finance-akun" class="table table-bordered table-striped" style="width:100%;">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode & Nama Akun</th>
                            <th>Induk Akun</th>
                            <th>Tipe</th>
                            <th>Level</th>
                            <th>Status</th>
                            <th>Jumlah Jurnal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="akunModal" tabindex="-1" role="dialog" aria-labelledby="akunModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow">
            <form id="akunForm">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="akunModalLabel">Tambah Akun</h5>
                        <div class="text-muted small">Susun kode akun, parent, dan status agar siap dipakai pada jurnal umum.</div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body pt-3">
                    <div class="alert alert-danger" id="akunFormError" style="display:none;"></div>
                    <input type="hidden" id="akun-id" name="id">
                    <div class="card bg-light border-0 mb-3">
                        <div class="card-body py-3">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="small text-muted mb-1">Parent Akun</label>
                                    <select class="form-control" id="akun-parent-id" name="parent_id">
                                        <option value="">Tanpa Parent / Akun Induk</option>
                                        @foreach($parentOptions as $parent)
                                            <option value="{{ $parent->id }}" data-level="{{ $parent->level }}">{{ $parent->kode_akun }} - {{ $parent->nama_akun }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="small text-muted mb-1">Level Hirarki</label>
                                    <input type="text" class="form-control bg-white" id="akun-level-preview" value="0" readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="small text-muted mb-1">Kode Akun</label>
                                    <input type="text" class="form-control" id="akun-kode" name="kode_akun" required>
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label class="small text-muted mb-1">Nama Akun</label>
                                    <input type="text" class="form-control" id="akun-nama" name="nama_akun" required>
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label class="small text-muted mb-1">Tipe Akun</label>
                                    <input type="text" class="form-control" id="akun-tipe" name="tipe_akun" list="akun-type-list" placeholder="Contoh: Asset, Liability, Revenue">
                                    <datalist id="akun-type-list">
                                        @foreach($types as $type)
                                            <option value="{{ $type }}"></option>
                                        @endforeach
                                        <option value="Asset"></option>
                                        <option value="Liability"></option>
                                        <option value="Equity"></option>
                                        <option value="Revenue"></option>
                                        <option value="Expense"></option>
                                    </datalist>
                                </div>
                                <div class="col-md-4 mb-3 d-flex align-items-center">
                                    <div class="custom-control custom-switch mt-4">
                                        <input type="checkbox" class="custom-control-input" id="akun-is-active" name="is_active" value="1" checked>
                                        <label class="custom-control-label" for="akun-is-active">Aktif dipakai</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light border" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-akun">Simpan Akun</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
        var akunTable = $('#datatable-finance-akun').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("finance.akun.data") }}',
                data: function (d) {
                    d.tipe_akun = $('#filter-tipe-akun').val();
                    d.status = $('#filter-status-akun').val();
                    d.level = $('#filter-level-akun').val();
                }
            },
            order: [[1, 'asc']],
            columns: [
                { data: null, orderable: false, searchable: false, render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                { data: 'kode_nama_display', name: 'kode_akun' },
                { data: 'parent_display', name: 'parent.nama_akun', orderable: false },
                { data: 'type_display', name: 'tipe_akun' },
                { data: 'level_display', name: 'level' },
                { data: 'status_display', name: 'is_active' },
                { data: 'usage_display', name: 'jurnal_count', searchable: false },
                { data: 'actions_display', orderable: false, searchable: false }
            ]
        });

        function resetAkunForm() {
            $('#akunForm')[0].reset();
            $('#akun-id').val('');
            $('#akun-parent-id').val('');
            $('#akun-level-preview').val('0');
            $('#akun-is-active').prop('checked', true);
            $('#akunFormError').hide().empty();
            $('#akunModalLabel').text('Tambah Akun');
            $('#btn-save-akun').text('Simpan Akun');
            $('#akun-parent-id option').prop('disabled', false);
        }

        function updateLevelPreview() {
            var selected = $('#akun-parent-id option:selected');
            var level = selected.length && selected.val() ? (parseInt(selected.data('level'), 10) || 0) + 1 : 0;
            $('#akun-level-preview').val(level);
        }

        function buildUrl(template, id) {
            return template.replace(':id', id);
        }

        function showErrors(xhr, $target) {
            var message = 'Terjadi kesalahan pada server.';
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                var items = [];
                $.each(xhr.responseJSON.errors, function (key, value) {
                    items.push(Array.isArray(value) ? value.join('<br>') : value);
                });
                message = items.join('<br>');
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }

            $target.html(message).show();
        }

        $('#filter-tipe-akun, #filter-status-akun').on('change', function () {
            akunTable.ajax.reload();
        });

        $('#filter-level-akun').on('input', function () {
            akunTable.ajax.reload();
        });

        $('#reset-filter-akun').on('click', function () {
            $('#filter-tipe-akun').val('');
            $('#filter-status-akun').val('');
            $('#filter-level-akun').val('');
            akunTable.ajax.reload();
        });

        $('#akun-parent-id').on('change', updateLevelPreview);

        $('#btn-create-akun').on('click', function () {
            resetAkunForm();
            $('#akunModal').modal('show');
        });

        $(document).on('click', '.btn-edit-akun', function () {
            var id = $(this).data('id');
            resetAkunForm();

            $.get(buildUrl('{{ route("finance.akun.show", ":id") }}', id))
                .done(function (response) {
                    var data = response.data || {};
                    $('#akun-id').val(data.id || '');
                    $('#akun-parent-id').val(data.parent_id || '');
                    $('#akun-kode').val(data.kode_akun || '');
                    $('#akun-nama').val(data.nama_akun || '');
                    $('#akun-tipe').val(data.tipe_akun || '');
                    $('#akun-is-active').prop('checked', !!data.is_active);
                    $('#akun-parent-id option[value="' + data.id + '"]').prop('disabled', true);
                    updateLevelPreview();
                    $('#akunModalLabel').text('Edit Akun');
                    $('#btn-save-akun').text('Update Akun');
                    $('#akunModal').modal('show');
                })
                .fail(function () {
                    Swal.fire('Error', 'Gagal memuat data akun.', 'error');
                });
        });

        $('#akunForm').on('submit', function (e) {
            e.preventDefault();

            var id = $('#akun-id').val();
            var url = id ? buildUrl('{{ route("finance.akun.update", ":id") }}', id) : '{{ route("finance.akun.store") }}';
            var method = id ? 'POST' : 'POST';
            var payload = $(this).serializeArray();

            if (id) {
                payload.push({ name: '_method', value: 'PUT' });
            }

            if (!$('#akun-is-active').is(':checked')) {
                payload.push({ name: 'is_active', value: '0' });
            }

            $('#akunFormError').hide().empty();
            $('#btn-save-akun').prop('disabled', true);

            $.ajax({
                url: url,
                method: method,
                data: $.param(payload)
            }).done(function (response) {
                $('#akunModal').modal('hide');
                Swal.fire('Sukses', response.message || 'Akun berhasil disimpan.', 'success').then(function () {
                    window.location.reload();
                });
            }).fail(function (xhr) {
                showErrors(xhr, $('#akunFormError'));
            }).always(function () {
                $('#btn-save-akun').prop('disabled', false);
            });
        });

        $(document).on('click', '.btn-delete-akun', function () {
            var id = $(this).data('id');
            var label = $(this).data('label');

            Swal.fire({
                title: 'Hapus akun?',
                text: label,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                $.ajax({
                    url: buildUrl('{{ route("finance.akun.destroy", ":id") }}', id),
                    method: 'POST',
                    data: { _method: 'DELETE', _token: '{{ csrf_token() }}' }
                }).done(function (response) {
                    Swal.fire('Terhapus', response.message || 'Akun berhasil dihapus.', 'success');
                    akunTable.ajax.reload(null, false);
                }).fail(function (xhr) {
                    var message = 'Gagal menghapus akun.';
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        var merged = [];
                        $.each(xhr.responseJSON.errors, function (key, value) {
                            merged.push(Array.isArray(value) ? value.join('\n') : value);
                        });
                        message = merged.join('\n');
                    }
                    Swal.fire('Error', message, 'error');
                });
            });
        });
    });
</script>
@endsection
