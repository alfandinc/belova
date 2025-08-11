@extends('layouts.hrd.app')
@section('title', 'HRD | Daftar Dokter')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container py-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title m-0"><i class="fas fa-user-md mr-2"></i>Tambah Dokter</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('hrd.dokters.store') }}" method="POST" enctype="multipart/form-data" id="dokterForm">
                @csrf
                @if(isset($dokter))
                    <input type="hidden" name="id" value="{{ $dokter->id }}">
                @endif
                <ul class="nav nav-tabs mb-3" id="dokterTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="pribadi-tab" data-toggle="tab" href="#pribadi" role="tab" aria-controls="pribadi" aria-selected="true">Data Pribadi</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="profesional-tab" data-toggle="tab" href="#profesional" role="tab" aria-controls="profesional" aria-selected="false">Data Profesional</a>
                    </li>
                </ul>
                <div class="tab-content" id="dokterTabContent">
                    <div class="tab-pane fade show active" id="pribadi" role="tabpanel" aria-labelledby="pribadi-tab">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="user_id"><i class="fas fa-user mr-1"></i>Pilih User</label>
                                    <select name="user_id" class="form-control select2" required>
                                        <option value="">-- Pilih User --</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ (isset($dokter) && $dokter->user_id == $user->id) ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="nik"><i class="fas fa-id-card mr-1"></i>NIK</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                        </div>
                                        <input type="text" name="nik" class="form-control" value="{{ $dokter->nik ?? '' }}">
                                    </div>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="alamat"><i class="fas fa-map-marker-alt mr-1"></i>Alamat</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                        </div>
                                        <input type="text" name="alamat" class="form-control" value="{{ $dokter->alamat ?? '' }}">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="no_hp"><i class="fas fa-phone mr-1"></i>No HP</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        </div>
                                        <input type="text" name="no_hp" class="form-control" value="{{ $dokter->no_hp ?? '' }}">
                                    </div>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="photo"><i class="fas fa-image mr-1"></i>Upload Dokumen Foto</label>
                                    <div class="custom-file">
                                        <input type="file" name="photo" class="custom-file-input" id="photo" accept="image/*">
                                        <label class="custom-file-label" for="photo">Pilih file foto</label>
                                    </div>
                                    @if(isset($dokter) && $dokter->photo)
                                        <div class="mt-2">
                                            <img src="{{ asset('storage/' . $dokter->photo) }}" alt="Foto Dokter" style="max-width:120px;max-height:120px;">
                                        </div>
                                    @endif
                                </div>
                                <div class="form-group mb-3">
                                    <label for="ttd"><i class="fas fa-signature mr-1"></i>Upload TTD (Foto)</label>
                                    <div class="custom-file">
                                        <input type="file" name="ttd" class="custom-file-input" id="ttd" accept="image/*">
                                        <label class="custom-file-label" for="ttd">Pilih file ttd</label>
                                    </div>
                                    @if(isset($dokter) && $dokter->ttd)
                                        <div class="mt-2">
                                            <img src="{{ asset('storage/' . $dokter->ttd) }}" alt="TTD Dokter" style="max-width:120px;max-height:120px;">
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="profesional" role="tabpanel" aria-labelledby="profesional-tab">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="sip"><i class="fas fa-id-badge mr-1"></i>Nomor SIP</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-id-badge"></i></span>
                                        </div>
                                        <input type="text" name="sip" class="form-control" value="{{ $dokter->sip ?? '' }}" required>
                                    </div>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="due_date_sip"><i class="fas fa-calendar-alt mr-1"></i>Due Date SIP</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                        </div>
                                        <input type="date" name="due_date_sip" class="form-control" value="{{ isset($dokter) && $dokter->due_date_sip ? $dokter->due_date_sip : '' }}">
                                    </div>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="str"><i class="fas fa-certificate mr-1"></i>Nomor STR</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-certificate"></i></span>
                                        </div>
                                        <input type="text" name="str" class="form-control" value="{{ $dokter->str ?? '' }}">
                                    </div>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="due_date_str"><i class="fas fa-calendar-check mr-1"></i>Due Date STR</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-calendar-check"></i></span>
                                        </div>
                                        <input type="date" name="due_date_str" class="form-control" value="{{ isset($dokter) && $dokter->due_date_str ? $dokter->due_date_str : '' }}">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="spesialisasi_id"><i class="fas fa-user-md mr-1"></i>Spesialisasi</label>
                                    <select name="spesialisasi_id" class="form-control select2" required>
                                        <option value="">-- Pilih Spesialisasi --</option>
                                        @foreach($spesialisasis as $s)
                                            <option value="{{ $s->id }}" {{ (isset($dokter) && $dokter->spesialisasi_id == $s->id) ? 'selected' : '' }}>{{ $s->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="klinik_id"><i class="fas fa-hospital mr-1"></i>Klinik</label>
                                    <select name="klinik_id" class="form-control select2" required>
                                        <option value="">-- Pilih Klinik --</option>
                                        @foreach($kliniks as $klinik)
                                            <option value="{{ $klinik->id }}" {{ (isset($dokter) && $dokter->klinik_id == $klinik->id) ? 'selected' : '' }}>{{ $klinik->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="status"><i class="fas fa-toggle-on mr-1"></i>Status</label>
                                    <div class="input-group">
                                        <select name="status" class="form-control select2" required>
                                            <option value="">-- Pilih Status --</option>
                                            <option value="Kontrak" {{ (isset($dokter) && $dokter->status == 'Kontrak') ? 'selected' : '' }}>Kontrak</option>
                                            <option value="Tetap" {{ (isset($dokter) && $dokter->status == 'Tetap') ? 'selected' : '' }}>Tetap</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-success btn-lg px-5 shadow">{{ isset($dokter) ? 'Update' : 'Simpan' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Enable Bootstrap tab switching
    $('#dokterTab a').on('click', function (e) {
        e.preventDefault();
        $(this).tab('show');
    });

    // Init select2
    $('.select2').select2({ width: '100%' });

    // Custom file input handling for photo and ttd
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).siblings('.custom-file-label').addClass('selected').html(fileName);
    });

    $('#dokterForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this)[0];
        var formData = new FormData(form);
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sukses',
                        text: response.message
                    }).then(() => {
                        window.location.href = "{{ route('hrd.dokters.index') }}";
                    });
                } else {
                    Swal.fire('Gagal', response.message || 'Terjadi kesalahan.', 'error');
                }
            },
            error: function(xhr) {
                let msg = 'Terjadi kesalahan.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).join('\n');
                }
                Swal.fire('Gagal', msg, 'error');
            }
        });
    });
});
</script>
@endpush
