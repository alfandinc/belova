@extends('layouts.hrd.app')
@section('title', 'HRD | Riwayat Kontrak Karyawan')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container py-4">
    <div class="card shadow">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h3 class="card-title m-0 font-weight-bold text-primary">
                Riwayat Kontrak - {{ $employee->nama }}
            </h3>
            <div>
                <a href="{{ route('hrd.employee.index') }}" class="btn btn-secondary mr-2">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali
                </a>
                @if($employee->status == 'kontrak' || $employee->status == 'tidak aktif')
                <button type="button" class="btn btn-primary" id="create-contract-btn">
                    <i class="fas fa-plus mr-1"></i> Perpanjang Kontrak
                </button>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="employee-info mb-4 p-3 bg-light rounded">
                <div class="row">
                    <div class="col-md-4">
                        <p class="mb-1"><strong>NIK:</strong> {{ $employee->nik }}</p>
                        <p class="mb-1"><strong>No Induk:</strong> {{ $employee->no_induk }}</p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1"><strong>Posisi:</strong> {{ optional($employee->position)->name ?? '-' }}</p>
                        <p class="mb-1"><strong>Divisi:</strong> {{ optional($employee->division)->name ?? '-' }}</p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1"><strong>Tanggal Masuk:</strong> {{ $employee->tanggal_masuk ? $employee->tanggal_masuk->format('d M Y') : '-' }}</p>
                        <p class="mb-1">
                            <strong>Status:</strong> 
                            <span class="badge badge-{{ $employee->status == 'tetap' ? 'success' : ($employee->status == 'kontrak' ? 'warning' : 'danger') }}">
                                {{ ucfirst($employee->status) }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
            
            @if($contracts->count() > 0)
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th width="5%">No</th>
                            <th width="15%">Tanggal Mulai</th>
                            <th width="15%">Tanggal Berakhir</th>
                            <th width="10%">Durasi</th>
                            <th width="15%">Status</th>
                            <th width="20%">Dokumen</th>
                            <th width="20%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contracts as $index => $contract)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $contract->start_date->format('d M Y') }}</td>
                            <td>{{ $contract->end_date->format('d M Y') }}</td>
                            <td>{{ $contract->duration_months }} bulan</td>
                            <td>
                                @php
                                    $statusBadge = '';
                                    switch($contract->status) {
                                        case 'active':
                                            $statusBadge = 'success';
                                            $statusText = 'Aktif';
                                            break;
                                        case 'renewed':
                                            $statusBadge = 'info';
                                            $statusText = 'Diperpanjang';
                                            break;
                                        case 'expired':
                                            $statusBadge = 'warning';
                                            $statusText = 'Habis';
                                            break;
                                        case 'terminated':
                                            $statusBadge = 'danger';
                                            $statusText = 'Diputus';
                                            break;
                                    }
                                @endphp
                                <span class="badge badge-{{ $statusBadge }}">{{ $statusText }}</span>
                            </td>
                            <td>
                                @if($contract->contract_document)
                                <a href="{{ asset('storage/' . $contract->contract_document) }}" class="btn btn-sm btn-info" target="_blank">
                                    <i class="fas fa-file-download mr-1"></i> Dokumen Kontrak
                                </a>
                                @else
                                <span class="text-muted">Tidak ada dokumen</span>
                                @endif
                            </td>
                            <td>
                                                <button type="button" class="btn btn-sm btn-info view-contract" 
                                        data-id="{{ $contract->id }}"
                                        data-employee="{{ $employee->id }}">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @if($contract->status == 'active')
                                <button type="button" class="btn btn-sm btn-danger terminate-contract" 
                                        data-id="{{ $contract->id }}"
                                        data-employee="{{ $employee->id }}">
                                    <i class="fas fa-times"></i> Putus Kontrak
                                </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-1"></i> Belum ada data kontrak untuk karyawan ini.
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Terminate Contract Modal -->
<div class="modal fade" id="terminateContractModal" tabindex="-1" role="dialog" aria-labelledby="terminateContractModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="terminateContractModalLabel"><i class="fas fa-exclamation-triangle mr-2"></i>Putus Kontrak</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="terminateContractForm">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-circle mr-1"></i> Pemutusan kontrak akan mengubah status karyawan menjadi tidak aktif. Tindakan ini tidak dapat dibatalkan.
                    </div>
                    <div class="form-group">
                        <label for="termination_notes">Alasan Pemutusan Kontrak <span class="text-danger">*</span></label>
                        <textarea id="termination_notes" name="termination_notes" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger" id="confirm-terminate-btn">
                        <i class="fas fa-check mr-1"></i>Konfirmasi Pemutusan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Contract Modal (for both create and show) -->
<div class="modal fade" id="contractModal" tabindex="-1" role="dialog" aria-labelledby="contractModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="contractModalLabel"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat...</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat data...</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .employee-info {
        border-left: 4px solid #4e73df;
    }
    
    .table td, .table th {
        vertical-align: middle;
    }
</style>
@endpush

@section('scripts')
<script>
$(function() {
    // Handle contract termination
    $('.terminate-contract').on('click', function() {
        var contractId = $(this).data('id');
        var employeeId = $(this).data('employee');
        
        // Set form action URL
        $('#terminateContractForm').attr('action', `/hrd/employee/${employeeId}/contracts/${contractId}/terminate`);
        
        // Show the modal
        $('#terminateContractModal').modal('show');
    });
    
    // Handle termination form submission
    $('#terminateContractForm').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var formData = new FormData(this);
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#confirm-terminate-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memproses...');
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message || 'Kontrak berhasil diputus',
                        timer: 2000,
                        timerProgressBar: true
                    }).then(function() {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Terjadi kesalahan saat memutus kontrak'
                    });
                }
            },
            error: function(xhr) {
                var errors = xhr.responseJSON.errors;
                var errorMessage = '';
                
                $.each(errors, function(key, value) {
                    errorMessage += value + '<br>';
                });
                
                Swal.fire({
                    icon: 'error',
                    title: 'Validasi Error',
                    html: errorMessage
                });
            },
            complete: function() {
                $('#confirm-terminate-btn').prop('disabled', false).html('<i class="fas fa-check mr-1"></i>Konfirmasi Pemutusan');
                $('#terminateContractModal').modal('hide');
            }
        });
    });
    
    // Handle "Create Contract" button click
    $('#create-contract-btn').on('click', function() {
        var employeeId = {{ $employee->id }};
        
        // Reset modal content
        $('#contractModal .modal-content').html(`
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="contractModalLabel"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat...</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat data...</p>
                </div>
            </div>
        `);
        
        // Show the modal
        $('#contractModal').modal('show');
        
        // Fetch the contract create form via AJAX
        $.ajax({
            url: `/hrd/employee/${employeeId}/contracts/modal/create`,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    $('#contractModal .modal-content').html(response.html);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Terjadi kesalahan saat memuat form'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Terjadi kesalahan saat memuat form'
                });
            }
        });
    });
    
    // Handle view contract button click
    $('.view-contract').on('click', function() {
        var contractId = $(this).data('id');
        var employeeId = $(this).data('employee');
        
        // Reset modal content
        $('#contractModal .modal-content').html(`
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="contractModalLabel"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat...</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat data...</p>
                </div>
            </div>
        `);
        
        // Show the modal
        $('#contractModal').modal('show');
        
        // Fetch the contract details via AJAX
        $.ajax({
            url: `/hrd/employee/${employeeId}/contracts/${contractId}/modal`,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    $('#contractModal .modal-content').html(response.html);
                    
                    // Re-bind terminate button in the modal
                    $('#contractModal .terminate-contract').on('click', function() {
                        var contractId = $(this).data('id');
                        var employeeId = $(this).data('employee');
                        
                        $('#contractModal').modal('hide');
                        
                        // Set form action URL
                        $('#terminateContractForm').attr('action', `/hrd/employee/${employeeId}/contracts/${contractId}/terminate`);
                        
                        // Show the termination modal
                        setTimeout(function() {
                            $('#terminateContractModal').modal('show');
                        }, 500);
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Terjadi kesalahan saat memuat data kontrak'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Terjadi kesalahan saat memuat data kontrak'
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
    
    // Show info message if exists
    @if(session('info'))
    Swal.fire({
        icon: 'info',
        title: 'Informasi',
        text: '{{ session('info') }}',
        timer: 3000,
        timerProgressBar: true
    });
    @endif
});
</script>
@endsection
