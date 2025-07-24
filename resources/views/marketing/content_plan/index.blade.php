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
                        <th>Status</th>
                        <th>Jenis Konten</th>
                        <th>Link Publikasi</th>
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
            { data: 'platform', name: 'platform' },
            { data: 'status', name: 'status' },
            { data: 'jenis_konten', name: 'jenis_konten' },
            { data: 'link_publikasi', name: 'link_publikasi', render: function(data) {
                if (data) {
                    return `<a href="${data}" target="_blank">${data}</a>`;
                }
                return '';
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
        e.preventDefault();
        let action = $(this).attr('data-action');
        let id = $(this).attr('data-id');
        let url = action === 'store' ? '{{ route('marketing.content-plan.store') }}' : `/marketing/content-plan/${id}`;
        let method = action === 'store' ? 'POST' : 'PUT';
        let formData = $(this).serializeArray();
        $.ajax({
            url: url,
            method: method,
            data: formData,
            success: function(res) {
                $('#contentPlanModal').modal('hide');
                table.ajax.reload();
                Swal.fire('Sukses', 'Data berhasil disimpan!', 'success');
            },
            error: function(xhr) {
                let msg = 'Terjadi kesalahan.';
                if(xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).join('<br>');
                }
                Swal.fire('Error', msg, 'error');
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
