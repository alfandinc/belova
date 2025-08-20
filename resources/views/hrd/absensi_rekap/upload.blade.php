@extends('layouts.hrd.app')
@section('title', 'HRD | Daftar Karyawan')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection
@section('content')
<div class="container">
    <h2>Upload Rekap Absensi</h2>
    <form action="{{ route('hrd.absensi_rekap.upload') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label for="file">File Absensi (XLS/XLSX):</label>
            <input type="file" name="file" id="file" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary mt-2">Upload</button>
    </form>
</div>
@endsection
