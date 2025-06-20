@extends('layouts.hrd.app')
@section('title', 'HRD | Daftar Karyawan')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="card shadow">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h3 class="card-title m-0 font-weight-bold text-primary">Daftar Karyawan</h3>
            <a href="{{ route('hrd.employee.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> Tambah Karyawan
            </a>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <select class="form-control form-control-sm" id="entries-select">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <label for="entries-select" class="ml-2">entries</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control form-control-sm" id="search-input" placeholder="Search...">
                        <div class="input-group-append">
                            <button class="btn btn-sm btn-outline-secondary" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table id="employees-table" class="table table-bordered table-hover table-striped">
                    <thead class="thead-light">
                        <tr>
                            <th width="8%">NIK</th>
                            <th width="20%">Nama</th>
                            <th width="15%">Posisi</th>
                            <th width="15%">Divisi</th>
                            <th width="10%">Status</th>
                            <th width="15%">Tanggal Masuk</th>
                            <th width="17%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- DataTables will populate this -->
                    </tbody>
                </table>
            </div>
            
            <div class="row mt-3">
                <div class="col-md-5">
                    <div class="dataTables_info" id="table-info" role="status" aria-live="polite">
                        Showing <span id="showing-entries">0 to 0</span> of <span id="total-entries">0</span> entries
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="dataTables_paginate paging_simple_numbers" id="pagination-container">
                        <!-- Pagination will be handled by DataTables -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    #employees-table th, #employees-table td {
        vertical-align: middle;
    }
    
    .table-action-btn {
        margin: 0 3px;
    }
    
    .badge-active {
        background-color: #28a745;
    }
    
    .badge-inactive {
        background-color: #dc3545;
    }
    
    @media (max-width: 767px) {
        #employees-table {
            min-width: 800px;
        }
        
        .card-header {
            flex-direction: column;
            align-items: start !important;
        }
        
        .card-header .btn {
            margin-top: 10px;
            align-self: flex-start;
        }
    }
</style>
@endpush

@section('scripts')
<script>
$(function() {
     var table = $('#employees-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        dom: '<"top"f>rt<"bottom"lip><"clear">',
        language: {
            processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>',
            paginate: {
                previous: '<i class="fas fa-chevron-left"></i>',
                next: '<i class="fas fa-chevron-right"></i>'
            },
            emptyTable: 'Tidak ada data yang tersedia'
        },
        ajax: {
            url: "{{ route('hrd.employee.index') }}",
            error: function (xhr, error, thrown) {
                console.error('DataTables error:', error, thrown);
                console.log('Response:', xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Gagal memuat data. Silakan coba lagi.'
                });
            }
        },
        columns: [
            {data: 'nik', name: 'nik', defaultContent: '-'},
            {data: 'nama', name: 'nama', defaultContent: '-'},
            {data: 'position.name', name: 'position.name', defaultContent: '-'},
            {data: 'division.name', name: 'division.name', defaultContent: '-'},
            {
                data: 'status_label', 
                name: 'status', 
                defaultContent: '-',
                searchable: false,
                render: function(data, type, row) {
                    var statusClass = row.status ? 'badge-active' : 'badge-inactive';
                    var statusText = row.status ? 'Aktif' : 'Tidak aktif';
                    return '<span class="badge badge-pill ' + statusClass + '">' + statusText + '</span>';
                }
            },
            {
                data: 'tanggal_masuk', 
                name: 'tanggal_masuk', 
                defaultContent: '-',
                render: function(data) {
                    if (!data) return '-';
                    return new Date(data).toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric'
                    });
                }
            },
            {
                data: 'action', 
                name: 'action', 
                orderable: false, 
                searchable: false,
                render: function(data, type, row) {
                    return `
                        <div class="btn-group">                            <a href="/hrd/employee/${row.id}" class="btn btn-sm btn-info table-action-btn" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="/hrd/employee/${row.id}/edit" class="btn btn-sm btn-primary table-action-btn" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-danger delete-employee table-action-btn" data-id="${row.id}" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        drawCallback: function(settings) {
            var info = this.api().page.info();
            $('#showing-entries').text((info.start + 1) + ' to ' + info.end);
            $('#total-entries').text(info.recordsTotal);
        }
    });
    
    // Connect the custom search box to DataTables
    $('#search-input').on('keyup', function() {
        table.search(this.value).draw();
    });
    
    // Connect the entries select to DataTables
    $('#entries-select').on('change', function() {
        table.page.len(parseInt(this.value, 10)).draw();
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
                            Swal.fire({
                                icon: 'success',
                                title: 'Terhapus!',
                                text: response.message,
                                timer: 2000,
                                timerProgressBar: true
                            });
                            table.ajax.reload();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Terjadi kesalahan saat menghapus data'
                        });
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