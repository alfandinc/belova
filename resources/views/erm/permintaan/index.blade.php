@extends('layouts.erm.app')

@section('content')
<div class="container">
    <h1>Daftar Permintaan Pembelian</h1>
    <a href="{{ route('erm.permintaan.create') }}" class="btn btn-primary mb-3">Buat Permintaan</a>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tanggal Permintaan</th>
                <th>Status</th>
                <th>Jumlah Item</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($permintaans as $p)
            <tr>
                <td>{{ $p->id }}</td>
                <td>{{ $p->request_date }}</td>
                <td>{{ $p->status }}</td>
                <td>{{ $p->items->count() }}</td>
                <td>
                    @if($p->status === 'waiting_approval')
                    <form action="{{ route('erm.permintaan.approve', $p->id) }}" method="POST" style="display:inline-block;">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Approve permintaan ini?')">Approve</button>
                    </form>
                    @else
                    <span class="text-success">Approved</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    {{ $permintaans->links() }}
</div>
@endsection
