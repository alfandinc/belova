@extends('layouts.bcl.app')

@section('title', 'Laporan Bulanan')

@php
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $agingTotal = array_sum($aging);
@endphp

@section('bcl-content')
<div class="page-title-box d-flex flex-wrap align-items-center justify-content-between" style="gap: 10px;">
    <div>
        <h4 class="page-title mb-0">Laporan Bulanan Kos BCL</h4>
        <span>{{ $month->translatedFormat('F Y') }}</span>
    </div>
    <form method="GET" action="{{ route('bcl.report') }}" class="d-flex align-items-center d-print-none" style="gap: 8px;">
        <input type="month" name="bulan" value="{{ $month->format('Y-m') }}" class="form-control form-control-sm" onchange="this.form.submit()">
        <button type="button" class="btn btn-secondary bcl-btn" onclick="window.print()"><i data-feather="printer"></i> Cetak</button>
    </form>
</div>
<div class="d-print-none">@include('bcl.partials.subnav', ['group' => 'finance'])</div>

<div class="bcl-kpis is-money">
    <div class="bcl-kpi is-static is-info"><span class="bcl-kpi__label">Okupansi</span><span class="bcl-kpi__value">{{ $occupancy }}%</span><small class="text-muted">{{ number_format($nights) }} dari {{ number_format($capacity) }} kamar-malam ({{ $roomCount }} kamar)</small></div>
    <div class="bcl-kpi is-static is-good"><span class="bcl-kpi__label">Kas masuk</span><span class="bcl-kpi__value">{{ $rp($cashInTotal) }}</span></div>
    <div class="bcl-kpi is-static is-bad"><span class="bcl-kpi__label">Kas keluar</span><span class="bcl-kpi__value">{{ $rp($cashOutTotal) }}</span></div>
    <div class="bcl-kpi is-static"><span class="bcl-kpi__label">Selisih kas</span><span class="bcl-kpi__value">{{ $rp($cashInTotal - $cashOutTotal) }}</span></div>
    <div class="bcl-kpi is-static is-warn"><span class="bcl-kpi__label">Piutang saat ini</span><span class="bcl-kpi__value">{{ $rp($agingTotal) }}</span></div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h4 class="card-title">Kas masuk per metode</h4></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    @forelse($byMethod as $method => $amount)
                    <tr><td>{{ $method }}</td><td class="text-right">{{ $rp($amount) }}</td></tr>
                    @empty
                    <tr><td class="text-muted">Tidak ada kas masuk.</td></tr>
                    @endforelse
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h4 class="card-title">Kas masuk per jenis</h4></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    @forelse($byIdentity as $label => $amount)
                    <tr><td>{{ $label }}</td><td class="text-right">{{ $rp($amount) }}</td></tr>
                    @empty
                    <tr><td class="text-muted">-</td></tr>
                    @endforelse
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h4 class="card-title">Kas keluar per jenis</h4></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    @forelse($outByIdentity as $label => $amount)
                    <tr><td>{{ $label }}</td><td class="text-right">{{ $rp($amount) }}</td></tr>
                    @empty
                    <tr><td class="text-muted">Tidak ada kas keluar.</td></tr>
                    @endforelse
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h4 class="card-title">Pendapatan diterima per kategori</h4></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    @forelse($perCategory as $label => $amount)
                    <tr><td>{{ $label }}</td><td class="text-right">{{ $rp($amount) }}</td></tr>
                    @empty
                    <tr><td class="text-muted">Tidak ada pembayaran.</td></tr>
                    @endforelse
                    <tr class="font-weight-bold"><td>Total</td><td class="text-right">{{ $rp($revenueTotal) }}</td></tr>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h4 class="card-title">Pendapatan diterima per kamar</h4></div>
            <div class="card-body p-0" style="max-height: 360px; overflow-y: auto;">
                <table class="table table-sm mb-0">
                    @forelse($perRoom as $label => $amount)
                    <tr><td>{{ $label }}</td><td class="text-right">{{ $rp($amount) }}</td></tr>
                    @empty
                    <tr><td class="text-muted">-</td></tr>
                    @endforelse
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h4 class="card-title">Umur piutang (hari ini)</h4></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    @foreach($aging as $bucket => $amount)
                    <tr class="{{ $bucket === '> 90 hari' && $amount > 0 ? 'text-danger font-weight-bold' : '' }}"><td>{{ $bucket }}</td><td class="text-center">{{ $agingCount[$bucket] }} tagihan</td><td class="text-right">{{ $rp($amount) }}</td></tr>
                    @endforeach
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h4 class="card-title">Pengeluaran per tipe</h4></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    @forelse($expenses as $label => $amount)
                    <tr><td>{{ $label }}</td><td class="text-right">{{ $rp($amount) }}</td></tr>
                    @empty
                    <tr><td class="text-muted">Tidak ada pengeluaran.</td></tr>
                    @endforelse
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h4 class="card-title">Dana titipan penyewa (hari ini)</h4></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr><td>Uang jaminan dipegang</td><td class="text-right">{{ $rp($guaranteeHeld) }}</td></tr>
                    <tr><td>Saldo deposit penyewa</td><td class="text-right">{{ $rp($depositHeld) }}</td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h4 class="card-title">Penyewa masuk ({{ $moveIns->count() }})</h4></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Tanggal</th><th>Penyewa</th><th>Kamar</th><th>Paket</th></tr></thead>
                    @forelse($moveIns->sortBy('tgl_mulai') as $trx)
                    <tr><td>{{ $trx->tgl_mulai }}</td><td>{{ $trx->renter->nama ?? '-' }}</td><td>{{ $trx->room->room_name ?? '-' }}</td><td>{{ $trx->lama_sewa }} {{ $trx->jangka_sewa }}</td></tr>
                    @empty
                    <tr><td colspan="4" class="text-muted">Tidak ada penyewa baru.</td></tr>
                    @endforelse
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h4 class="card-title">Penyewa keluar ({{ $moveOuts->count() }})</h4></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Tanggal</th><th>Penyewa</th><th>Kamar</th><th>Lama tinggal</th></tr></thead>
                    @forelse($moveOuts->sortBy('tgl_selesai') as $trx)
                    <tr><td>{{ $trx->tgl_selesai }}</td><td>{{ $trx->renter->nama ?? '-' }}</td><td>{{ $trx->room->room_name ?? '-' }}</td><td>{{ \Carbon\Carbon::parse($trx->tgl_mulai)->diffInDays(\Carbon\Carbon::parse($trx->tgl_selesai)) }} hari (transaksi terakhir)</td></tr>
                    @empty
                    <tr><td colspan="4" class="text-muted">Tidak ada penyewa keluar.</td></tr>
                    @endforelse
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
