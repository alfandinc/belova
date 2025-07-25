@extends('layouts.marketing.app')

@section('title', 'Content Plan')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Content Plan</h4>
            <button class="btn btn-primary" id="btnAddContentPlan">Tambah Content Plan</button>
        </div>
        <div class="card-body">
            <table class="table table-bordered" id="contentPlanTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Judul</th>
                        <th>Tanggal Publish</th>
                        <th>Platform</th>
                        <th>Jenis Konten</th>
                        <th>Link Publikasi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@include('marketing.content_plan.partials.modal')
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function() {
    // Gambar Referensi Preview Logic (moved from modal partial)
    function updateGambarPreview() {
        var gambar = $('#gambar_referensi').data('current');
        if (gambar) {
            $('#gambarReferensiPreview').attr('src', '/storage/' + gambar);
            $('#gambarReferensiPreviewWrapper').show();
        } else {
            $('#gambarReferensiPreview').attr('src', '');
            $('#gambarReferensiPreviewWrapper').hide();
        }
    }
    // Always update preview when modal is shown
    $('#contentPlanModal').on('shown.bs.modal', function() {
        updateGambarPreview();
    });
    // Hide preview on add
    $('#btnAddContentPlan').on('click', function() {
        $('#gambarReferensiPreview').attr('src', '');
        $('#gambarReferensiPreviewWrapper').hide();
        $('#gambar_referensi').val('');
        $('#gambar_referensi').removeData('current');
    });
    let table = $('#contentPlanTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('marketing.content-plan.index') }}',
        columns: [
            { data: null, name: 'no', orderable: false, searchable: false },
            { data: 'judul', name: 'judul' },
            { data: 'tanggal_publish', name: 'tanggal_publish', render: function(data) {
                if (data) {
                    // Use moment.js to format date in Indonesian
                    return moment(data).locale('id').format('D MMMM YYYY [jam] HH.mm');
                }
                return '';
            } },
            { data: 'platform', name: 'platform', render: function(data) {
                if (!data) return '';
                let icons = {
                    'Instagram': '<i class="fab fa-instagram fa-lg" title="Instagram" style="color:#E4405F; font-size:1.5em;"></i>',
                    'Facebook': '<i class="fab fa-facebook fa-lg" title="Facebook" style="color:#1877F3; font-size:1.5em;"></i>',
                    'TikTok': `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" style="vertical-align:middle;position:relative;top:-2px;" shape-rendering="geometricPrecision" text-rendering="geometricPrecision" image-rendering="optimizeQuality" fill-rule="evenodd" clip-rule="evenodd" viewBox="0 0 1000 1000"><path d="M906.25 0H93.75C42.19 0 0 42.19 0 93.75v812.49c0 51.57 42.19 93.75 93.75 93.75l812.5.01c51.56 0 93.75-42.19 93.75-93.75V93.75C1000 42.19 957.81 0 906.25 0zM684.02 319.72c-32.42-21.13-55.81-54.96-63.11-94.38-1.57-8.51-2.45-17.28-2.45-26.25H515l-.17 414.65c-1.74 46.43-39.96 83.7-86.8 83.7-14.57 0-28.27-3.63-40.35-9.99-27.68-14.57-46.63-43.58-46.63-76.97 0-47.96 39.02-86.98 86.97-86.98 8.95 0 17.54 1.48 25.66 4.01V421.89c-8.41-1.15-16.95-1.86-25.66-1.86-105.01 0-190.43 85.43-190.43 190.45 0 64.42 32.18 121.44 81.3 155.92 30.93 21.72 68.57 34.51 109.14 34.51 105.01 0 190.43-85.43 190.43-190.43V400.21c40.58 29.12 90.3 46.28 143.95 46.28V343.03c-28.89 0-55.8-8.59-78.39-23.31z"/></svg>`,
                    'YouTube': '<i class="fab fa-youtube fa-lg" title="YouTube" style="color:#FF0000; font-size:1.5em;"></i>',
                    'Website': '<i class="fas fa-globe fa-lg" title="Website" style="color:#28a745; font-size:1.5em;"></i>',
                    'Other': '<i class="fas fa-ellipsis-h fa-lg" title="Other" style="color:#6c757d; font-size:1.5em;"></i>'
                };
                let arr = [];
                if (Array.isArray(data)) {
                    arr = data;
                } else if (typeof data === 'string') {
                    // Try to handle comma-separated string (e.g. "Instagram,Facebook")
                    if (data.indexOf(',') !== -1) {
                        arr = data.split(',').map(s => s.trim());
                    } else {
                        arr = [data.trim()];
                    }
                }
                return arr.map(p => icons[p] || p).join(' ');
            } },
            { data: 'jenis_konten', name: 'jenis_konten' },
            { data: 'link_publikasi', name: 'link_publikasi', render: function(data) {
                if (data) {
                    return `<a href="${data}" target="_blank">${data}</a>`;
                }
                return '';
            } },
            { data: 'status', name: 'status', render: function(data) {
                let color = 'secondary';
                let label = data;
                if (!data) return '';
                switch (data.toLowerCase()) {
                    case 'draft': color = 'primary'; break;
                    case 'scheduled': color = 'warning'; break;
                    case 'published': color = 'success'; break;
                    case 'cancelled': color = 'danger'; break;
                }
                return `<span class="badge badge-${color}">${label}</span>`;
            } },
            { data: 'action', orderable: false, searchable: false },
        ],
        order: [[2, 'desc']],
        drawCallback: function(settings) {
            var api = this.api();
            api.column(0, {search:'applied', order:'applied'}).nodes().each(function(cell, i) {
                cell.innerHTML = api.page.info().start + i + 1;
            });
        }
    });

    // Add Content Plan
    $('#btnAddContentPlan').on('click', function() {
        $('#contentPlanForm')[0].reset();
        $('#contentPlanModalLabel').text('Tambah Content Plan');
        $('#contentPlanModal').modal('show');
        $('#contentPlanForm').attr('data-action', 'store');
        $('#contentPlanForm').attr('data-id', '');
        $('.select2').val(null).trigger('change');
    });

    // Store/Update Content Plan
    $('#contentPlanForm').on('submit', function(e) {
        var $btn = $('#contentPlanModal .btn-primary');
        $btn.prop('disabled', true);
        var originalText = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm mr-1"></span> Menyimpan...');
        e.preventDefault();
        let action = $(this).attr('data-action');
        let id = $(this).attr('data-id');
        let url = action === 'store' ? '{{ route('marketing.content-plan.store') }}' : `/marketing/content-plan/${id}`;
        let method = action === 'store' ? 'POST' : 'POST'; // Always POST, use _method for PUT
        let form = this;
        let formData = new FormData(form);
        if (action === 'update') {
            formData.append('_method', 'PUT');
        }
        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                $('#contentPlanModal').modal('hide');
                table.ajax.reload();
                Swal.fire('Sukses', 'Data berhasil disimpan!', 'success');
                $btn.prop('disabled', false);
                $btn.html(originalText);
            },
            error: function(xhr) {
                let msg = 'Terjadi kesalahan.';
                if(xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).join('<br>');
                }
                Swal.fire('Error', msg, 'error');
                $btn.prop('disabled', false);
                $btn.html(originalText);
            }
        });
    });

    // Edit Content Plan
    $('#contentPlanTable').on('click', '.btn-edit', function() {
        let id = $(this).data('id');
        $.get(`/marketing/content-plan/${id}`, function(res) {
            let data = res;
            $('#contentPlanForm')[0].reset();
            $('#contentPlanModalLabel').text('Edit Content Plan');
            $('#contentPlanModal').modal('show');
            $('#contentPlanForm').attr('data-action', 'update');
            $('#contentPlanForm').attr('data-id', id);
            $('#judul').val(data.judul);
            $('#deskripsi').val(data.deskripsi);
            // Format tanggal_publish to 'YYYY-MM-DDTHH:MM' for datetime-local input
            let tgl = data.tanggal_publish ? data.tanggal_publish.replace(' ', 'T').slice(0,16) : '';
            $('#tanggal_publish').val(tgl);
            $('#platform').val(data.platform).trigger('change');
            $('#status').val(data.status);
            $('#jenis_konten').val(data.jenis_konten).trigger('change');
            $('#target_audience').val(data.target_audience);
            $('#link_asset').val(data.link_asset);
            $('#link_publikasi').val(data.link_publikasi);
            $('#catatan').val(data.catatan);
            // Set gambar referensi preview only (no filename)
            if (data.gambar_referensi) {
                $('#gambar_referensi').data('current', data.gambar_referensi);
                $('#gambarReferensiPreview').attr('src', '/storage/' + data.gambar_referensi).show();
            } else {
                $('#gambar_referensi').removeData('current');
                $('#gambarReferensiPreview').hide();
            }
        });
    });

    // Delete Content Plan
    $('#contentPlanTable').on('click', '.btn-delete', function() {
        let id = $(this).data('id');
        Swal.fire({
            title: 'Yakin hapus?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!'
        }).then((result) => {
            if(result.isConfirmed) {
                $.ajax({
                    url: `/marketing/content-plan/${id}`,
                    method: 'DELETE',
                    success: function() {
                        table.ajax.reload();
                        Swal.fire('Terhapus!', 'Data berhasil dihapus.', 'success');
                    },
                    error: function() {
                        Swal.fire('Error', 'Gagal menghapus data.', 'error');
                    }
                });
            }
        });
    });

    // Select2
    $('.select2').select2({
        dropdownParent: $('#contentPlanModal'),
        width: '100%'
    });
});
</script>
@endpush
