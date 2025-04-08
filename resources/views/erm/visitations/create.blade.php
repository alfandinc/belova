@extends('layouts.erm.app')
@section('title', 'Tambah Visitation')

@section('content')
<div class="container">
    <h2>Tambah Visitation Pasien</h2>
    <form action="{{ route('erm.visitations.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="pasien_id">Pasien</label>
            <select name="pasien_id" class="form-control" required>
                <option disabled selected>Pilih Pasien</option>
                @foreach($pasiens as $pasien)
                    <option value="{{ $pasien->id }}">{{ $pasien->nama }} - {{ $pasien->nik }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="dokter_id">Dokter</label>
            <select name="dokter_id" class="form-control" required>
                <option disabled selected>Pilih Dokter</option>
                @foreach($dokters as $dokter)
        <option value="{{ $dokter->id }}">
            {{ $dokter->user->name }} - {{ $dokter->spesialisasi->nama }}
        </option>
    @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="progress">Progress</label>
            <select name="progress" class="form-control" required>
                <option value="1">Perawat</option>
                <option value="2">Dokter</option>
            </select>
        </div>

        <div class="form-group">
            <label for="status">Status</label>
            <select name="status" class="form-control" required>
                <option value="Asesmen Awal">Asesmen Awal</option>
                <option value="CPPT">CPPT</option>
            </select>
        </div>

        <div class="form-group">
            <label for="visitation_date">Tanggal</label>
            <input type="date" name="visitation_date" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-success">Simpan</button>
    </form>
</div>
@endsection
