@extends('layouts.hrd.app')
@section('title', 'HRD | Edit Karyawan')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container py-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title m-0"><i class="fas fa-user-edit mr-2"></i>Edit Karyawan</h3>
        </div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form id="employee-form" action="{{ route('hrd.employee.update', $employee->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')                <div class="row">
                    <div class="col-12 mb-3">
                        <h5 class="border-bottom pb-2">Data Pribadi</h5>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nama"><i class="fas fa-user mr-1"></i>Nama</label>
                            <input type="text" id="nama" name="nama" class="form-control" value="{{ old('nama', $employee->nama) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nik"><i class="fas fa-id-card mr-1"></i>NIK</label>
                            <input type="text" id="nik" name="nik" class="form-control" value="{{ old('nik', $employee->nik) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="no_induk"><i class="fas fa-fingerprint mr-1"></i>No Induk</label>
                            <input type="text" id="no_induk" name="no_induk" class="form-control" value="{{ old('no_induk', $employee->no_induk) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tempat_lahir">Tempat Lahir</label>
                            <input type="text" id="tempat_lahir" name="tempat_lahir" class="form-control" value="{{ old('tempat_lahir', $employee->tempat_lahir) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tanggal_lahir">Tanggal Lahir</label>
                            <input type="date" id="tanggal_lahir" name="tanggal_lahir" class="form-control" value="{{ old('tanggal_lahir', $employee->tanggal_lahir) }}" required>
                        </div>
                    </div>                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="alamat">Alamat</label>
                            <textarea id="alamat" name="alamat" class="form-control" rows="3" required>{{ old('alamat', $employee->alamat) }}</textarea>
                        </div>
                    </div>

                    <div class="col-12 mt-4 mb-3">
                        <h5 class="border-bottom pb-2">Data Kepegawaian</h5>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="kategori_pegawai">Kategori Pegawai</label>
                            <select id="kategori_pegawai" name="kategori_pegawai" class="form-control select2">
                                <option value="">-- Pilih Kategori --</option>
                                <option value="medis" {{ old('kategori_pegawai', $employee->kategori_pegawai) == 'medis' ? 'selected' : '' }}>Medis</option>
                                <option value="non-medis" {{ old('kategori_pegawai', $employee->kategori_pegawai) == 'non-medis' ? 'selected' : '' }}>Non-Medis</option>
                                <option value="manajemen" {{ old('kategori_pegawai', $employee->kategori_pegawai) == 'manajemen' ? 'selected' : '' }}>Manajemen</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="position">Posisi</label>
                            <select name="position" id="position" class="form-control select2" required>
                                <option value="">Pilih Posisi</option>
                                @foreach ($positions as $position)
                                    <option value="{{ $position->id }}" {{ old('position', $employee->position) == $position->id ? 'selected' : '' }}>{{ $position->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="division_id">Divisi</label>
                            <select name="division_id" id="division_id" class="form-control select2" required>
                                <option value="">Pilih Divisi</option>
                                @foreach ($divisions as $division)
                                    <option value="{{ $division->id }}" {{ old('division_id', $employee->division_id) == $division->id ? 'selected' : '' }}>{{ $division->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="pendidikan">Pendidikan</label>
                            <input type="text" id="pendidikan" name="pendidikan" class="form-control" value="{{ old('pendidikan', $employee->pendidikan) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="no_hp">No HP</label>
                            <input type="text" id="no_hp" name="no_hp" class="form-control" value="{{ old('no_hp', $employee->no_hp) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control" required>
                                @foreach (['tetap', 'kontrak', 'tidak aktif'] as $status)
                                    <option value="{{ $status }}" {{ old('status', $employee->status) == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tanggal_masuk">Tanggal Masuk</label>
                            <input type="date" id="tanggal_masuk" name="tanggal_masuk" class="form-control" value="{{ old('tanggal_masuk', $employee->tanggal_masuk) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="durasi_kontrak">Durasi Kontrak (bulan)</label>
                            <input type="number" id="durasi_kontrak" name="durasi_kontrak" class="form-control" min="1" max="60" 
                                value="{{ old('durasi_kontrak', $employee->kontrak_berakhir ? round((strtotime($employee->kontrak_berakhir) - strtotime($employee->tanggal_masuk)) / (30 * 24 * 60 * 60)) : '') }}">
                            <input type="hidden" id="kontrak_berakhir" name="kontrak_berakhir" value="{{ old('kontrak_berakhir', $employee->kontrak_berakhir) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="masa_pensiun">Masa Pensiun</label>
                            <input type="date" id="masa_pensiun" name="masa_pensiun" class="form-control" value="{{ old('masa_pensiun', $employee->masa_pensiun) }}">
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
                                <input type="file" name="{{ $field }}" id="{{ $field }}" class="custom-file-input">
                                <label class="custom-file-label" for="{{ $field }}">Pilih file</label>
                            </div>
                            @if ($employee->$field)
                                <small class="form-text text-muted">
                                    <a href="{{ asset('storage/' . $employee->$field) }}" target="_blank">
                                        <i class="fas fa-file-alt mr-1"></i>Lihat {{ $label }}
                                    </a>
                                </small>
                            @endif
                        </div>
                    </div>
                    @endforeach                </div>
                
                <div class="form-group mt-4 text-center">
                    <button type="submit" class="btn btn-success px-4" id="save-btn">
                        <i class="fas fa-save mr-2"></i>Update
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
                        text: response.message || 'Data karyawan berhasil diperbarui',
                        timer: 2000,
                        timerProgressBar: true
                    }).then(function() {
                        window.location.href = response.redirect;
                    });
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
                $('#save-btn').prop('disabled', false).html('<i class="fas fa-save mr-2"></i>Update');
            }
        });
    });
});
</script>
@endsection
