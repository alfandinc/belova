@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Edit Karyawan</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('hrd.employee.update', $hrd_employee->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-md-6"><label>Nama</label><input type="text" name="nama" class="form-control" value="{{ old('nama', $hrd_employee->nama) }}" required></div>
            <div class="col-md-6"><label>NIK</label><input type="text" name="nik" class="form-control" value="{{ old('nik', $hrd_employee->nik) }}" required></div>
            <div class="col-md-6"><label>Tempat Lahir</label><input type="text" name="tempat_lahir" class="form-control" value="{{ old('tempat_lahir', $hrd_employee->tempat_lahir) }}" required></div>
            <div class="col-md-6"><label>Tanggal Lahir</label><input type="date" name="tanggal_lahir" class="form-control" value="{{ old('tanggal_lahir', $hrd_employee->tanggal_lahir) }}" required></div>
            <div class="col-md-6"><label>Alamat</label><textarea name="alamat" class="form-control" required>{{ old('alamat', $hrd_employee->alamat) }}</textarea></div>
            <div class="col-md-6">
                <label>Desa</label>
                <select name="village_id" class="form-control" required>
                    <option value="">Pilih Desa</option>
                    {{-- @foreach ($villages as $village)
                        <option value="{{ $village->id }}" {{ old('village_id', $hrd_employee->village_id) == $village->id ? 'selected' : '' }}>{{ $village->name }}</option>
                    @endforeach --}}
                </select>
            </div>
            <div class="col-md-6">
                <label>Posisi</label>
                <select name="position" class="form-control" required>
                    <option value="">Pilih Posisi</option>
                    @foreach ($positions as $position)
                        <option value="{{ $position->id }}" {{ old('position', $hrd_employee->position) == $position->id ? 'selected' : '' }}>{{ $position->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6"><label>No HP</label><input type="text" name="no_hp" class="form-control" value="{{ old('no_hp', $hrd_employee->no_hp) }}"></div>
            <div class="col-md-6"><label>Pendidikan</label><input type="text" name="pendidikan" class="form-control" value="{{ old('pendidikan', $hrd_employee->pendidikan) }}"></div>
            <div class="col-md-6">
                <label>Status</label>
                <select name="status" class="form-control" required>
                    @foreach (['tetap', 'kontrak', 'tidak aktif'] as $status)
                        <option value="{{ $status }}" {{ old('status', $hrd_employee->status) == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6"><label>Tanggal Masuk</label><input type="date" name="tanggal_masuk" class="form-control" value="{{ old('tanggal_masuk', $hrd_employee->tanggal_masuk) }}"></div>
            <div class="col-md-6"><label>Kontrak Berakhir</label><input type="date" name="kontrak_berakhir" class="form-control" value="{{ old('kontrak_berakhir', $hrd_employee->kontrak_berakhir) }}"></div>
            <div class="col-md-6"><label>Masa Pensiun</label><input type="date" name="masa_pensiun" class="form-control" value="{{ old('masa_pensiun', $hrd_employee->masa_pensiun) }}"></div>

            @foreach (['doc_cv' => 'CV', 'doc_ktp' => 'KTP', 'doc_kontrak' => 'Kontrak', 'doc_pendukung' => 'Dokumen Pendukung'] as $field => $label)
            <div class="col-md-6">
                <label>{{ $label }}</label>
                <input type="file" name="{{ $field }}" class="form-control">
                @if ($hrd_employee->$field)
                    <a href="{{ asset('storage/' . $hrd_employee->$field) }}" target="_blank">Lihat {{ $label }}</a>
                @endif
            </div>
            @endforeach
        </div>

        <button class="btn btn-primary mt-3">Update</button>
    </form>
</div>
@endsection
