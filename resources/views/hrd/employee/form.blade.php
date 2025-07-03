@extends('layouts.hrd.app')
@section('title', 'HRD | ' . (isset($employee) ? 'Edit' : 'Tambah') . ' Karyawan')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container py-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title m-0">
                @if(isset($employee))
                    <i class="fas fa-user-edit mr-2"></i>Edit Karyawan
                @else
                    <i class="fas fa-user-plus mr-2"></i>Tambah Karyawan
                @endif
            </h3>
        </div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            <form id="employee-form" 
                action="{{ isset($employee) ? route('hrd.employee.update', $employee->id) : route('hrd.employee.store') }}" 
                method="POST" 
                enctype="multipart/form-data">
                @csrf
                @if(isset($employee))
                    @method('PUT')
                @endif
                
                <div class="row">
                    <div class="col-12 mb-3">
                        <h5 class="border-bottom pb-2">Data Pribadi</h5>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nama"><i class="fas fa-user mr-1"></i>Nama</label>
                            <input type="text" id="nama" name="nama" class="form-control" value="{{ old('nama', $employee->nama ?? '') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nik"><i class="fas fa-id-card mr-1"></i>NIK</label>
                            <input type="text" id="nik" name="nik" class="form-control" value="{{ old('nik', $employee->nik ?? '') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="no_induk"><i class="fas fa-fingerprint mr-1"></i>No Induk</label>
                            <input type="text" id="no_induk" name="no_induk" class="form-control" value="{{ old('no_induk', $employee->no_induk ?? '') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tempat_lahir">Tempat Lahir</label>
                            <input type="text" id="tempat_lahir" name="tempat_lahir" class="form-control" value="{{ old('tempat_lahir', $employee->tempat_lahir ?? '') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tanggal_lahir">Tanggal Lahir</label>
                            <input type="date" id="tanggal_lahir" name="tanggal_lahir" class="form-control" 
                                value="{{ old('tanggal_lahir', isset($employee->tanggal_lahir) ? $employee->tanggal_lahir->format('Y-m-d') : '') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="alamat">Alamat</label>
                            <textarea id="alamat" name="alamat" class="form-control" rows="3" required>{{ old('alamat', $employee->alamat ?? '') }}</textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="no_hp"><i class="fas fa-phone mr-1"></i>No HP</label>
                            <input type="text" id="no_hp" name="no_hp" class="form-control" value="{{ old('no_hp', $employee->no_hp ?? '') }}" required>
                        </div>
                    </div>

                    <div class="col-12 mt-4 mb-3">
                        <h5 class="border-bottom pb-2">Data Kepegawaian</h5>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="position">Posisi</label>
                            <select name="position" id="position" class="form-control select2" required>
                                <option value="">-- Pilih Posisi --</option>
                                @foreach($positions as $position)
                                    <option value="{{ $position->id }}" {{ old('position', $employee->position ?? '') == $position->id ? 'selected' : '' }}>
                                        {{ $position->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="division_id">Divisi</label>
                            <select name="division_id" id="division_id" class="form-control select2" required>
                                <option value="">-- Pilih Divisi --</option>
                                @foreach($divisions as $division)
                                    <option value="{{ $division->id }}" {{ old('division_id', $employee->division_id ?? '') == $division->id ? 'selected' : '' }}>
                                        {{ $division->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="pendidikan">Pendidikan</label>
                            <input type="text" id="pendidikan" name="pendidikan" class="form-control" value="{{ old('pendidikan', $employee->pendidikan ?? '') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tanggal_masuk">Tanggal Masuk</label>
                            <input type="date" id="tanggal_masuk" name="tanggal_masuk" class="form-control" 
                                value="{{ old('tanggal_masuk', isset($employee->tanggal_masuk) ? $employee->tanggal_masuk->format('Y-m-d') : '') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control" required>
                                <option value="">-- Pilih Status --</option>
                                <option value="tetap" {{ old('status', $employee->status ?? '') == 'tetap' ? 'selected' : '' }}>Tetap</option>
                                <option value="kontrak" {{ old('status', $employee->status ?? '') == 'kontrak' ? 'selected' : '' }}>Kontrak</option>
                                <option value="tidak aktif" {{ old('status', $employee->status ?? '') == 'tidak aktif' ? 'selected' : '' }}>Tidak Aktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="durasi_kontrak">Durasi Kontrak (bulan)</label>
                            <input type="number" id="durasi_kontrak" name="durasi_kontrak" class="form-control" min="1" max="60" 
                                value="{{ old('durasi_kontrak', isset($employee->kontrak_berakhir) && isset($employee->tanggal_masuk) ? 
                                round(($employee->kontrak_berakhir->timestamp - $employee->tanggal_masuk->timestamp) / (30 * 24 * 60 * 60)) : '') }}">
                            <input type="hidden" id="kontrak_berakhir" name="kontrak_berakhir" value="{{ old('kontrak_berakhir', isset($employee->kontrak_berakhir) ? $employee->kontrak_berakhir->format('Y-m-d') : '') }}">
                        </div>
                    </div>
                    
                    <div class="col-12 mt-4 mb-3">
                        <h5 class="border-bottom pb-2">Dokumen</h5>
                    </div>

                    @php
                        $documents = [
                            'doc_cv' => 'CV', 
                            'doc_ktp' => 'KTP', 
                            'doc_kontrak' => 'Kontrak', 
                            'doc_pendukung' => 'Dokumen Pendukung'
                        ];
                    @endphp
                    
                    @foreach ($documents as $field => $label)
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="{{ $field }}">{{ $label }}</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="{{ $field }}" name="{{ $field }}">
                                <label class="custom-file-label" for="{{ $field }}">
                                    @if(isset($employee) && $employee->{$field})
                                        {{ basename($employee->{$field}) }}
                                    @else
                                        Pilih file
                                    @endif
                                </label>
                            </div>
                            @if(isset($employee) && $employee->{$field})
                            <div class="mt-2">
                                <a href="{{ asset('storage/' . $employee->{$field}) }}" class="btn btn-sm btn-info" target="_blank">
                                    <i class="fas fa-file-download"></i> Lihat dokumen
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach

                    @if(!isset($employee))
                    <div class="col-12 mt-4 mb-3">
                        <h5 class="border-bottom pb-2">Akun Pengguna</h5>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="create_account" name="create_account" value="1" {{ old('create_account') ? 'checked' : '' }}>
                            <label class="form-check-label" for="create_account">
                                Buat akun pengguna untuk karyawan ini
                            </label>
                        </div>
                    </div>
                    
                    <div id="account_details" class="col-12 {{ old('create_account') ? '' : 'd-none' }}">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="role">Role</label>
                                    <select name="role" id="role" class="form-control">
                                        <option value="">-- Pilih Role --</option>
                                        @foreach(Spatie\Permission\Models\Role::all() as $r)
                                            <option value="{{ $r->name }}" {{ old('role') == $r->name ? 'selected' : '' }}>
                                                {{ ucfirst($r->name) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-1"></i> Password akan dibuat secara otomatis dan ditampilkan setelah karyawan berhasil dibuat.
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                
                <div class="form-group mt-4 text-center">
                    <button type="submit" class="btn btn-success px-4" id="save-btn">
                        <i class="fas fa-save mr-2"></i>{{ isset($employee) ? 'Update' : 'Simpan' }}
                    </button>
                    <a href="{{ route('hrd.employee.index') }}" class="btn btn-secondary ml-2">
                        <i class="fas fa-arrow-left mr-2"></i>Kembali
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function() {
    // Initialize select2
    $('.select2').select2({
        width: '100%',
    });
    
    // Custom file input handling
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).siblings('.custom-file-label').addClass('selected').html(fileName);
    });
    
    // Calculate contract end date based on duration
    function calculateEndDate() {
        var startDate = $('#tanggal_masuk').val();
        var duration = $('#durasi_kontrak').val();
        
        if (startDate && duration) {
            var start = new Date(startDate);
            // Add months to the start date
            var end = new Date(start);
            end.setMonth(end.getMonth() + parseInt(duration));
            
            // Format date to YYYY-MM-DD for the hidden input
            var year = end.getFullYear();
            var month = String(end.getMonth() + 1).padStart(2, '0');
            var day = String(end.getDate()).padStart(2, '0');
            
            $('#kontrak_berakhir').val(`${year}-${month}-${day}`);
        }
    }
    
    // Calculate end date when duration or start date changes
    $('#durasi_kontrak, #tanggal_masuk').on('change', function() {
        calculateEndDate();
    });
    
    // Calculate initial end date if both fields have values
    if ($('#tanggal_masuk').val() && $('#durasi_kontrak').val()) {
        calculateEndDate();
    }
    
    // Toggle account details visibility
    $('#create_account').on('change', function() {
        if(this.checked) {
            $('#account_details').removeClass('d-none');
        } else {
            $('#account_details').addClass('d-none');
        }
    });
    
    // Form submission with AJAX
    $('#employee-form').on('submit', function(e) {
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
                $('#save-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message || 'Data karyawan berhasil disimpan',
                        timer: 2000,
                        timerProgressBar: true
                    }).then(function() {
                        window.location.href = response.redirect;
                    });
                    
                    // Show password if generated
                    @if(session('generated_password'))
                    Swal.fire({
                        icon: 'info',
                        title: 'Password Akun',
                        text: 'Password sementara: {{ session('generated_password') }}',
                        confirmButtonText: 'Salin Password',
                        showCancelButton: true,
                        cancelButtonText: 'Tutup'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            navigator.clipboard.writeText('{{ session('generated_password') }}');
                            Swal.fire({
                                icon: 'success',
                                title: 'Password disalin!',
                                showConfirmButton: false,
                                timer: 1500
                            });
                        }
                    });
                    @endif
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Terjadi kesalahan saat menyimpan data'
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
                var isUpdate = {{ isset($employee) ? 'true' : 'false' }};
                $('#save-btn').prop('disabled', false).html('<i class="fas fa-save mr-2"></i>' + 
                    (isUpdate ? 'Update' : 'Simpan'));
            }
        });
    });
});
</script>
@endsection
