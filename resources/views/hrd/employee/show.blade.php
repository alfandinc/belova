@extends('layouts.hrd.app')
@section('title', 'HRD | Profil Karyawan')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container">
    <div class="row">
        <!-- Profile Card -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Profil Karyawan</h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <img src="{{ asset('assets/images/default-profile.jpg') }}" alt="Profile" class="img-fluid rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                    </div>
                    <h4>{{ $employee->nama }}</h4>
                    <p class="text-muted">{{ $employee->position->name ?? '-' }}</p>
                    <p><span class="badge badge-{{ $employee->status == 'tetap' ? 'success' : ($employee->status == 'kontrak' ? 'warning' : 'danger') }}">{{ ucfirst($employee->status) }}</span></p>
                    
                    <div class="mt-3">
                        <a href="{{ route('hrd.employee.profile.edit') }}" class="btn btn-sm btn-primary">Edit Profil</a>
                        <a href="{{ route('hrd.employee.password.change') }}" class="btn btn-sm btn-secondary">Ubah Password</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Employee Details -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Data Karyawan</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>NIK:</strong> {{ $employee->nik }}</p>
                            <p><strong>Tempat, Tanggal Lahir:</strong> {{ $employee->tempat_lahir }}, {{ $employee->tanggal_lahir->format('d-m-Y') }}</p>
                            <p><strong>Pendidikan:</strong> {{ $employee->pendidikan }}</p>
                            <p><strong>Divisi:</strong> {{ $employee->division->name ?? '-' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Tanggal Masuk:</strong> {{ $employee->tanggal_masuk->format('d-m-Y') }}</p>
                            <p><strong>No. HP:</strong> {{ $employee->no_hp }}</p>
                            <p><strong>Alamat:</strong> {{ $employee->alamat }}</p>
                            <p><strong>Desa:</strong> {{ $employee->village->name ?? '-' }}</p>
                        </div>
                    </div>
                    
                    @if($employee->status == 'kontrak')
                    <div class="alert alert-warning mt-3">
                        <strong>Kontrak berakhir:</strong> {{ $employee->kontrak_berakhir ? $employee->kontrak_berakhir->format('d-m-Y') : 'Belum diatur' }}
                    </div>
                    @endif
                    
                    @if(auth()->user()->hasRole('manager'))
                    <div class="mt-4">
                        <h5>Tim Saya</h5>
                        <a href="{{ route('hrd.division.team') }}" class="btn btn-outline-primary">Lihat Tim</a>
                    </div>
                    @endif
                </div>
            </div>
            
            <!-- Documents -->
            <div class="card mt-3">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Dokumen</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        @foreach(['doc_cv' => 'CV', 'doc_ktp' => 'KTP', 'doc_kontrak' => 'Kontrak', 'doc_pendukung' => 'Dokumen Pendukung'] as $doc => $label)
                            @if($employee->$doc)
                            <a href="{{ asset('storage/'.$employee->$doc) }}" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-file-pdf mr-2"></i> {{ $label }}</span>
                                <span class="badge badge-primary badge-pill">Lihat</span>
                            </a>
                            @else
                            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center text-muted">
                                <span><i class="fas fa-file mr-2"></i> {{ $label }}</span>
                                <span class="badge badge-secondary badge-pill">Belum ada</span>
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')

<script>
$(function() {
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
    
    // Show error message if exists
    @if(session('error'))
    Swal.fire({
        icon: 'error',
        title: 'Error!',
        text: '{{ session('error') }}',
        timer: 3000,
        timerProgressBar: true
    });
    @endif
});
</script>
@endsection