@extends('layouts.hrd.app')
@section('title', 'HRD | Employee')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container">
    <h2>Daftar Karyawan</h2>
    <a href="{{ route('hrd.employee.create') }}" class="btn btn-primary mb-3">Tambah Karyawan</a>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Posisi</th>
                <th>Status</th>
                <th>Tanggal Masuk</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($employees as $emp)
                <tr>
                    <td>{{ $emp->nama }}</td>
                    <td>{{ $emp->position->nama ?? '-' }}</td>
                    <td>{{ ucfirst($emp->status) }}</td>
                    <td>{{ $emp->tanggal_masuk }}</td>
                    <td>
                        <a href="{{ route('hrd.employee.edit', $emp->id) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('hrd.employee.destroy', $emp->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Hapus karyawan ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger">Hapus</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
