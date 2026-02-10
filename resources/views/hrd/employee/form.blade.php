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
                
                <ul class="nav nav-tabs mb-4" id="employeeTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="pribadi-tab" data-toggle="tab" href="#pribadi" role="tab" aria-controls="pribadi" aria-selected="true">Data Pribadi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="kepegawaian-tab" data-toggle="tab" href="#kepegawaian" role="tab" aria-controls="kepegawaian" aria-selected="false">Data Kepegawaian</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="dokumen-tab" data-toggle="tab" href="#dokumen" role="tab" aria-controls="dokumen" aria-selected="false">Dokumen</a>
                    </li>
                </ul>

                <div class="tab-content" id="employeeTabContent">
                    <!-- Data Pribadi Tab -->
                    <div class="tab-pane fade show active" id="pribadi" role="tabpanel" aria-labelledby="pribadi-tab">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="nama">Nama</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    </div>
                                    <input type="text" id="nama" name="nama" class="form-control" value="{{ old('nama', $employee->nama ?? '') }}">
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="nik">NIK</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    </div>
                                    <input type="text" id="nik" name="nik" class="form-control" value="{{ old('nik', $employee->nik ?? '') }}">
                                </div>
                            </div>
                            <!-- No Induk moved to Data Kepegawaian tab -->
                            <div class="form-group col-md-6">
                                <label for="tempat_lahir">Tempat Lahir</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                    </div>
                                    <input type="text" id="tempat_lahir" name="tempat_lahir" class="form-control" value="{{ old('tempat_lahir', $employee->tempat_lahir ?? '') }}">
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="tanggal_lahir">Tanggal Lahir</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                    </div>
                                    <input type="date" id="tanggal_lahir" name="tanggal_lahir" class="form-control" value="{{ old('tanggal_lahir', isset($employee->tanggal_lahir) ? $employee->tanggal_lahir->format('Y-m-d') : '') }}">
                                </div>
                            </div>
                            <!-- Alamat will be moved to the last position below -->
                            <div class="form-group col-md-6">
                                <label for="no_hp">No HP</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    </div>
                                    <input type="text" id="no_hp" name="no_hp" class="form-control" value="{{ old('no_hp', $employee->no_hp ?? '') }}">
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="no_darurat">No Darurat</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-phone-alt"></i></span>
                                    </div>
                                    <input type="text" id="no_darurat" name="no_darurat" class="form-control" value="{{ old('no_darurat', $employee->no_darurat ?? '') }}" placeholder="Nomor darurat (Emergency Contact)">
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="pendidikan">Pendidikan</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-graduation-cap"></i></span>
                                    </div>
                                    <input type="text" id="pendidikan" name="pendidikan" class="form-control" value="{{ old('pendidikan', $employee->pendidikan ?? '') }}">
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="gol_darah">Golongan Darah</label>
                                <select name="gol_darah" id="gol_darah" class="form-control">
                                    <option value="">- Pilih Golongan Darah -</option>
                                    <option value="A" {{ old('gol_darah', $employee->gol_darah ?? '') == 'A' ? 'selected' : '' }}>A</option>
                                    <option value="B" {{ old('gol_darah', $employee->gol_darah ?? '') == 'B' ? 'selected' : '' }}>B</option>
                                    <option value="AB" {{ old('gol_darah', $employee->gol_darah ?? '') == 'AB' ? 'selected' : '' }}>AB</option>
                                    <option value="O" {{ old('gol_darah', $employee->gol_darah ?? '') == 'O' ? 'selected' : '' }}>O</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="email">Email</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    </div>
                                    <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $employee->email ?? '') }}" placeholder="Email">
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="instagram">Instagram Accounts</label>
                                @php
                                    $instagrams = old('instagram', $employee->instagram ?? []);
                                    // If it's a JSON string, decode it
                                    if (is_string($instagrams)) {
                                        $decoded = json_decode($instagrams, true);
                                        $instagrams = is_array($decoded) ? $decoded : [$instagrams];
                                    }
                                    if (!is_array($instagrams)) {
                                        $instagrams = $instagrams ? [$instagrams] : [];
                                    }
                                @endphp
                                <div id="instagram-list">
                                    @foreach ($instagrams as $idx => $insta)
                                        <div class="input-group mb-1 instagram-input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                                            </div>
                                            <input type="text" name="instagram[]" class="form-control" value="{{ $insta }}" placeholder="Instagram username">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-danger" onclick="removeInstagramInput(this)"><i class="fas fa-times"></i></button>
                                            </div>
                                        </div>
                                    @endforeach
                                    <div class="input-group mb-1 instagram-input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                                        </div>
                                        <input type="text" name="instagram[]" class="form-control" value="" placeholder="Add another Instagram">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-danger" onclick="removeInstagramInput(this)"><i class="fas fa-times"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-link" onclick="addInstagramInput()">+ Add Instagram</button>
                                
                            </div>
                            <div class="form-group col-md-12">
                                <label for="alamat">Alamat</label>
                                <textarea id="alamat" name="alamat" class="form-control" rows="3">{{ old('alamat', $employee->alamat ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Data Kepegawaian Tab -->
                    <div class="tab-pane fade" id="kepegawaian" role="tabpanel" aria-labelledby="kepegawaian-tab">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="perusahaan">Perusahaan</label>
                                <select id="perusahaan" name="perusahaan" class="form-control select2">
                                    <option value="">-- Pilih Perusahaan --</option>
                                    <option value="Klinik Utama Premiere Belova" {{ old('perusahaan', $employee->perusahaan ?? '') == 'Klinik Utama Premiere Belova' ? 'selected' : '' }}>Klinik Utama Premiere Belova</option>
                                    <option value="Klinik Pratama Belova" {{ old('perusahaan', $employee->perusahaan ?? '') == 'Klinik Pratama Belova' ? 'selected' : '' }}>Klinik Pratama Belova</option>
                                    <option value="Belova Center Living" {{ old('perusahaan', $employee->perusahaan ?? '') == 'Belova Center Living' ? 'selected' : '' }}>Belova Center Living</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="kategori_pegawai">Kategori Pegawai</label>
                                <select id="kategori_pegawai" name="kategori_pegawai" class="form-control select2">
                                    <option value="">-- Pilih Kategori --</option>
                                    <option value="normal" {{ old('kategori_pegawai', $employee->kategori_pegawai ?? '') == 'normal' ? 'selected' : '' }}>Normal</option>
                                    <option value="khusus" {{ old('kategori_pegawai', $employee->kategori_pegawai ?? '') == 'khusus' ? 'selected' : '' }}>Khusus</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="no_induk">No Induk</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-fingerprint"></i></span>
                                    </div>
                                    <input type="text" id="no_induk" name="no_induk" class="form-control" value="{{ old('no_induk', $employee->no_induk ?? ($nextNoInduk ?? '') ) }}">
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="division_id">Divisi</label>
                                <select name="division_id" id="division_id" class="form-control select2">
                                    <option value="">-- Pilih Divisi --</option>
                                    @foreach($divisions as $division)
                                        <option value="{{ $division->id }}" {{ old('division_id', $employee->division_id ?? '') == $division->id ? 'selected' : '' }}>
                                            {{ $division->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="position_id">Posisi</label>
                                <select name="position_id" id="position_id" class="form-control select2">
                                    <option value="">-- Pilih Posisi --</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="status">Status</label>
                                <select name="status" id="status" class="form-control select2">
                                    <option value="">-- Pilih Status --</option>
                                    <option value="tetap" {{ old('status', $employee->status ?? '') == 'tetap' ? 'selected' : '' }}>Tetap</option>
                                    <option value="kontrak" {{ old('status', $employee->status ?? '') == 'kontrak' ? 'selected' : '' }}>Kontrak</option>
                                    <option value="tidak aktif" {{ old('status', $employee->status ?? '') == 'tidak aktif' ? 'selected' : '' }}>Tidak Aktif</option>
                                    <option value="freelance" {{ old('status', $employee->status ?? '') == 'freelance' ? 'selected' : '' }}>Freelance</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="tanggal_masuk">Tanggal Masuk</label>
                                <input type="date" id="tanggal_masuk" name="tanggal_masuk" class="form-control" value="{{ old('tanggal_masuk', isset($employee->tanggal_masuk) ? $employee->tanggal_masuk->format('Y-m-d') : '') }}">
                            </div>
                            <input type="hidden" id="durasi_kontrak" name="durasi_kontrak" value="{{ old('durasi_kontrak', isset($employee->kontrak_berakhir) && isset($employee->tanggal_masuk) ? round(($employee->kontrak_berakhir->timestamp - $employee->tanggal_masuk->timestamp) / (30 * 24 * 60 * 60)) : '') }}">
                            <input type="hidden" id="kontrak_berakhir" name="kontrak_berakhir" value="{{ old('kontrak_berakhir', isset($employee->kontrak_berakhir) ? $employee->kontrak_berakhir->format('Y-m-d') : '') }}">

                            <div class="form-group col-md-6">
                                <label for="gol_gaji_pokok_id">Gaji Pokok</label>
                                <select name="gol_gaji_pokok_id" id="gol_gaji_pokok_id" class="form-control select2">
                                    <option value="">-- Pilih Gaji Pokok --</option>
                                    @foreach($gajiPokokList as $gaji)
                                        <option value="{{ $gaji->id }}" {{ old('gol_gaji_pokok_id', $employee->gol_gaji_pokok_id ?? '') == $gaji->id ? 'selected' : '' }}>
                                            {{ $gaji->golongan }} - Rp{{ number_format($gaji->nominal,0,',','.') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="gol_tunjangan_jabatan_id">Tunjangan Jabatan</label>
                                <select name="gol_tunjangan_jabatan_id" id="gol_tunjangan_jabatan_id" class="form-control select2">
                                    <option value="">-- Pilih Tunjangan Jabatan --</option>
                                    @foreach($tunjanganJabatanList as $tunjangan)
                                        <option value="{{ $tunjangan->id }}" {{ old('gol_tunjangan_jabatan_id', $employee->gol_tunjangan_jabatan_id ?? '') == $tunjangan->id ? 'selected' : '' }}>
                                            {{ $tunjangan->golongan }} - Rp{{ number_format($tunjangan->nominal,0,',','.') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Dokumen Tab -->
                    <div class="tab-pane fade" id="dokumen" role="tabpanel" aria-labelledby="dokumen-tab">
                        <div class="form-row">
                            @php
                                $documents = [
                                    'doc_cv' => 'CV',
                                    'doc_ktp' => 'KTP',
                                    'doc_kontrak' => 'Kontrak',
                                    'doc_pendukung' => 'Dokumen Pendukung'
                                ];
                            @endphp
                            @foreach ($documents as $field => $label)
                                <div class="form-group col-md-6 mb-3">
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
                            @endforeach
                        </div>
                    </div>
                </div>
                
                <div class="form-group mt-4 text-center">
                    <button type="submit" class="btn btn-success btn-lg px-5" id="save-btn">
                        <i class="fas fa-save mr-2"></i>{{ isset($employee) ? 'Update' : 'Simpan' }}
                    </button>
                    <a href="{{ route('hrd.employee.index') }}" class="btn btn-secondary btn-lg ml-2">
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
function addInstagramInput() {
    var container = document.getElementById('instagram-list');
    var newInput = document.createElement('div');
    newInput.className = 'input-group mb-1 instagram-input-group';
    newInput.innerHTML = `
        <div class=\"input-group-prepend\">\n            <span class=\"input-group-text\"><i class=\"fab fa-instagram\"></i></span>\n        </div>\n        <input type=\"text\" name=\"instagram[]\" class=\"form-control\" value=\"\" placeholder=\"Instagram username\">\n        <div class=\"input-group-append\">\n            <button type=\"button\" class=\"btn btn-danger\" onclick=\"removeInstagramInput(this)\"><i class=\"fas fa-times\"></i></button>\n        </div>\n    `;
    container.appendChild(newInput);
}
function removeInstagramInput(btn) {
    var group = btn.closest('.instagram-input-group');
    if (group) {
        group.remove();
    }
}
</script>
<script>
// Store all positions in JS for dynamic filtering
var allPositions = [
    @foreach($positions as $position)
        {
            id: '{{ $position->id }}',
            name: '{{ $position->name }}',
            division_id: '{{ $position->division_id }}',
            selected: '{{ old('position_id', $employee->position_id ?? '') }}' == '{{ $position->id }}'
        },
    @endforeach
];

$(function() {
    // Initialize select2
    $('.select2').select2({
        width: '100%',
    });

    // Populate positions based on division
    function populatePositions() {
        var selectedDivision = $('#division_id').val();
        var $position = $('#position_id');
        var currentValue = $position.val();
        $position.empty();
        $position.append('<option value="">-- Pilih Posisi --</option>');
        allPositions.forEach(function(pos) {
            if (!selectedDivision || pos.division_id == selectedDivision) {
                var selected = (pos.selected || currentValue == pos.id) ? 'selected' : '';
                $position.append('<option value="'+pos.id+'" '+selected+'>'+pos.name+'</option>');
            }
        });
        $position.trigger('change.select2');
    }
    populatePositions();
    $('#division_id').on('change', function() {
        populatePositions();
    });

    // ...existing code for file input, contract date, etc...
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).siblings('.custom-file-label').addClass('selected').html(fileName);
    });
    function calculateEndDate() {
        var startDate = $('#tanggal_masuk').val();
        var duration = $('#durasi_kontrak').val();
        if (startDate && duration) {
            var start = new Date(startDate);
            var end = new Date(start);
            end.setMonth(end.getMonth() + parseInt(duration));
            var year = end.getFullYear();
            var month = String(end.getMonth() + 1).padStart(2, '0');
            var day = String(end.getDate()).padStart(2, '0');
            $('#kontrak_berakhir').val(`${year}-${month}-${day}`);
        }
    }
    $('#durasi_kontrak, #tanggal_masuk').on('change', function() {
        calculateEndDate();
    });
    if ($('#tanggal_masuk').val() && $('#durasi_kontrak').val()) {
        calculateEndDate();
    }
    $('#create_account').on('change', function() {
        if(this.checked) {
            $('#account_details').removeClass('d-none');
        } else {
            $('#account_details').addClass('d-none');
        }
    });
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
