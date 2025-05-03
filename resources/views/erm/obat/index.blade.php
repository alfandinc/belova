@extends('layouts.erm.app')
@section('title', 'E-Resep')

@section('content')
<div class="container">
    <h1>Daftar Obat</h1>
    <a href="{{ route('erm.obat.create') }}" class="btn btn-primary mb-3">+ Tambah Obat</a>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nama Obat</th>
                <th>Zat Aktif</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($obats as $obat)
                <tr>
                    <td>{{ $obat->nama }}</td>
                    <td>
                        @foreach ($obat->zatAktifs as $zat)
                            <span class="badge bg-secondary">{{ $zat->nama }}</span>
                        @endforeach
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
