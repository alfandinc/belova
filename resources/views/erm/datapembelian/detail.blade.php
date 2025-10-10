@extends('layouts.erm.app')
@section('title', 'ERM | Detail Pembelian - ' . $pemasok->nama)
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection  
@section('content')
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row mt-3 align-items-center">
        <div class="col-md-6">
            <h2 class="mb-0">Detail Pembelian - {{ $pemasok->nama }}</h2>
        </div>
        <div class="col-md-6 text-right">
            <a href="{{ route('erm.datapembelian.index') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/erm">ERM</a></li>
                            <li class="breadcrumb-item"><a href="#" onclick="return false;">Pembelian</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('erm.datapembelian.index') }}">Data Pembelian</a></li>
                            <li class="breadcrumb-item active">Detail {{ $pemasok->nama }}</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->
    
    <!-- Supplier Info -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Informasi Pemasok</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="150"><strong>Nama Pemasok</strong></td>
                                    <td>: {{ $pemasok->nama }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Alamat</strong></td>
                                    <td>: {{ $pemasok->alamat ?: '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="150"><strong>Telepon</strong></td>
                                    <td>: {{ $pemasok->telepon ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Email</strong></td>
                                    <td>: {{ $pemasok->email ?: '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Purchase Summary -->
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media">
                        <div class="media-body">
                            <p class="text-muted fw-medium">Total Pembelian</p>
                            <h4 class="fw-bold">Rp {{ number_format($pemasok->fakturBeli->sum('total'), 0, ',', '.') }}</h4>
                        </div>
                        <div class="align-self-center">
                            <i class="mdi mdi-currency-usd h2 text-muted"></i>
                        </div>
                    </div>
                </div><!--end card-body-->
            </div><!--end card-->
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media">
                        <div class="media-body">
                            <p class="text-muted fw-medium">Jumlah Faktur</p>
                            <h4 class="fw-bold">{{ $pemasok->fakturBeli->count() }}</h4>
                        </div>
                        <div class="align-self-center">
                            <i class="mdi mdi-file-document h2 text-muted"></i>
                        </div>
                    </div>
                </div><!--end card-body-->
            </div><!--end card-->
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media">
                        <div class="media-body">
                            <p class="text-muted fw-medium">Jenis Item</p>
                            <h4 class="fw-bold">{{ $pemasok->fakturBeli->flatMap(function($f) { return $f->items->pluck('obat_id'); })->unique()->count() }}</h4>
                        </div>
                        <div class="align-self-center">
                            <i class="mdi mdi-package-variant h2 text-muted"></i>
                        </div>
                    </div>
                </div><!--end card-body-->
            </div><!--end card-->
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media">
                        <div class="media-body">
                            <p class="text-muted fw-medium">Pembelian Terakhir</p>
                            <h4 class="fw-bold">{{ $pemasok->fakturBeli->first()?->received_date ? \Carbon\Carbon::parse($pemasok->fakturBeli->first()->received_date)->format('d/m/Y') : '-' }}</h4>
                        </div>
                        <div class="align-self-center">
                            <i class="mdi mdi-calendar h2 text-muted"></i>
                        </div>
                    </div>
                </div><!--end card-body-->
            </div><!--end card-->
        </div>
    </div>

    <!-- Purchase History -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Riwayat Pembelian</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>No Faktur</th>
                                    <th>Tanggal Terima</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Jumlah Item</th>
                                    <th>Subtotal</th>
                                    <th>Diskon</th>
                                    <th>Pajak</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pemasok->fakturBeli as $faktur)
                                <tr>
                                    <td>{{ $faktur->no_faktur ?: '-' }}</td>
                                    <td>{{ $faktur->received_date ? \Carbon\Carbon::parse($faktur->received_date)->format('d/m/Y') : '-' }}</td>
                                    <td>{{ $faktur->due_date ? \Carbon\Carbon::parse($faktur->due_date)->format('d/m/Y') : '-' }}</td>
                                    <td>{{ $faktur->items->count() }} item</td>
                                    <td>Rp {{ number_format($faktur->subtotal ?: 0, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($faktur->global_diskon ?: 0, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($faktur->global_pajak ?: 0, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($faktur->total ?: 0, 0, ',', '.') }}</td>
                                    <td>
                                        @php
                                            $badgeClass = match($faktur->status) {
                                                'diminta' => 'badge-warning',
                                                'diterima' => 'badge-info',
                                                'diapprove' => 'badge-success',
                                                'diretur' => 'badge-danger',
                                                default => 'badge-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ $faktur->status }}</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center">Tidak ada data pembelian</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection