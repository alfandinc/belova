@extends('layouts.erm.app')

@section('title', 'ERM | E-Laboratorium')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection

@section('content')

@include('erm.partials.modal-alergipasien')

<div class="container-fluid">
    <div class="d-flex align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">E-Laboratorium</h3>
    </div>
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Rawat Jalan</li>
                            <li class="breadcrumb-item active">E-Lab</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->  
    <!-- end page title end breadcrumb -->
    @include('erm.partials.card-identitaspasien')

    <!-- Two column layout for Lab Management -->
    <div class="row">
        <!-- Left Column - Permintaan Lab -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center ">
                    <h5 class="mb-0"><i class="fa fa-flask mr-2"></i> Permintaan Laboratorium</h5>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="kategoriFilter">Filter Kategori:</label>
                        <select id="kategoriFilter" class="form-control select2">
                            <option value="">-- Semua Kategori --</option>
                            @foreach($labCategories as $category)
                                <option value="{{ $category->id }}">{{ $category->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="table-responsive mt-3">
                        <table id="permintaanLabTable" class="table table-bordered table-hover w-100">
                            <thead class="thead-light">
                                <tr>
                                    <th>Nama Pemeriksaan</th>
                                    <th>Kategori</th>
                                    <th>Harga</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded via DataTables -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column - Riwayat Lab -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa fa-file-medical-alt mr-2"></i> Riwayat Permintaan Lab</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="riwayatLabTable" class="table table-bordered table-hover w-100">
                            <thead class="thead-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Pemeriksaan</th>
                                    <th>Kategori</th>
                                    <th>Status</th>
                                    <th>Hasil</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded via DataTables -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>  
$(document).ready(function () {
    // Initialize select2
    $('.select2').select2();
    
    // Initialize DataTables
    let permintaanTable = $('#permintaanLabTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("erm.elab.tests.data") }}',
            data: function (d) {
                d.kategori_id = $('#kategoriFilter').val();
            }
        },
        columns: [
            { data: 'nama', name: 'nama' },
            { data: 'kategori', name: 'kategori' },
            { data: 'harga_formatted', name: 'harga' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data per halaman",
            zeroRecords: "Tidak ada data yang ditemukan",
            info: "Menampilkan halaman _PAGE_ dari _PAGES_",
            infoEmpty: "Tidak ada data tersedia",
            infoFiltered: "(difilter dari _MAX_ total data)"
        }
    });
    
    let riwayatTable = $('#riwayatLabTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("erm.elab.requests.data", $visitation->id) }}',
        columns: [
            { data: 'tanggal', name: 'created_at' },
            { data: 'nama_pemeriksaan', name: 'nama_pemeriksaan' },
            { data: 'kategori', name: 'kategori' },
            { data: 'status_label', name: 'status' },
            { data: 'hasil_text', name: 'hasil' }
        ],
        order: [[0, 'desc']],
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data per halaman",
            zeroRecords: "Tidak ada data yang ditemukan",
            info: "Menampilkan halaman _PAGE_ dari _PAGES_",
            infoEmpty: "Tidak ada data tersedia",
            infoFiltered: "(difilter dari _MAX_ total data)"
        }
    });
    
    // Filter by category when selection changes
    $('#kategoriFilter').change(function() {
        permintaanTable.ajax.reload();
    });
    
    // Handle request lab button click using delegation (for dynamically created elements)
    $('#permintaanLabTable').on('click', '.btn-permintaan-lab', function() {
        let testId = $(this).data('id');
        
        $.ajax({
            url: '{{ route("erm.elab.store") }}',
            type: 'POST',
            data: {
                visitation_id: '{{ $visitation->id }}',
                lab_test_id: testId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    
                    // Reload riwayat table
                    riwayatTable.ajax.reload();
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Terjadi kesalahan! Silakan coba lagi.'
                });
                console.error(xhr.responseText);
            }
        });
    });
});
</script>    
@endsection