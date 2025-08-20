
@extends('layouts.hrd.app')
@section('title', 'HRD | Profil Karyawan')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
{{-- @include('hrd.partials.modal-edit-profile') --}}



<div class="container py-4">
    <div class="row">
        <!-- Profile Card -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    @if($employee->photo)
        <img src="{{ asset('storage/'.$employee->photo) }}" alt="Profile" 
             class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover;">
    @else
        <img src="{{ asset('assets/images/default-profile.jpg') }}" alt="Profile" 
             class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover;">
    @endif
                        
                    <h4 class="mb-1">{{ $employee->nama }}</h4>
                    <p class="text-muted mb-2">{{ $employee->position->name ?? '-' }}</p>
                    <span class="badge badge-{{ $employee->status == 'tetap' ? 'success' : ($employee->status == 'kontrak' ? 'warning' : 'danger') }}">
                        {{ ucfirst($employee->status) }}
                    </span>
                    <div class="mt-4">
                        <button type="button" class="btn btn-primary btn-block mb-2" id="btnEditProfile">
                            <i class="fas fa-user-edit"></i> Edit Profil
                        </button>
                        <button type="button" class="btn btn-secondary btn-block mb-2" id="btnChangePassword">
                            <i class="fas fa-user-edit"></i> Ganti Password
                        </button>
                        
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Profile Details -->
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Data Karyawan</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nama:</strong> {{ $employee->nama }}</p>
                            <p><strong>NIK:</strong> {{ $employee->nik }}</p>
                            <p><strong>Tempat, Tanggal Lahir:</strong> {{ $employee->tempat_lahir }}, {{ $employee->tanggal_lahir ? \Carbon\Carbon::parse($employee->tanggal_lahir)->format('d-m-Y') : '-' }}</p>
                            <p><strong>Pendidikan:</strong> {{ $employee->pendidikan }}</p>
                            <p><strong>Gol. Darah:</strong> {{ $employee->gol_darah ?? '-' }}</p>
                            <p><strong>Divisi:</strong> {{ $employee->division->name ?? '-' }}</p>
                            <p><strong>Jabatan:</strong> {{ $employee->position->name ?? '-' }}</p>
                            <p><strong>Email:</strong> {{ $employee->email ?? '-' }}</p>
                            <p><strong>Instagram:</strong>
                                @php
                                    $instagrams = is_array($employee->instagram) ? $employee->instagram : json_decode($employee->instagram, true);
                                    $instagrams = array_filter($instagrams); // Remove empty/null values
                                @endphp
                                        @if (!empty($instagrams))
                                            <ul class="mb-0">
                                                @foreach($instagrams as $insta)
                                                    <li>
                                                        @if(Str::startsWith($insta, '@'))
                                                            <a href="https://instagram.com/{{ ltrim($insta, '@') }}" target="_blank">{{ $insta }}</a>
                                                        @else
                                                            <a href="https://instagram.com/{{ $insta }}" target="_blank">{{ '@' . $insta }}</a>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            -
                                        @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Tanggal Masuk:</strong> {{ $employee->tanggal_masuk ? \Carbon\Carbon::parse($employee->tanggal_masuk)->format('d-m-Y') : '-' }}</p>
                            <p><strong>Status:</strong> {{ ucfirst($employee->status) }}</p>
                            <p><strong>No. HP:</strong> {{ $employee->no_hp }}</p>
                            <p><strong>Alamat:</strong> {{ $employee->alamat }}</p>
                            {{-- <p><strong>Desa:</strong> {{ $employee->village->name ?? '-' }}</p> --}}
                        </div>
                    </div>

                    @if($employee->status == 'kontrak')
                    <div class="alert alert-warning mt-3">
                        <strong>Kontrak berakhir:</strong> {{ $employee->kontrak_berakhir ? \Carbon\Carbon::parse($employee->kontrak_berakhir)->format('d-m-Y') : 'Belum diatur' }}
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
            
            <!-- Documents List -->
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Dokumen</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        @foreach(['doc_cv' => 'CV', 'doc_ktp' => 'KTP', 'doc_pendukung' => 'Dokumen Pendukung'] as $doc => $label)
                            @if($employee->$doc)
                            <a href="{{ asset('storage/'.$employee->$doc) }}" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-file-pdf mr-2"></i> {{ $label }}</span>
                                <span class="badge badge-primary badge-pill">Lihat</span>
                            </a>
                            @else
                            <div class="list-group-item d-flex justify-content-between align-items-center text-muted">
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

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" role="dialog" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" id="modalContent">
            <!-- Content will be loaded here -->
            <div class="text-center p-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function() {
    // Load modal content via AJAX
    $('#btnEditProfile').on('click', function() {
        $('#modalContent').html('<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>');
        $('#editProfileModal').modal('show');
        
        $.ajax({
            url: '{{ route("hrd.employee.profile.modal") }}',
            type: 'GET',
            success: function(response) {
                $('#modalContent').html(response);
                
                // Initialize custom file inputs after loading the modal
                $('.custom-file-input').on('change', function() {
                    var fileName = $(this).val().split('\\').pop();
                    $(this).siblings('.custom-file-label').addClass('selected').html(fileName);
                });
                
                // Initialize form submission
                initFormSubmission();
            },
            error: function() {
                $('#modalContent').html('<div class="p-4 text-center text-danger">Error loading form. Please try again.</div>');
            }
        });
    });
    
    // Handle Change Password button click
    $('#btnChangePassword').on('click', function() {
        $('#modalContent').html('<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>');
        $('#editProfileModal').modal('show');
        
        $.ajax({
            url: '{{ route("hrd.employee.profile.modal") }}?mode=password',
            type: 'GET',
            success: function(response) {
                $('#modalContent').html(response);
                initPasswordFormSubmission();
            },
            error: function() {
                $('#modalContent').html('<div class="p-4 text-center text-danger">Error loading form. Please try again.</div>');
            }
        });
    });
    
    // Form submission via AJAX
    function initFormSubmission() {
        $('#profileUpdateForm').on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $('#profileUpdateForm button[type="submit"]').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
                    $('#profileUpdateForm button[type="submit"]').attr('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        $('#editProfileModal').modal('hide');
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message,
                            confirmButtonText: 'OK',
                            timer: 2000,
                            timerProgressBar: true,
                            willClose: function() {
                                window.location.reload();
                            }
                        }).then((result) => {
                            // Reload page when user clicks OK
                            if (result.value) {
                                window.location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message
                        });
                    }
                },
                error: function(xhr) {
                    var errors = xhr.responseJSON.errors;
                    var errorMessage = '';
                    
                    $.each(errors, function(key, value) {
                        errorMessage += value[0] + '<br>';
                    });
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        html: errorMessage
                    });
                    
                    $('#profileUpdateForm button[type="submit"]').html('Simpan Perubahan');
                    $('#profileUpdateForm button[type="submit"]').attr('disabled', false);
                },
                complete: function() {
                    $('#profileUpdateForm button[type="submit"]').html('Simpan Perubahan');
                    $('#profileUpdateForm button[type="submit"]').attr('disabled', false);
                }
            });
        });
    }
    
    // Password form submission
    function initPasswordFormSubmission() {
        $('#passwordChangeForm').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                beforeSend: function() {
                    $('#passwordChangeForm button[type="submit"]').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
                    $('#passwordChangeForm button[type="submit"]').attr('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        $('#editProfileModal').modal('hide');
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message,
                            confirmButtonText: 'OK',
                            timer: 2000,
                            timerProgressBar: true,
                            willClose: function() {
                                window.location.reload();
                            }
                        }).then((result) => {
                            // Reload page when user clicks OK
                            if (result.isConfirmed) {
                                window.location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message
                        });
                    }
                },
                error: function(xhr) {
                    var errors = xhr.responseJSON.errors;
                    var errorMessage = '';
                    
                    $.each(errors, function(key, value) {
                        errorMessage += value[0] + '<br>';
                    });
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        html: errorMessage
                    });
                },
                complete: function() {
                    $('#passwordChangeForm button[type="submit"]').html('Simpan Perubahan');
                    $('#passwordChangeForm button[type="submit"]').attr('disabled', false);
                }
            });
        });
    }

    // Show notifications
    @if(session('success'))
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: '{{ session('success') }}',
        timer: 3000,
        timerProgressBar: true
    });
    @endif

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