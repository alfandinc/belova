@extends('layouts.hrd.app')
@section('title', 'HRD | Daftar Karyawan')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <h2>Rekap Absensi</h2>
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="form-group mb-0">
                <label for="dateRange">Filter Tanggal:</label>
                <input type="text" id="dateRange" class="form-control" placeholder="Pilih rentang tanggal" autocomplete="off">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group mb-0">
                <label for="employeeFilter">Filter Karyawan:</label>
                <select id="employeeFilter" class="form-control">
                    @foreach(\App\Models\HRD\Employee::orderBy('nama')->get() as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->nama }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-lg-3 col-md-4 ml-auto">
            <form id="uploadForm" enctype="multipart/form-data">
                @csrf
                <div class="input-group">
                    <div class="custom-file">
                        <input type="file" name="file" id="file" class="custom-file-input" required>
                        <label class="custom-file-label" for="file">Choose XLS file</label>
                    </div>
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <hr>
    <table id="rekapTable" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Finger ID</th>
                <th>Nama</th>
                <th>Tanggal</th>
                <th>Jam Masuk</th>
                <th>Jam Keluar</th>
                <th>Shift</th>
                <th>Work Hour</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Jam Masuk & Jam Keluar</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="editId">
                    <div class="form-group">
                        <label for="editJamMasuk">Jam Masuk</label>
                        <input type="time" class="form-control" id="editJamMasuk" required>
                    </div>
                    <div class="form-group">
                        <label for="editJamKeluar">Jam Keluar</label>
                        <input type="time" class="form-control" id="editJamKeluar" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveEditBtn">Simpan</button>
            </div>
        </div>
    </div>
</div>
        </thead>
    </table>
</div>
@push('scripts')
<script>
$(function() {
    // Date range picker
    $('#dateRange').daterangepicker({
        locale: { format: 'YYYY-MM-DD' },
        autoUpdateInput: false,
        opens: 'left',
        ranges: {
            'Hari ini': [moment(), moment()],
            '7 Hari Terakhir': [moment().subtract(6, 'days'), moment()],
            'Bulan ini': [moment().startOf('month'), moment().endOf('month')],
            'Bulan lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    });
    $('#dateRange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        table.ajax.reload();
    });
    $('#dateRange').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
        table.ajax.reload();
    });

    // Enable select2 multi-select
    $('#employeeFilter').val(null).select2({
        placeholder: 'Pilih Karyawan',
        allowClear: true,
        width: '100%'
    });

    var table = $('#rekapTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('hrd.absensi_rekap.data') }}',
            data: function(d) {
                d.date_range = $('#dateRange').val();
                d.employee_ids = $('#employeeFilter').val();
            }
        },
        columns: [
            { data: 'finger_id', name: 'finger_id' },
            { data: 'employee_name', name: 'employee_name' },
            { data: 'date', name: 'date' },
            { data: 'jam_masuk', name: 'jam_masuk' },
            { data: 'jam_keluar', name: 'jam_keluar' },
            { data: 'shift', name: 'shift' },
            { data: 'work_hour', name: 'work_hour' },
            { data: 'status', name: 'status' },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return '<button class="btn btn-sm btn-warning edit-btn" data-id="'+row.id+'" data-date="'+row.date+'" data-jam-masuk="'+row.jam_masuk+'" data-jam-keluar="'+row.jam_keluar+'">Edit</button>';
                }
            }
        ]
    });

    $('#employeeFilter').on('change', function() {
        table.ajax.reload();
    });

    $('#rekapTable').on('click', '.edit-btn', function() {
        var id = $(this).data('id');
        var jamMasuk = $(this).data('jam-masuk');
        var jamKeluar = $(this).data('jam-keluar');
        $('#editId').val(id);
        $('#editJamMasuk').val(jamMasuk);
        $('#editJamKeluar').val(jamKeluar);
        $('#editModal').modal('show');
    });

    $('#saveEditBtn').on('click', function() {
        var id = $('#editId').val();
        var jamMasuk = $('#editJamMasuk').val();
        var jamKeluar = $('#editJamKeluar').val();
        $.post({
            url: '/hrd/absensi-rekap/' + id + '/update',
            data: {
                _token: '{{ csrf_token() }}',
                jam_masuk: jamMasuk,
                jam_keluar: jamKeluar
            },
            success: function(response) {
                $('#editModal').modal('hide');
                table.ajax.reload();
                alert('Data berhasil diupdate!');
            },
            error: function(xhr) {
                alert('Gagal update: ' + (xhr.responseJSON?.error || 'Unknown error'));
            }
        });
    });
});
</script>
@endpush
@endsection
