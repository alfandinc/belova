@extends('layouts.hrd.app')
@section('title', 'HRD | Tambah Karyawan')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container py-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title m-0"><i class="fas fa-user-plus mr-2"></i>Tambah Karyawan</h3>
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
            
            <form id="employee-form" action="{{ route('hrd.employee.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="row">
                    <div class="col-12 mb-3">
                        <h5 class="border-bottom pb-2">Data Pribadi</h5>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nama"><i class="fas fa-user mr-1"></i>Nama</label>
                            <input type="text" id="nama" name="nama" class="form-control" value="{{ old('nama') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nik"><i class="fas fa-id-card mr-1"></i>NIK</label>
                            <input type="text" id="nik" name="nik" class="form-control" value="{{ old('nik') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tempat_lahir">Tempat Lahir</label>
                            <input type="text" id="tempat_lahir" name="tempat_lahir" class="form-control" value="{{ old('tempat_lahir') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tanggal_lahir">Tanggal Lahir</label>
                            <input type="date" id="tanggal_lahir" name="tanggal_lahir" class="form-control" value="{{ old('tanggal_lahir') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="alamat">Alamat</label>
                            <textarea id="alamat" name="alamat" class="form-control" rows="3" required>{{ old('alamat') }}</textarea>
                        </div>
                    </div>
                    {{-- <div class="col-md-6">
                        <div class="form-group">
                            <label for="village_id">Desa</label>
                            <select id="village_id" name="village_id" class="form-control select2">
                                <option value="">Pilih Desa</option>
                                @foreach ($villages as $village)
                                    <option value="{{ $village->id }}" {{ old('village_id') == $village->id ? 'selected' : '' }}>{{ $village->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div> --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="no_hp"><i class="fas fa-phone mr-1"></i>No HP</label>
                            <input type="text" id="no_hp" name="no_hp" class="form-control" value="{{ old('no_hp') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="pendidikan"><i class="fas fa-graduation-cap mr-1"></i>Pendidikan</label>
                            <input type="text" id="pendidikan" name="pendidikan" class="form-control" value="{{ old('pendidikan') }}" required>
                        </div>
                    </div>

                    <div class="col-12 mt-4 mb-3">
                        <h5 class="border-bottom pb-2">Data Kepegawaian</h5>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="position">Posisi</label>
                            <select id="position" name="position" class="form-control select2" required>
                                <option value="">Pilih Posisi</option>
                                @foreach ($positions as $position)
                                    <option value="{{ $position->id }}" {{ old('position') == $position->id ? 'selected' : '' }}>{{ $position->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="division">Divisi</label>
                            <select id="division_id" name="division_id" class="form-control select2" required>
                                <option value="">Pilih Divisi</option>
                                @foreach ($divisions as $division)
                                    <option value="{{ $division->id }}" {{ old('division') == $division->id ? 'selected' : '' }}>{{ $division->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="form-control select2" required>
                                <option value="">Pilih Status</option>
                                @foreach (['tetap', 'kontrak', 'tidak aktif'] as $status)
                                    <option value="{{ $status }}" {{ old('status') == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tanggal_masuk">Tanggal Masuk</label>
                            <input type="date" id="tanggal_masuk" name="tanggal_masuk" class="form-control" value="{{ old('tanggal_masuk') }}" required>
                        </div>
                    </div>                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="durasi_kontrak">Durasi Kontrak (bulan)</label>
                            <input type="number" id="durasi_kontrak" name="durasi_kontrak" class="form-control" min="1" max="60" value="{{ old('durasi_kontrak') }}">
                            <input type="hidden" id="kontrak_berakhir" name="kontrak_berakhir" value="{{ old('kontrak_berakhir') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="masa_pensiun">Masa Pensiun</label>
                            <input type="date" id="masa_pensiun" name="masa_pensiun" class="form-control" value="{{ old('masa_pensiun') }}">
                        </div>
                    </div>
                    
                    <div class="col-12 mt-4 mb-3">
                        <h5 class="border-bottom pb-2">Dokumen</h5>
                    </div>

                    @foreach (['doc_cv' => 'CV', 'doc_ktp' => 'KTP', 'doc_kontrak' => 'Kontrak', 'doc_pendukung' => 'Dokumen Pendukung'] as $field => $label)
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="{{ $field }}">{{ $label }}</label>
                            <div class="custom-file">
                                <input type="file" name="{{ $field }}" class="custom-file-input" id="{{ $field }}">
                                <label class="custom-file-label" for="{{ $field }}">Choose file</label>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    
                    <div class="col-12 mt-4 mb-3">
                        <h5 class="border-bottom pb-2">Akun Pengguna</h5>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="create_account" name="create_account" value="1" {{ old('create_account') ? 'checked' : '' }}>
                            <label class="form-check-label" for="create_account">Buat akun login untuk karyawan</label>
                        </div>
                    </div>
                    
                    <div id="account_details" class="col-12 {{ old('create_account') ? '' : 'd-none' }}">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email"><i class="fas fa-envelope mr-1"></i>Email</label>
                                    <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="role"><i class="fas fa-user-tag mr-1"></i>Role</label>
                                    <select id="role" name="role" class="form-control select2">
                                        <option value="employee" {{ old('role') == 'employee' ? 'selected' : '' }}>Karyawan</option>
                                        <option value="manager" {{ old('role') == 'manager' ? 'selected' : '' }}>Manager</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group mt-4 text-center">
                    <button type="submit" class="btn btn-success px-4" id="save-btn">
                        <i class="fas fa-save mr-2"></i>Simpan
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
    
    // Toggle account details visibility
    $('#create_account').on('change', function() {
        console.log("Checkbox changed:", this.checked);
        if(this.checked) {
            $('#account_details').removeClass('d-none');
        } else {
            $('#account_details').addClass('d-none');
        }
    });
    
    // Check initial state of checkbox on page load
    if($('#create_account').is(':checked')) {
        $('#account_details').removeClass('d-none');
    }
    
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
                        text: response.message,
                        timer: 3000,
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
                            Swal.fire('Disalin!', 'Password telah disalin ke clipboard', 'success');
                        }
                    });
                    @endif
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
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
                $('#save-btn').prop('disabled', false).html('<i class="fas fa-save mr-2"></i>Simpan');
            }
        });
    });
});
</script>
@endsection