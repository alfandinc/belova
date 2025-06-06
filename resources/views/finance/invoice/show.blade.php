@extends('layouts.finance.app')
@section('title', 'Invoice Detail')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection
@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Detail Invoice</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('finance.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('finance.invoice.index') }}">Invoice</a></li>
                <li class="breadcrumb-item active">{{ $invoice->invoice_number }}</li>
            </ol>
        </nav>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h4 class="mb-3">Informasi Invoice</h4>
                    <table class="table table-borderless">
                        <tr>
                            <td style="width: 150px">Nomor Invoice</td>
                            <td>: <strong>{{ $invoice->invoice_number }}</strong></td>
                        </tr>
                        <tr>
                            <td>Tanggal</td>
                            <td>: {{ \Carbon\Carbon::parse($invoice->created_at)->format('d F Y') }}</td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>: 
                                @php
                                    $badgeClass = [
                                        'draft' => 'badge-secondary',
                                        'issued' => 'badge-primary',
                                        'paid' => 'badge-success',
                                        'canceled' => 'badge-danger'
                                    ][$invoice->status] ?? 'badge-secondary';
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ ucfirst($invoice->status) }}</span>
                            </td>
                        </tr>
                        @if($invoice->payment_date)
                        <tr>
                            <td>Tanggal Pembayaran</td>
                            <td>: {{ \Carbon\Carbon::parse($invoice->payment_date)->format('d F Y') }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
                <div class="col-md-6">
                    <h4 class="mb-3">Informasi Pasien</h4>
                    <table class="table table-borderless">
                        <tr>
                            <td style="width: 150px">Nama Pasien</td>
                            <td>: <strong>{{ $invoice->visitation->pasien->nama ?? '-' }}</strong></td>
                        </tr>
                        <tr>
                            <td>No. RM</td>
                            <td>: {{ $invoice->visitation->pasien->no_rm ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td>Klinik</td>
                            <td>: {{ $invoice->visitation->klinik->nama ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td>Tanggal Kunjungan</td>
                            <td>: {{ \Carbon\Carbon::parse($invoice->visitation->tanggal_visitation)->format('d F Y') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h4 class="mb-3">Daftar Item</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Item</th>
                            <th>Deskripsi</th>
                            <th>Qty</th>
                            <th>Harga</th>
                            <th>Diskon</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->description }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td>
                                @if($item->discount > 0)
                                    @if($item->discount_type == '%')
                                        {{ $item->discount }}%
                                    @else
                                        Rp {{ number_format($item->discount, 0, ',', '.') }}
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td>Rp {{ number_format($item->final_amount, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6" class="text-right"><strong>Total</strong></td>
                            <td><strong>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h4 class="mb-3">Tindakan</h4>
            <div class="d-flex flex-wrap">
                <form action="{{ route('finance.invoice.updateStatus', $invoice->id) }}" method="POST" class="mr-2 mb-2">
                    @csrf
                    @method('PUT')
                    <div class="input-group">
                        <select name="status" class="form-control">
                            <option value="draft" {{ $invoice->status == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="issued" {{ $invoice->status == 'issued' ? 'selected' : '' }}>Issued</option>
                            <option value="paid" {{ $invoice->status == 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="canceled" {{ $invoice->status == 'canceled' ? 'selected' : '' }}>Canceled</option>
                        </select>
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-primary">Ubah Status</button>
                        </div>
                    </div>
                </form>
                
                <a href="{{ route('finance.invoice.print', $invoice->id) }}" class="btn btn-secondary mb-2" target="_blank">
                    <i class="fas fa-print mr-1"></i> Cetak Invoice
                </a>
                
                <a href="{{ route('finance.invoice.index') }}" class="btn btn-outline-secondary ml-2 mb-2">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>
@endsection