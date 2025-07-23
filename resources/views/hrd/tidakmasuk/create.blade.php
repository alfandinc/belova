@extends('layouts.hrd.app')
@section('title', 'Ajukan Tidak Masuk')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection
@section('content')
<div class="container">
    <h3>Form Pengajuan Tidak Masuk (Sakit/Izin)</h3>
    <form method="POST" action="{{ route('hrd.tidakmasuk.store') }}">
        @csrf
        <div class="form-group">
            <label for="jenis">Jenis</label>
            <select name="jenis" id="jenis" class="form-control" required>
                <option value="">Pilih Jenis</option>
                <option value="sakit">Sakit</option>
                <option value="izin">Izin</option>
            </select>
        </div>
        <div class="form-group">
            <label for="tanggal_mulai">Tanggal Mulai</label>
            <input type="date" name="tanggal_mulai" id="tanggal_mulai" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="tanggal_selesai">Tanggal Selesai</label>
            <input type="date" name="tanggal_selesai" id="tanggal_selesai" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="alasan">Alasan</label>
            <textarea name="alasan" id="alasan" class="form-control" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Ajukan</button>
    </form>
</div>
@endsection
