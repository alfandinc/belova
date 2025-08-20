@extends('layouts.hrd.app')
@section('title', 'HRD | Daftar Karyawan')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection
@section('content')
<div class="container">
    <h2>Hasil Rekap Absensi</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Finger ID</th>
                <th>Nama</th>
                <th>Tanggal</th>
                <th>Jam Masuk</th>
                <th>Jam Keluar</th>
                <th>Shift Start</th>
                <th>Shift End</th>
                <th>Work Hour</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rekap as $row)
            <tr>
                <td>{{ $row['finger_id'] }}</td>
                <td>{{ $row['employee_name'] }}</td>
                <td>{{ $row['date'] }}</td>
                <td>{{ $row['jam_masuk'] }}</td>
                <td>{{ $row['jam_keluar'] }}</td>
                <td>{{ $row['shift_start'] }}</td>
                <td>{{ $row['shift_end'] }}</td>
                <td>{{ $row['work_hour'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <a href="{{ route('hrd.absensi_rekap.index') }}" class="btn btn-secondary">Upload Lagi</a>
</div>
@endsection
