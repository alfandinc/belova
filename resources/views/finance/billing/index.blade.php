@extends('layouts.finance.app')
@section('title', 'Finance | Billing')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection
@section('content')
<div class="container">
    <h3>Daftar Billing</h3>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nama Pasien</th>
                <th>Tanggal Visit</th>
                <th>Klinik</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($visitations as $visitation)
                <tr>
                    <td>{{ $visitation->pasien->nama }}</td>
                    <td>{{ \Carbon\Carbon::parse($visitation->tanggal_visitation)->locale('id')->format('j F Y') }}</td>
                    <td>{{ $visitation->klinik->nama }}</td>
                    <td>
                         <a href="{{ route('finance.billing.create', $visitation->id) }}" class="btn btn-sm btn-primary">Lihat Billing</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection