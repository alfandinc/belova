@extends('layouts.hrd.app')
@section('title', 'HRD | Daftar Karyawan')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Daftar Karyawan</h3>
            <a href="{{ route('hrd.employee.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tambah Karyawan
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="employees-table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>NIK</th>
                            <th>Nama</th>
                            <th>Posisi</th>
                            <th>Divisi</th>
                            <th>Status</th>
                            <th>Tanggal Masuk</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
@endpush

@section('scripts')
{{-- <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> --}}

<script>
$(function() {
    var table = $('#employees-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hrd.employee.index') }}",
        columns: [
            {data: 'nik', name: 'nik'},
            {data: 'nama', name: 'nama'},
            {data: 'position.name', name: 'position.name'},
            {data: 'division.name', name: 'division.name'},
            {data: 'status_label', name: 'status', searchable: false},
            {data: 'tanggal_masuk', name: 'tanggal_masuk'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });
    
    // Handle delete button with SweetAlert
    $('#employees-table').on('click', '.delete-employee', function() {
        var employeeId = $(this).data('id');
        
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Data karyawan akan dihapus!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/hrd/employee/' + employeeId,
                    type: 'DELETE',
                    data: {
                        "_token": "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire(
                                'Terhapus!',
                                response.message,
                                'success'
                            );
                            table.ajax.reload();
                        } else {
                            Swal.fire(
                                'Error!',
                                response.message,
                                'error'
                            );
                        }
                    },
                    error: function(xhr) {
                        Swal.fire(
                            'Error!',
                            'Terjadi kesalahan saat menghapus data',
                            'error'
                        );
                    }
                });
            }
        });
    });
    
    // Show success message if exists
    @if(session('success'))
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: '{{ session('success') }}',
        timer: 3000,
        timerProgressBar: true
    });
    @endif
});
</script>
@endsection