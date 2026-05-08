@extends('layouts.erm.app')

@section('title', 'ERM | Obat Hibah')

@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mt-3 align-items-center mb-2">
        <div class="col-md-6">
            <h2 class="mb-0">Obat Hibah</h2>
        </div>
        <div class="col-md-6 text-md-right mt-2 mt-md-0">
            <a href="{{ route('erm.obat-hibah.create') }}" class="btn btn-primary">
                <i class="fa fa-plus"></i> Input Obat Hibah
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box py-1 mb-3">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Farmasi</li>
                            <li class="breadcrumb-item active">Obat Hibah</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nomor Hibah</th>
                            <th>Tanggal Terima</th>
                            <th>Sumber</th>
                            <th>Item</th>
                            <th>Input Oleh</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($hibahs as $hibah)
                            <tr>
                                <td>{{ $hibahs->firstItem() + $loop->index }}</td>
                                <td>{{ $hibah->nomor_hibah }}</td>
                                <td>{{ \Carbon\Carbon::parse($hibah->received_date)->format('d/m/Y') }}</td>
                                <td>{{ $hibah->sumber ?: '-' }}</td>
                                <td>
                                    @foreach($hibah->items as $item)
                                        <div>
                                            {{ $item->obat?->nama ?? '-' }} - {{ rtrim(rtrim(number_format((float) $item->qty, 4, '.', ''), '0'), '.') }}
                                            @if($item->gudang)
                                                ({{ $item->gudang->nama }})
                                            @endif
                                            @if($item->batch)
                                                | Batch: {{ $item->batch }}
                                            @endif
                                        </div>
                                    @endforeach
                                </td>
                                <td>{{ $hibah->creator?->name ?? '-' }}</td>
                                <td>{{ $hibah->notes ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Belum ada data obat hibah.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $hibahs->links() }}
    </div>
</div>
@endsection