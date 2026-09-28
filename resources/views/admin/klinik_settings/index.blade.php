@extends('layouts.admin.app')

@section('title', 'Klinik Setting')

@section('navbar')
    @include('layouts.admin.navbar')
@endsection

@section('content')
<div class="container">
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h3 class="card-title mb-0">Klinik Setting</h3>
                <button type="button" class="btn btn-primary" id="btnAddKlinik">Tambah Klinik</button>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered" id="klinik-table" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:140px">Logo</th>
                            <th>Nama Klinik</th>
                            <th style="width:120px">Cut Off</th>
                            <th style="width:150px">Warna</th>
                            <th style="width:160px">Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="klinikModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="klinikForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="klinikModalLabel">Tambah Klinik</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="klinikId" name="id">
                    <div class="form-group">
                        <label for="klinikNama">Nama Klinik</label>
                        <input type="text" class="form-control" id="klinikNama" name="nama" maxlength="255" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="klinikCutoff">Jam Cut Off Laporan</label>
                            <input type="time" class="form-control" id="klinikCutoff" name="report_cutoff_time" value="00:00">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="klinikColorText">Warna</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <input type="color" class="form-control p-1" id="klinikColorPicker" value="#007bff" style="width:48px; height:38px;">
                                </div>
                                <input type="text" class="form-control" id="klinikColorText" name="color" placeholder="#007bff" maxlength="7">
                            </div>
                            <small class="text-muted">Kosongkan jika tidak ada warna.</small>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label for="klinikLogo">Logo</label>
                        <div id="klinikLogoPreviewWrap" class="mb-2" style="display:none;">
                            <img id="klinikLogoPreview" src="" alt="Logo" style="max-height:80px; max-width:200px;" class="border rounded p-1">
                            <div class="custom-control custom-checkbox mt-1" id="klinikRemoveLogoWrap">
                                <input type="checkbox" class="custom-control-input" id="klinikRemoveLogo" name="remove_logo" value="1">
                                <label class="custom-control-label" for="klinikRemoveLogo">Hapus logo</label>
                            </div>
                        </div>
                        <input type="file" class="form-control-file" id="klinikLogo" name="logo" accept="image/png,image/jpeg,image/webp">
                        <small class="text-muted">Format JPG, PNG, atau WEBP. Maksimal 2 MB.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveKlinik">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    var baseUrl = '{{ url('/admin/klinik-settings') }}';

    var table = $('#klinik-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: '{{ route('admin.klinik_settings.index') }}',
        order: [[1, 'asc']],
        columns: [
            { data: 'logo_html', name: 'logo', orderable: false, searchable: false },
            { data: 'nama', name: 'nama' },
            { data: 'report_cutoff_time', name: 'report_cutoff_time', searchable: false },
            { data: 'color_html', name: 'color', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ]
    });

    function resetForm() {
        $('#klinikForm')[0].reset();
        $('#klinikId').val('');
        $('#klinikCutoff').val('00:00');
        $('#klinikColorText').val('');
        $('#klinikColorPicker').val('#007bff');
        $('#klinikLogoPreview').attr('src', '');
        $('#klinikLogoPreviewWrap').hide();
        $('#klinikRemoveLogoWrap').show();
    }

    // keep color picker and hex text in sync
    $('#klinikColorPicker').on('input change', function() {
        $('#klinikColorText').val($(this).val());
    });
    $('#klinikColorText').on('input', function() {
        var v = $.trim($(this).val());
        if (/^#[0-9A-Fa-f]{6}$/.test(v)) $('#klinikColorPicker').val(v);
    });

    // preview a newly chosen logo
    $('#klinikLogo').on('change', function() {
        var file = this.files && this.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#klinikLogoPreview').attr('src', e.target.result);
            $('#klinikLogoPreviewWrap').show();
            $('#klinikRemoveLogoWrap').hide();
            $('#klinikRemoveLogo').prop('checked', false);
        };
        reader.readAsDataURL(file);
    });

    $('#btnAddKlinik').on('click', function() {
        resetForm();
        $('#klinikModalLabel').text('Tambah Klinik');
        $('#klinikModal').modal('show');
    });

    $('#klinik-table').on('click', '.btn-edit-klinik', function() {
        var id = $(this).data('id');
        $.get(baseUrl + '/' + id, function(data) {
            resetForm();
            $('#klinikId').val(data.id);
            $('#klinikNama').val(data.nama);
            $('#klinikCutoff').val(data.report_cutoff_time || '00:00');
            if (data.color) {
                $('#klinikColorText').val(data.color);
                $('#klinikColorPicker').val(data.color);
            }
            if (data.logo_url) {
                $('#klinikLogoPreview').attr('src', data.logo_url);
                $('#klinikLogoPreviewWrap').show();
            }
            $('#klinikModalLabel').text('Edit Klinik');
            $('#klinikModal').modal('show');
        });
    });

    $('#klinikForm').on('submit', function(e) {
        e.preventDefault();
        var id = $('#klinikId').val();
        var formData = new FormData(this);
        // multipart requests can't be sent as PUT, so spoof the method
        if (id) formData.append('_method', 'PUT');

        var $btn = $('#btnSaveKlinik').prop('disabled', true);
        $.ajax({
            url: id ? baseUrl + '/' + id : baseUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function() {
                $('#klinikModal').modal('hide');
                table.ajax.reload(null, false);
                Swal.fire('Berhasil', 'Klinik berhasil disimpan.', 'success');
            },
            error: function(xhr) {
                var msg = 'Terjadi kesalahan.';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.errors) {
                        msg = Object.values(xhr.responseJSON.errors).map(function(m) { return m[0]; }).join('<br>');
                    } else if (xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                }
                Swal.fire({ title: 'Gagal', html: msg, icon: 'error' });
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    $('#klinik-table').on('click', '.btn-delete-klinik', function() {
        var id = $(this).data('id');
        var name = $(this).data('name') || 'klinik ini';

        Swal.fire({
            title: 'Hapus klinik?',
            text: 'Anda akan menghapus ' + name + '.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Hapus',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (!result.value) {
                return;
            }

            $.ajax({
                url: baseUrl + '/' + id,
                type: 'DELETE',
                success: function() {
                    table.ajax.reload(null, false);
                    Swal.fire('Terhapus', 'Klinik berhasil dihapus.', 'success');
                },
                error: function(xhr) {
                    Swal.fire('Gagal', xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Terjadi kesalahan.', 'error');
                }
            });
        });
    });
});
</script>
@endsection
